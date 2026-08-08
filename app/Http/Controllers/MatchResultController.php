<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Services\Brackets\BracketAdvancementService;
use App\Services\Progression\PlayerProgressionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class MatchResultController extends Controller
{
    public function update(
        Request $request,
        Tournament $tournament,
        TournamentDivision $division,
        TournamentMatch $match,
        BracketAdvancementService $advancement,
        PlayerProgressionService $progression,
    ): RedirectResponse {
        $this->authorize('update', $tournament);
        abort_if($division->tournament_id !== $tournament->id, 404);
        abort_if($match->tournament_division_id !== $division->id, 404);

        if ($match->status !== 'ready') {
            return back()->with('error', 'Este partido no está listo para registrar un resultado.');
        }

        $validated = $request->validate([
            'winner_entrant_id' => 'required|integer|in:'.$match->entrant1_id.','.$match->entrant2_id,
        ]);

        $advancement->recordResult($match, (int) $validated['winner_entrant_id']);
        $progression->applyForMatch($match->fresh());

        return back()->with('success', 'Resultado registrado.');
    }
}
