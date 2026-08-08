<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Services\Brackets\BracketGeneratorFactory;
use App\Services\Brackets\SwissGenerator;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class DrawController extends Controller
{
    public function store(Tournament $tournament, TournamentDivision $division, BracketGeneratorFactory $factory): RedirectResponse
    {
        $this->authorize('update', $tournament);
        abort_if($division->tournament_id !== $tournament->id, 404);

        if ($division->status !== 'pending_draw') {
            return back()->with('error', 'Esta categoría ya tiene un sorteo generado.');
        }

        try {
            $factory->make($division)->generate($division);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sorteo generado.');
    }

    public function nextSwissRound(Tournament $tournament, TournamentDivision $division, SwissGenerator $generator): RedirectResponse
    {
        $this->authorize('update', $tournament);
        abort_if($division->tournament_id !== $tournament->id, 404);
        abort_if($division->format !== 'swiss', 404);

        try {
            $generator->generateNextRound($division);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Siguiente ronda generada.');
    }
}
