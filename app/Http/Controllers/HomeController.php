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

        $topPlayers = Player::with('user')
            ->orderByDesc('rating_current')
            ->limit(5)
            ->get();

        return Inertia::render('home', [
            'tournaments' => $tournaments,
            'topPlayers' => $topPlayers,
            'stats' => [
                'tournaments' => Tournament::count(),
                'players' => Player::count(),
                'matches_played' => (int) floor(Player::sum('matches_played') / 2),
            ],
        ]);
    }
}
