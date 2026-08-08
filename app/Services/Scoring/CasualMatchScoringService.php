<?php

namespace App\Services\Scoring;

use App\Models\CasualMatch;
use App\Models\CasualMatchGame;
use App\Models\CasualMatchPoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Scores a peer-to-peer "reto" match between two players. Deliberately independent from
 * MatchScoringService: a casual match has no division/tournament/bracket behind it, so it
 * carries its own small copy of the ITTF serve-rotation/expedite/deuce rules instead of
 * coupling to the tournament scoring engine.
 */
class CasualMatchScoringService
{
    private const EXPEDITE_THRESHOLD_MINUTES = 10;

    public function startMatch(CasualMatch $match, int $firstServerPlayerId): CasualMatchGame
    {
        if ($match->status !== 'ready') {
            throw new RuntimeException('El reto no está listo para comenzar.');
        }

        if (! in_array($firstServerPlayerId, [$match->creator_player_id, $match->opponent_player_id], true)) {
            throw new RuntimeException('El jugador que saca debe ser uno de los dos participantes.');
        }

        return DB::transaction(function () use ($match, $firstServerPlayerId) {
            $match->update(['status' => 'in_progress', 'started_at' => now()]);

            return CasualMatchGame::create([
                'casual_match_id' => $match->id,
                'game_number' => 1,
                'first_server_player_id' => $firstServerPlayerId,
                'started_at' => now(),
            ]);
        });
    }

    public function recordPoint(CasualMatch $match, int $scoringPlayerId, ?User $recorder = null): CasualMatchGame
    {
        if ($match->status !== 'in_progress') {
            throw new RuntimeException('El reto no está en curso.');
        }

        if (! in_array($scoringPlayerId, [$match->creator_player_id, $match->opponent_player_id], true)) {
            throw new RuntimeException('El punto debe asignarse a uno de los dos participantes.');
        }

        $game = $match->currentGame();

        if (! $game) {
            throw new RuntimeException('No hay un juego activo para este reto.');
        }

        return DB::transaction(function () use ($match, $scoringPlayerId, $recorder, $game) {
            $creatorId = $match->creator_player_id;
            $opponentId = $match->opponent_player_id;

            $server = $this->currentServer($game, $creatorId, $opponentId);
            $isExpedite = $this->isExpediteActive($game);

            if ($scoringPlayerId === $creatorId) {
                $game->creator_points++;
            } else {
                $game->opponent_points++;
            }

            $pointNumber = $game->points()->count() + 1;

            CasualMatchPoint::create([
                'casual_match_id' => $match->id,
                'casual_match_game_id' => $game->id,
                'point_number' => $pointNumber,
                'scoring_player_id' => $scoringPlayerId,
                'server_player_id' => $server,
                'creator_points_after' => $game->creator_points,
                'opponent_points_after' => $game->opponent_points,
                'was_expedite' => $isExpedite,
                'recorded_by' => $recorder?->id,
            ]);

            $gameWinnerId = $this->checkGameWinner($game, $match->points_to_win, $creatorId, $opponentId);

            if ($gameWinnerId === null) {
                $game->save();

                return $game->fresh();
            }

            $game->winner_player_id = $gameWinnerId;
            $game->completed_at = now();
            $game->save();

            $creatorGames = $match->games()->where('winner_player_id', $creatorId)->count();
            $opponentGames = $match->games()->where('winner_player_id', $opponentId)->count();
            $match->score_summary = "{$creatorGames}-{$opponentGames}";

            $gamesToWinMatch = (int) ceil($match->best_of / 2);

            if ($creatorGames >= $gamesToWinMatch || $opponentGames >= $gamesToWinMatch) {
                $matchWinnerId = $creatorGames > $opponentGames ? $creatorId : $opponentId;
                $matchLoserId = $matchWinnerId === $creatorId ? $opponentId : $creatorId;

                $match->winner_player_id = $matchWinnerId;
                $match->loser_player_id = $matchLoserId;
                $match->status = 'completed';
                $match->completed_at = now();
            } else {
                $this->startNextGame($match, $game, $creatorId, $opponentId);
            }

            $match->save();

            return $game->fresh();
        });
    }

