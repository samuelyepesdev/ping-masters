<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Season;
use App\Services\Seasons\SeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeasonController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $current = Season::current();

        $pastSeasons = Season::whereNotNull('ended_at')
            ->withCount('standings')
            ->orderByDesc('started_at')
            ->get();

        return Inertia::render('admin/seasons/index', [
            'current' => $current,
            'activePlayers' => Player::where('matches_played_rated', '>', 0)->count(),
            'pastSeasons' => $pastSeasons,
        ]);
    }

    public function reset(Request $request, SeasonService $seasons): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $newSeason = $seasons->startNewSeason($validated['name'] ?? null);

        return back()->with(
            'success',
            "Temporada anterior cerrada y guardada en el histórico. Empezó \"{$newSeason->name}\" con el ranking reiniciado para todos."
        );
    }
}
