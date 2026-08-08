<?php

namespace App\Services\Progression;

use App\Models\Player;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistrationDivision;
use App\Services\Achievements\AchievementEngine;
use App\Services\Brackets\BracketAdvancementService;
use App\Services\Brackets\StandingsService;
use App\Services\Ratings\EloRatingService;
use App\Services\Xp\XpService;

class PlayerProgressionService
{
    public function __construct(
        private readonly EloRatingService $elo,
        private readonly XpService $xp,
        private readonly AchievementEngine $achievements,
        private readonly BracketAdvancementService $advancement,
        private readonly StandingsService $standings,
    ) {}

    /**
     * Apply rating, XP, and achievement progression for a match result. Byes and walkovers
     * don't reflect real playing strength, so they're excluded from rating and win/loss XP
     * (though a walkover-decided division final can still miss the champion bonus — a rare,
     * accepted gap rather than something worth extra bookkeeping for).
     */
    public function applyForMatch(TournamentMatch $match): void
    {
        if ($match->isBye() || $match->status !== 'completed') {
            return;
        }

        $winner = $this->resolvePlayer($match->winnerEntrant);
        $loser = $this->resolvePlayer($match->loserEntrant);

        if (! $winner || ! $loser) {
            return;
        }

        $this->elo->applyMatchResult($match, $winner, $loser);
        $this->xp->awardForMatch($match, $winner, $loser);
        $this->achievements->evaluateForPlayer($winner);
        $this->achievements->evaluateForPlayer($loser);

        $this->maybeAwardDivisionChampion($match->division);
    }

    private function maybeAwardDivisionChampion(TournamentDivision $division): void
    {
        if (! $this->advancement->isDivisionComplete($division)) {
            return;
        }

        $championEntrant = match ($division->format) {
            'single_elimination', 'group_knockout' => $this->lastStageWinner($division, 'main_bracket'),
            'double_elimination' => $this->lastStageWinner($division, 'grand_final'),
            'round_robin', 'swiss' => $this->topStandingEntrant($division),
            default => null,
        };

        $player = $this->resolvePlayer($championEntrant);

        if (! $player) {
            return;
        }

        $anchorMatch = $division->matches()
            ->whereIn('status', ['completed', 'walkover'])
            ->orderByDesc('completed_at')
            ->first();

        if (! $anchorMatch) {
            return;
        }

        $this->xp->awardForDivisionChampion($player, $division->tournament, $anchorMatch);
        $this->achievements->evaluateForPlayer($player);
    }

    private function lastStageWinner(TournamentDivision $division, string $stage): ?TournamentRegistrationDivision
    {
        $match = TournamentMatch::where('matches.tournament_division_id', $division->id)
            ->whereHas('round', fn ($q) => $q->where('stage', $stage))
            ->whereNotNull('winner_entrant_id')
            ->join('rounds', 'rounds.id', '=', 'matches.round_id')
            ->orderByDesc('rounds.round_number')
            ->select('matches.*')
            ->first();

        return $match?->winnerEntrant;
    }

    private function topStandingEntrant(TournamentDivision $division): ?TournamentRegistrationDivision
    {
        $standings = $this->standings->standingsForDivision($division);
        $topEntrantId = $standings->first()['entrant_id'] ?? null;

        return $topEntrantId ? TournamentRegistrationDivision::find($topEntrantId) : null;
    }

    private function resolvePlayer(?TournamentRegistrationDivision $entrant): ?Player
    {
        return $entrant?->registration?->player;
    }
}
