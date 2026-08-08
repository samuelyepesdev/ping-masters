<?php

namespace App\Services\Brackets;

use App\Models\TournamentDivision;
use App\Models\TournamentGroup;
use App\Models\TournamentMatch;

class BracketAdvancementService
{
    public function __construct(private readonly StandingsService $standings) {}

    /**
     * Propagate a completed match's winner/loser into whichever future matches source from it.
     * Handles chained byes recursively (a fed-in entrant that immediately faces a bye slot
     * auto-completes and propagates again).
     */
    public function advance(TournamentMatch $match): void
    {
        if (! in_array($match->status, ['completed', 'walkover'], true)) {
            return;
        }

        $dependents = TournamentMatch::query()
            ->where('entrant1_source_match_id', $match->id)
            ->orWhere('entrant2_source_match_id', $match->id)
            ->get();

        foreach ($dependents as $dependent) {
            $changed = false;

            if ($dependent->entrant1_source_match_id === $match->id) {
                $value = $dependent->entrant1_source_type === 'winner' ? $match->winner_entrant_id : $match->loser_entrant_id;
                $dependent->entrant1_id = $value;
                $dependent->entrant1_is_bye = $value === null;
                $changed = true;
            }

            if ($dependent->entrant2_source_match_id === $match->id) {
                $value = $dependent->entrant2_source_type === 'winner' ? $match->winner_entrant_id : $match->loser_entrant_id;
                $dependent->entrant2_id = $value;
                $dependent->entrant2_is_bye = $value === null;
                $changed = true;
            }

            if (! $changed) {
                continue;
            }

            $this->resolveMatchState($dependent);
        }
    }

    private function resolveMatchState(TournamentMatch $match): void
    {
        $awaitingSlot1 = $match->entrant1_id === null && ! $match->entrant1_is_bye
            && ($match->entrant1_source_match_id !== null || $match->entrant1_source_group_id !== null);
        $awaitingSlot2 = $match->entrant2_id === null && ! $match->entrant2_is_bye
            && ($match->entrant2_source_match_id !== null || $match->entrant2_source_group_id !== null);

        if ($awaitingSlot1 || $awaitingSlot2) {
            $match->save();

            return;
        }

        if ($match->entrant1_id !== null && $match->entrant2_id !== null) {
            $match->status = 'ready';
            $match->save();

            return;
        }

        // Exactly one side present (or none) and nothing left pending: it's a bye, auto-complete.
        $winnerId = $match->entrant1_id ?? $match->entrant2_id;

        if ($winnerId === null) {
            $match->save();

            return;
        }

        $match->winner_entrant_id = $winnerId;
        $match->loser_entrant_id = null;
        $match->status = 'completed';
        $match->completed_at = now();
        $match->save();

        $this->advance($match);
    }

    /**
     * Once every match in a group has finished, fill any knockout-stage slots that source
     * from a given finishing rank in that group.
     */
    public function maybeAdvanceGroupStage(TournamentGroup $group): void
    {
        $totalMatches = TournamentMatch::where('tournament_group_id', $group->id)->count();
        $finishedMatches = TournamentMatch::where('tournament_group_id', $group->id)
            ->whereIn('status', ['completed', 'walkover'])
            ->count();

        if ($totalMatches === 0 || $totalMatches !== $finishedMatches) {
            return;
        }

        $standings = $this->standings->standingsForGroup($group);

        $dependents = TournamentMatch::query()
            ->where('entrant1_source_group_id', $group->id)
            ->orWhere('entrant2_source_group_id', $group->id)
            ->get();

        foreach ($dependents as $dependent) {
            $changed = false;

            if ($dependent->entrant1_source_group_id === $group->id) {
                $rank = $dependent->entrant1_source_group_rank;
                $dependent->entrant1_id = $standings->get($rank - 1)['entrant_id'] ?? null;
                $changed = true;
            }

            if ($dependent->entrant2_source_group_id === $group->id) {
                $rank = $dependent->entrant2_source_group_rank;
                $dependent->entrant2_id = $standings->get($rank - 1)['entrant_id'] ?? null;
                $changed = true;
            }

            if ($changed) {
                $this->resolveMatchState($dependent);
            }
        }
    }

    /**
     * Record a match result (used by the temporary admin test action in Milestone 3, and
     * reused as-is by the live scoring engine in Milestone 4).
     */
    public function recordResult(TournamentMatch $match, int $winnerEntrantId, string $status = 'completed'): void
    {
        $loserEntrantId = $match->entrant1_id === $winnerEntrantId ? $match->entrant2_id : $match->entrant1_id;

        $match->winner_entrant_id = $winnerEntrantId;
        $match->loser_entrant_id = $loserEntrantId;
        $match->status = $status;
        $match->completed_at = now();
        $match->save();

        $this->advance($match);

        if ($match->tournament_group_id) {
            $group = $match->group ?? TournamentGroup::find($match->tournament_group_id);

            if ($group) {
                $this->maybeAdvanceGroupStage($group);
            }
        }
    }

    public function recordWalkover(TournamentMatch $match, int $winnerEntrantId): void
    {
        $this->recordResult($match, $winnerEntrantId, 'walkover');
    }

    public function isDivisionComplete(TournamentDivision $division): bool
    {
        $matches = $division->matches()->get();

        if ($matches->isEmpty()) {
            return false;
        }

        return $matches->every(fn (TournamentMatch $match) => in_array($match->status, ['completed', 'walkover', 'cancelled'], true));
    }
}
