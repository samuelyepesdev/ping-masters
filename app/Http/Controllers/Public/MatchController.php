<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TournamentMatch;
use App\Services\Scoring\MatchStatePresenter;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    public function show(TournamentMatch $match, MatchStatePresenter $presenter): Response
    {
        $match->load('division.tournament');

        return Inertia::render('public/matches/show', [
            'tournament' => $match->division->tournament,
            'division' => $match->division,
            'match' => $presenter->present($match),
        ]);
    }
}
