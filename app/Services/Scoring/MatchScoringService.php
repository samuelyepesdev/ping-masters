<?php

namespace App\Services\Scoring;

use App\Models\MatchGame;
use App\Models\MatchPoint;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\Brackets\BracketAdvancementService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MatchScoringService
{
    public function __construct(
        private readonly ServeRotationCalculator $serveRotation,
        private readonly ExpediteService $expedite,
        private readonly BracketAdvancementService $advancement,
    ) {}

    public function startMatch(TournamentMatch $match, int $firstServerEntrantId): MatchGame
    {
        if ($match->status !== 'ready') {
            throw new RuntimeException('El partido no está listo para comenzar.');
        }

        if (! in_array($firstServerEntrantId, [$match->entrant1_id, $match->entrant2_id], true)) {
            throw new RuntimeException('El jugador que saca debe ser uno de los dos participantes.');
        }

        return DB::transaction(function () use ($match, $firstServerEntrantId) {
            $match->update(['status' => 'in_progress']);

            return MatchGame::create([
                'match_id' => $match->id,
                'game_number' => 1,
                'first_server_entrant_id' => $firstServerEntrantId,
                'started_at' => now(),
            ]);
        });
    }

    public function recordPoint(TournamentMatch $match, int $scoringEntrantId, ?User $recorder = null): MatchGame
    {
        if ($match->status !== 'in_progress') {
            throw new RuntimeException('El partido no está en curso.');
        }

        if (! in_array($scoringEntrantId, [$match->entrant1_id, $match->entrant2_id], true)) {
            throw new RuntimeException('El punto debe asignarse a uno de los dos participantes.');
        }

        $game = $match->currentGame();

        if (! $game) {
            throw new RuntimeException('No hay un juego activo para este partido.');
        }

        return DB::transaction(function () use ($match, $scoringEntrantId, $recorder, $game) {
            $entrant1Id = $match->entrant1_id;
            $entrant2Id = $match->entrant2_id;

            $server = $this->serveRotation->currentServer($game, $entrant1Id, $entrant2Id);
            $isExpedite = $this->expedite->isActive($game);

            if ($scoringEntrantId === $entrant1Id) {
                $game->entrant1_points++;
            } else {
                $game->entrant2_points++;
            }

            $pointNumber = $game->points()->count() + 1;

            MatchPoint::create([
                'match_id' => $match->id,
                'match_game_id' => $game->id,
                'point_number' => $pointNumber,
                'scoring_entrant_id' => $scoringEntrantId,
                'server_entrant_id' => $server,
                'entrant1_points_after' => $game->entrant1_points,
                'entrant2_points_after' => $game->entrant2_points,
                'was_expedite' => $isExpedite,
                'recorded_by' => $recorder?->id,
            ]);

            $division = $match->division;
            $gameWinnerId = $this->checkGameWinner($game, $division->points_to_win, $entrant1Id, $entrant2Id);

            if ($gameWinnerId === null) {
                $game->save();

                return $game->fresh();
            }

            $game->winner_entrant_id = $gameWinnerId;
            $game->completed_at = now();
            $game->save();

            $entrant1Games = $match->games()->where('winner_entrant_id', $entrant1Id)->count();
            $entrant2Games = $match->games()->where('winner_entrant_id', $entrant2Id)->count();
            $match->score_summary = "{$entrant1Games}-{$entrant2Games}";
            $match->save();

            $gamesToWinMatch = (int) ceil($division->best_of / 2);

            if ($entrant1Games >= $gamesToWinMatch || $entrant2Games >= $gamesToWinMatch) {
                $matchWinnerId = $entrant1Games > $entrant2Games ? $entrant1Id : $entrant2Id;
                $this->advancement->recordResult($match, $matchWinnerId);
            } else {
                $this->startNextGame($match, $game, $entrant1Id, $entrant2Id);
            }

            return $game->fresh();
        });
    }

    public function undoLastPoint(TournamentMatch $match): void
    {
        if (in_array($match->status, ['completed', 'walkover', 'cancelled'], true)) {
            throw new RuntimeException('No se puede deshacer: el partido ya finalizó.');
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
            if ($lastPoint->scoring_entrant_id === $match->entrant1_id) {
                $game->entrant1_points = max(0, $game->entrant1_points - 1);
            } else {
                $game->entrant2_points = max(0, $game->entrant2_points - 1);
            }

            if ($game->isComplete()) {
                $game->winner_entrant_id = null;
                $game->completed_at = null;
            }

            $game->save();
            $lastPoint->delete();
        });
    }

    public function recordWalkover(TournamentMatch $match, int $winnerEntrantId): void
    {
        if (! in_array($winnerEntrantId, [$match->entrant1_id, $match->entrant2_id], true)) {
            throw new RuntimeException('El ganador debe ser uno de los dos participantes.');
        }

        if (in_array($match->status, ['completed', 'walkover', 'cancelled'], true)) {
            throw new RuntimeException('Este partido ya finalizó.');
        }

        $this->advancement->recordWalkover($match, $winnerEntrantId);
    }

    private function checkGameWinner(MatchGame $game, int $pointsToWin, int $entrant1Id, int $entrant2Id): ?int
    {
        if ($game->entrant1_points >= $pointsToWin && $game->entrant1_points - $game->entrant2_points >= 2) {
            return $entrant1Id;
        }

        if ($game->entrant2_points >= $pointsToWin && $game->entrant2_points - $game->entrant1_points >= 2) {
            return $entrant2Id;
        }

        return null;
    }

    private function startNextGame(TournamentMatch $match, MatchGame $finishedGame, int $entrant1Id, int $entrant2Id): void
    {
        $game1FirstServer = $match->games()->where('game_number', 1)->value('first_server_entrant_id');
        $nextGameNumber = $finishedGame->game_number + 1;

        $nextFirstServer = $nextGameNumber % 2 === 1
            ? $game1FirstServer
            : ($game1FirstServer === $entrant1Id ? $entrant2Id : $entrant1Id);

        MatchGame::create([
            'match_id' => $match->id,
            'game_number' => $nextGameNumber,
            'first_server_entrant_id' => $nextFirstServer,
            'started_at' => now(),
        ]);
    }
}
