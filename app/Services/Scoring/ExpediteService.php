<?php

namespace App\Services\Scoring;

use App\Models\MatchGame;

/**
 * Automates only the part of the ITTF "expedite system" that a rules engine can enforce:
 * detecting the 10-minute mark and switching to serve-every-point. The associated 13-shot
 * return rule requires a human umpire's judgement (or computer vision) and is not modeled here.
 */
class ExpediteService
{
    private const THRESHOLD_MINUTES = 10;

    public function isActive(MatchGame $game): bool
    {
        if ($game->isComplete() || $game->started_at === null) {
            return false;
        }

        return $game->started_at->diffInSeconds(now()) >= self::THRESHOLD_MINUTES * 60;
    }

    public function secondsRemaining(MatchGame $game): ?int
    {
        if ($game->isComplete() || $game->started_at === null) {
            return null;
        }

        $remaining = self::THRESHOLD_MINUTES * 60 - $game->started_at->diffInSeconds(now());

        return max(0, $remaining);
    }
}
