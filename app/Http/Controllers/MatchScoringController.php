<?php

namespace App\Http\Controllers;

use App\Events\MatchScoreUpdated;
use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Services\Progression\PlayerProgressionService;
use App\Services\Scoring\MatchScoringService;
use App\Services\Scoring\MatchStatePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class MatchScoringController extends Controller
{
    public function __construct(
        private readonly MatchStatePresenter $presenter,
        private readonly PlayerProgressionService $progression,
    ) {}

    public function show(Tournament $tournament, TournamentDivision $division, TournamentMatch $match): Response
    {
        $this->authorize('score', $match);
        $this->assertBelongs($tournament, $division, $match);

        return Inertia::render('scoring/console', [
            'tournament' => $tournament,
            'division' => $division,
            'match' => $this->presenter->present($match),
        ]);
    }

    public function start(
        Request $request,
        Tournament $tournament,
        TournamentDivision $division,
        TournamentMatch $match,
        MatchScoringService $scoring,
    ): RedirectResponse {
        $this->authorize('score', $match);
        $this->assertBelongs($tournament, $division, $match);

        $validated = $request->validate([
            'first_server_entrant_id' => 'required|integer|in:'.$match->entrant1_id.','.$match->entrant2_id,
        ]);

        try {
            $scoring->startMatch($match, (int) $validated['first_server_entrant_id']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->broadcastState($match->fresh());

        return back();
    }

    public function point(
        Request $request,
        Tournament $tournament,
        TournamentDivision $division,
        TournamentMatch $match,
        MatchScoringService $scoring,
    ): RedirectResponse {
        $this->authorize('score', $match);
        $this->assertBelongs($tournament, $division, $match);

        $validated = $request->validate([
            'entrant_id' => 'required|integer|in:'.$match->entrant1_id.','.$match->entrant2_id,
        ]);

        try {
            $scoring->recordPoint($match, (int) $validated['entrant_id'], $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $fresh = $match->fresh();

        if ($fresh->status === 'completed') {
            $this->progression->applyForMatch($fresh);
        }

        $this->broadcastState($fresh);

        return back();
    }

    public function undo(
        Tournament $tournament,
        TournamentDivision $division,
        TournamentMatch $match,
        MatchScoringService $scoring,
    ): RedirectResponse {
        $this->authorize('score', $match);
        $this->assertBelongs($tournament, $division, $match);

        try {
            $scoring->undoLastPoint($match);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->broadcastState($match->fresh());

        return back();
    }

    public function walkover(
        Request $request,
        Tournament $tournament,
        TournamentDivision $division,
        TournamentMatch $match,
        MatchScoringService $scoring,
    ): RedirectResponse {
        $this->authorize('score', $match);
        $this->assertBelongs($tournament, $division, $match);

        $validated = $request->validate([
            'winner_entrant_id' => 'required|integer|in:'.$match->entrant1_id.','.$match->entrant2_id,
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $scoring->recordWalkover($match, (int) $validated['winner_entrant_id']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->broadcastState($match->fresh());

        return back();
    }

    public function assignReferee(
        Request $request,
        Tournament $tournament,
        TournamentDivision $division,
        TournamentMatch $match,
    ): RedirectResponse {
        $this->authorize('assignReferee', $match);
        $this->assertBelongs($tournament, $division, $match);

        $validated = $request->validate([
            'referee_id' => 'nullable|integer|exists:users,id',
        ]);

        $match->update(['referee_id' => $validated['referee_id'] ?? null]);

        return back()->with('success', 'Árbitro actualizado.');
    }

    private function assertBelongs(Tournament $tournament, TournamentDivision $division, TournamentMatch $match): void
    {
        abort_if($division->tournament_id !== $tournament->id, 404);
        abort_if($match->tournament_division_id !== $division->id, 404);
    }

    private function broadcastState(TournamentMatch $match): void
    {
        // Scoring must keep working even if the Reverb server is unreachable — live
        // spectator updates are a nice-to-have, not a reason to fail the referee's action.
        try {
            broadcast(new MatchScoreUpdated($match->id, $this->presenter->present($match)));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
