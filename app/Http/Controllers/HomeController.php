<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $tournaments = Tournament::whereIn('status', ['registration_open', 'in_progress'])
            ->withCount(['divisions', 'registrations'])
            ->orderByDesc('start_date')
            ->limit(3)
            ->get();

        $activePlayers = Player::whereHas('user', fn ($q) => $q->whereNull('deleted_at'));

        $topPlayers = (clone $activePlayers)->with('user')
            ->orderByDesc('rating_current')
            ->limit(5)
            ->get();

        return Inertia::render('home', [
            'tournaments' => $tournaments,
            'topPlayers' => $topPlayers,
            'stats' => [
                'tournaments' => Tournament::count(),
                'players' => (clone $activePlayers)->count(),
                'matches_played' => (int) floor((clone $activePlayers)->sum('matches_played') / 2),
            ],
        ]);
    }
}
