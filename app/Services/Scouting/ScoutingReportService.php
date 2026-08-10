<?php

namespace App\Services\Scouting;

use App\Models\CasualMatch;
use App\Models\CasualMatchGame;
use App\Models\MatchGame;
use App\Models\Player;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistrationDivision;
use Illuminate\Support\Collection;

class ScoutingReportService
{
    /**
     * Derive scouting insights purely from point-by-point history already recorded during
     * live scoring — no extra data collection needed. Covers singles tournament matches
     * (doubles/team entrants are excluded since a single "player" can't be attributed to a
     * shared team score) and all "retos" (casual matches), which are always 1v1.
     */
    public function forPlayer(Player $player): array
    {
        $gamesAnalyzed = 0;
        $deucePlayed = 0;
        $deuceWon = 0;
        $deciderPlayed = 0;
        $deciderWon = 0;
        $bestStreak = 0;
        $bestStreakOpponent = null;
        $bestStreakDate = null;

        foreach ([...$this->tournamentGames($player), ...$this->casualGames($player)] as $entry) {
            $gamesAnalyzed++;

            if ($entry['is_deuce']) {
                $deucePlayed++;
                $deuceWon += $entry['won'] ? 1 : 0;
            }

            if ($entry['is_decider']) {
                $deciderPlayed++;
                $deciderWon += $entry['won'] ? 1 : 0;
            }

            if ($entry['streak'] > $bestStreak) {
                $bestStreak = $entry['streak'];
                $bestStreakOpponent = $entry['opponent'];
                $bestStreakDate = $entry['date'];
            }
        }

        return [
            'games_analyzed' => $gamesAnalyzed,
            'deuce' => [
                'played' => $deucePlayed,
                'won' => $deuceWon,
                'win_rate' => $deucePlayed > 0 ? (int) round($deuceWon / $deucePlayed * 100) : null,
            ],
            'decider' => [
                'played' => $deciderPlayed,
                'won' => $deciderWon,
                'win_rate' => $deciderPlayed > 0 ? (int) round($deciderWon / $deciderPlayed * 100) : null,
            ],
            'best_streak' => [
                'points' => $bestStreak,
                'opponent' => $bestStreakOpponent,
                'date' => $bestStreakDate,
            ],
        ];
    }

    /**
     * @return array<int, array{won: bool, is_deuce: bool, is_decider: bool, streak: int, opponent: ?string, date: ?string}>
     */
    private function tournamentGames(Player $player): array
    {
        $entrantIds = TournamentRegistrationDivision::whereHas(
            'registration', fn ($q) => $q->where('player_id', $player->id)
        )->whereHas(
            'division', fn ($q) => $q->where('category_type', 'singles')
        )->pluck('id');

        if ($entrantIds->isEmpty()) {
            return [];
        }

        $matches = TournamentMatch::where(fn ($q) => $q->whereIn('entrant1_id', $entrantIds)->orWhereIn('entrant2_id', $entrantIds))
            ->where('status', 'completed')
            ->with([
                'division:id,best_of',
                'games.points:id,match_game_id,point_number,scoring_entrant_id',
                'entrant1.registration.player.user:id,name',
                'entrant2.registration.player.user:id,name',
            ])
            ->get();

        $entries = [];

        foreach ($matches as $match) {
            $myEntrantId = $entrantIds->contains($match->entrant1_id) ? $match->entrant1_id : $match->entrant2_id;
            $opponentEntrant = $myEntrantId === $match->entrant1_id ? $match->entrant2 : $match->entrant1;
            $opponentName = $opponentEntrant?->registration?->player?->user?->name;
            $bestOf = $match->division?->best_of;

            /** @var MatchGame $game */
            foreach ($match->games as $game) {
                if (! $game->isComplete()) {
                    continue;
                }

                $myScore = $myEntrantId === $match->entrant1_id ? $game->entrant1_points : $game->entrant2_points;
                $oppScore = $myEntrantId === $match->entrant1_id ? $game->entrant2_points : $game->entrant1_points;

                $entries[] = [
                    'won' => $game->winner_entrant_id === $myEntrantId,
                    'is_deuce' => min($myScore, $oppScore) >= 10,
                    'is_decider' => $bestOf !== null && $game->game_number === $bestOf,
                    'streak' => $this->longestStreak($game->points, $myEntrantId, 'scoring_entrant_id'),
                    'opponent' => $opponentName,
                    'date' => $game->completed_at?->toDateString(),
                ];
            }
        }

        return $entries;
    }

    /**
     * @return array<int, array{won: bool, is_deuce: bool, is_decider: bool, streak: int, opponent: ?string, date: ?string}>
     */
    private function casualGames(Player $player): array
    {
        $matches = CasualMatch::where(fn ($q) => $q->where('creator_player_id', $player->id)->orWhere('opponent_player_id', $player->id))
            ->where('status', 'completed')
            ->with([
                'games.points:id,casual_match_game_id,point_number,scoring_player_id',
                'creator.user:id,name',
                'opponent.user:id,name',
            ])
            ->get();

        $entries = [];

        foreach ($matches as $match) {
            $isCreator = $match->creator_player_id === $player->id;
            $opponentName = $isCreator ? $match->opponent?->user?->name : $match->creator?->user?->name;

            /** @var CasualMatchGame $game */
            foreach ($match->games as $game) {
                if (! $game->isComplete()) {
                    continue;
                }

                $myScore = $isCreator ? $game->creator_points : $game->opponent_points;
                $oppScore = $isCreator ? $game->opponent_points : $game->creator_points;

                $entries[] = [
                    'won' => $game->winner_player_id === $player->id,
                    'is_deuce' => min($myScore, $oppScore) >= 10,
                    'is_decider' => $match->best_of !== null && $game->game_number === $match->best_of,
                    'streak' => $this->longestStreak($game->points, $player->id, 'scoring_player_id'),
                    'opponent' => $opponentName,
                    'date' => $game->completed_at?->toDateString(),
                ];
            }
        }

        return $entries;
    }

    private function longestStreak(Collection $points, int $scorerId, string $field): int
    {
        $best = 0;
        $current = 0;

        foreach ($points as $point) {
            if ($point->{$field} === $scorerId) {
                $current++;
                $best = max($best, $current);
            } else {
                $current = 0;
            }
        }

        return $best;
    }
}