    public function undoLastPoint(CasualMatch $match): void
    {
        if (in_array($match->status, ['completed', 'cancelled'], true)) {
            throw new RuntimeException('No se puede deshacer: el reto ya finalizó.');
        }

        $game = $match->games()->reorder('game_number', 'desc')->first();

        if (! $game) {
            return;
        }

        if ($game->points()->count() === 0) {
            $previousGame = $match->games()->where('game_number', '<', $game->game_number)->reorder('game_number', 'desc')->first();
            $game->delete();
            $game = $previousGame;
        }

        if (! $game) {
            return;
        }

        $lastPoint = $game->points()->reorder('point_number', 'desc')->first();

        if (! $lastPoint) {
            return;
        }

        DB::transaction(function () use ($match, $game, $lastPoint) {
            if ($lastPoint->scoring_player_id === $match->creator_player_id) {
                $game->creator_points = max(0, $game->creator_points - 1);
            } else {
                $game->opponent_points = max(0, $game->opponent_points - 1);
            }

            if ($game->isComplete()) {
                $game->winner_player_id = null;
                $game->completed_at = null;
            }

            $game->save();
            $lastPoint->delete();
        });
    }

    public function cancel(CasualMatch $match): void
    {
        if (in_array($match->status, ['completed', 'cancelled'], true)) {
            throw new RuntimeException('Este reto ya finalizó y no se puede cancelar.');
        }

        $match->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    public function forfeit(CasualMatch $match, int $winnerPlayerId): void
    {
        if (! in_array($winnerPlayerId, [$match->creator_player_id, $match->opponent_player_id], true)) {
            throw new RuntimeException('El ganador debe ser uno de los dos participantes.');
        }

        if (in_array($match->status, ['completed', 'cancelled'], true)) {
            throw new RuntimeException('Este reto ya finalizó.');
        }

        $loserPlayerId = $winnerPlayerId === $match->creator_player_id ? $match->opponent_player_id : $match->creator_player_id;

        $match->update([
            'winner_player_id' => $winnerPlayerId,
            'loser_player_id' => $loserPlayerId,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * ITTF service rule: serve alternates every 2 points, except at deuce (10-10) or during
     * expedite, when it alternates every point.
     */
    private function currentServer(CasualMatchGame $game, int $creatorId, int $opponentId): int
    {
        $firstServer = $game->first_server_player_id;
        $secondServer = $firstServer === $creatorId ? $opponentId : $creatorId;

        $totalPoints = $game->creator_points + $game->opponent_points;
        $deuce = $game->creator_points >= 10 && $game->opponent_points >= 10;

        if ($deuce || $this->isExpediteActive($game)) {
            return $totalPoints % 2 === 0 ? $firstServer : $secondServer;
        }

        return intdiv($totalPoints, 2) % 2 === 0 ? $firstServer : $secondServer;
    }

    private function isExpediteActive(CasualMatchGame $game): bool
    {
        if ($game->isComplete() || $game->started_at === null) {
            return false;
        }

        return $game->started_at->diffInSeconds(now()) >= self::EXPEDITE_THRESHOLD_MINUTES * 60;
    }

    private function expediteSecondsRemaining(CasualMatchGame $game): ?int
    {
        if ($game->isComplete() || $game->started_at === null) {
            return null;
        }

        $remaining = self::EXPEDITE_THRESHOLD_MINUTES * 60 - $game->started_at->diffInSeconds(now());

        return max(0, $remaining);
    }

    private function checkGameWinner(CasualMatchGame $game, int $pointsToWin, int $creatorId, int $opponentId): ?int
    {
        if ($game->creator_points >= $pointsToWin && $game->creator_points - $game->opponent_points >= 2) {
            return $creatorId;
        }

        if ($game->opponent_points >= $pointsToWin && $game->opponent_points - $game->creator_points >= 2) {
            return $opponentId;
        }

        return null;
    }

    private function startNextGame(CasualMatch $match, CasualMatchGame $finishedGame, int $creatorId, int $opponentId): void
    {
        $game1FirstServer = $match->games()->where('game_number', 1)->value('first_server_player_id');
        $nextGameNumber = $finishedGame->game_number + 1;

        $nextFirstServer = $nextGameNumber % 2 === 1
            ? $game1FirstServer
            : ($game1FirstServer === $creatorId ? $opponentId : $creatorId);

        CasualMatchGame::create([
            'casual_match_id' => $match->id,
            'game_number' => $nextGameNumber,
            'first_server_player_id' => $nextFirstServer,
            'started_at' => now(),
        ]);
    }

    public function currentServerFor(CasualMatchGame $game, CasualMatch $match): int
    {
        return $this->currentServer($game, $match->creator_player_id, $match->opponent_player_id);
    }

    public function expediteStateFor(CasualMatchGame $game): array
    {
        return [
            'active' => $this->isExpediteActive($game),
            'seconds_remaining' => $this->expediteSecondsRemaining($game),
        ];
    }
}
