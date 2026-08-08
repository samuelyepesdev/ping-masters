<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function ranking(Request $request): Response
    {
        $query = Player::with(['user', 'club'])->orderByDesc('rating_current');

        if ($search = $request->string('search')->trim()->value()) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $players = $query->paginate(25)->withQueryString();

        return Inertia::render('public/players/ranking', [
            'players' => $players,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Player $player): Response
    {
        $player->load(['user', 'club', 'achievements']);

        $ratingHistory = $player->ratingHistory()
            ->select('rating_after', 'created_at')
            ->get()
            ->map(fn ($row) => ['rating' => $row->rating_after, 'date' => $row->created_at->toDateString()]);

        $registrations = $player->tournamentRegistrations()
            ->with(['tournament', 'divisions.division'])
            ->orderByDesc('submitted_at')
            ->get();

        $level = Level::where('level_number', $player->level)->first();
        $nextLevel = Level::where('level_number', $player->level + 1)->first();

        return Inertia::render('public/players/show', [
            'player' => $player,
            'ratingHistory' => $ratingHistory,
            'registrations' => $registrations,
            'levelName' => $level?->name,
            'currentLevelXp' => $level?->xp_required ?? 0,
            'nextLevelXp' => $nextLevel?->xp_required,
        ]);
    }
}
