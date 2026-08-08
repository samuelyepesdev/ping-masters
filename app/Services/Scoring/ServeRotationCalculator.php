<?php

namespace App\Services\Scoring;

use App\Models\MatchGame;

/**
 * ITTF service rules: serve alternates every 2 points, except once the game reaches 10-10
 * (or the expedite system is in effect) when it alternates every point.
 */
class ServeRotationCalculator
{
    public function __construct(private readonly ExpediteService $expedite) {}

    public function currentServer(MatchGame $game, int $entrant1Id, int $entrant2Id): int
    {
        $firstServer = $game->first_server_entrant_id;
        $secondServer = $firstServer === $entrant1Id ? $entrant2Id : $entrant1Id;

        $totalPoints = $game->entrant1_points + $game->entrant2_points;
        $deuce = $game->entrant1_points >= 10 && $game->entrant2_points >= 10;

        if ($deuce || $this->expedite->isActive($game)) {
            return $totalPoints % 2 === 0 ? $firstServer : $secondServer;
        }

        return intdiv($totalPoints, 2) % 2 === 0 ? $firstServer : $secondServer;
    }
}
