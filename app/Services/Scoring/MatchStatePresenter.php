<?php

namespace App\Services\Scoring;

use App\Models\TournamentMatch;
use App\Models\TournamentRegistrationDivision;

class MatchStatePresenter
{
    public function __construct(
        private readonly ServeRotationCalculator $serveRotation,
        private readonly ExpediteService $expedite,
    ) {}

    public function present(TournamentMatch $match): array
    {
        $match->load([
            'entrant1.registration.player.user',
            'entrant2.registration.player.user',
            'division',
            'games.points',
        ]);

        $currentGame = $match->currentGame();
        $server = null;
        $expediteActive = false;
        $expediteSecondsRemaining = null;

        if ($currentGame) {
            $server = $this->serveRotation->currentServer($currentGame, $match->entrant1_id, $match->entrant2_id);
            $expediteActive = $this->expedite->isActive($currentGame);
            $expediteSecondsRemaining = $this->expedite->secondsRemaining($currentGame);
        }

        return [
            'id' => $match->id,
            'status' => $match->status,
            'entrant1_id' => $match->entrant1_id,
            'entrant2_id' => $match->entrant2_id,
            'entrant1_name' => $this->entrantLabel($match->entrant1),
            'entrant2_name' => $this->entrantLabel($match->entrant2),
            'best_of' => $match->division->best_of,
            'points_to_win' => $match->division->points_to_win,
            'score_summary' => $match->score_summary,
            'winner_entrant_id' => $match->winner_entrant_id,
            'games' => $match->games->map(fn ($g) => [
                'id' => $g->id,
                'game_number' => $g->game_number,
                'entrant1_points' => $g->entrant1_points,
                'entrant2_points' => $g->entrant2_points,
                'winner_entrant_id' => $g->winner_entrant_id,
                'first_server_entrant_id' => $g->first_server_entrant_id,
            ])->values(),
            'current_game_number' => $currentGame?->game_number,
            'current_server_entrant_id' => $server,
            'expedite_active' => $expediteActive,
            'expedite_seconds_remaining' => $expediteSecondsRemaining,
            'is_deciding_game' => $currentGame?->game_number === $match->division->best_of,
            'deciding_game_ends_switched' => $currentGame
                && $currentGame->game_number === $match->division->best_of
                && ($currentGame->entrant1_points >= 5 || $currentGame->entrant2_points >= 5),
        ];
    }

    private function entrantLabel(?TournamentRegistrationDivision $entrant): string
    {
        if (! $entrant) {
            return 'Por definir';
        }

        $name = $entrant->registration->player->user->name;

        return $entrant->partner_name ? "{$name} / {$entrant->partner_name}" : $name;
    }
}
