<?php

namespace App\Services\Scoring;

use App\Models\CasualMatch;
use App\Models\Player;

/**
 * Produces the same MatchScoreState-shaped array as MatchStatePresenter, keyed on player ids
 * instead of tournament entrant ids, so the existing scoring console component can render a
 * casual match without any frontend changes.
 */
class CasualMatchStatePresenter
{
    public function __construct(private readonly CasualMatchScoringService $scoring) {}

    public function present(CasualMatch $match): array
    {
        $match->load(['creator.user', 'opponent.user', 'games.points']);

        $currentGame = $match->currentGame();
        $server = null;
        $expediteActive = false;
        $expediteSecondsRemaining = null;

        if ($currentGame) {
            $server = $this->scoring->currentServerFor($currentGame, $match);
            $expedite = $this->scoring->expediteStateFor($currentGame);
            $expediteActive = $expedite['active'];
            $expediteSecondsRemaining = $expedite['seconds_remaining'];
        }

        return [
            'id' => $match->id,
            'code' => $match->code,
            'match_type' => $match->match_type,
            'status' => $match->status,
            'entrant1_id' => $match->creator_player_id,
            'entrant2_id' => $match->opponent_player_id,
            'entrant1_name' => $this->playerLabel($match->creator),
            'entrant2_name' => $match->opponent ? $this->playerLabel($match->opponent) : 'Esperando rival...',
            'best_of' => $match->best_of,
            'points_to_win' => $match->points_to_win,
            'score_summary' => $match->score_summary,
            'winner_entrant_id' => $match->winner_player_id,
            'games' => $match->games->map(fn ($g) => [
                'id' => $g->id,
                'game_number' => $g->game_number,
                'entrant1_points' => $g->creator_points,
                'entrant2_points' => $g->opponent_points,
                'winner_entrant_id' => $g->winner_player_id,
                'first_server_entrant_id' => $g->first_server_player_id,
            ])->values(),
            'current_game_number' => $currentGame?->game_number,
            'current_server_entrant_id' => $server,
            'expedite_active' => $expediteActive,
            'expedite_seconds_remaining' => $expediteSecondsRemaining,
            'is_deciding_game' => $currentGame?->game_number === $match->best_of,
            'deciding_game_ends_switched' => $currentGame
                && $currentGame->game_number === $match->best_of
                && ($currentGame->creator_points >= 5 || $currentGame->opponent_points >= 5),
        ];
    }

    private function playerLabel(?Player $player): string
    {
        return $player?->user?->name ?? 'Jugador';
    }
}
