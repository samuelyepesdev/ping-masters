<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function me(Request $request): RedirectResponse
    {
        $player = $this->playerFor($request);

        return redirect()->route('public.players.show', $player->id);
    }

    public function ranking(Request $request): Response
    {
        $query = Player::with(['user', 'club'])
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->orderByDesc('rating_current');

        if ($search = $request->string('search')->trim()->value()) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $players = $query->paginate(25)->withQueryString();

        return Inertia::render('public/players/ranking', [
            'players' => $players,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Request $request, Player $player): Response
    {
        $player->load(['user', 'club', 'achievements']);
        $player->loadCount(['followers', 'following']);

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

        $viewer = $request->user()?->player;

        return Inertia::render('public/players/show', [
            'player' => $player,
            'ratingHistory' => $ratingHistory,
            'registrations' => $registrations,
            'levelName' => $level?->name,
            'currentLevelXp' => $level?->xp_required ?? 0,
            'nextLevelXp' => $nextLevel?->xp_required,
            'isFollowing' => $viewer ? $viewer->isFollowing($player) : false,
            'isOwnProfile' => $viewer?->id === $player->id,
        ]);
    }

    public function follow(Request $request, Player $player): RedirectResponse
    {
        $viewer = $this->playerFor($request);

        if ($viewer->id === $player->id) {
            return back()->with('error', 'No puedes seguirte a ti mismo.');
        }

        $viewer->following()->syncWithoutDetaching([$player->id]);

        return back();
    }

    public function unfollow(Request $request, Player $player): RedirectResponse
    {
        $viewer = $this->playerFor($request);

        $viewer->following()->detach($player->id);

        return back();
    }

    private function playerFor(Request $request): Player
    {
        $user = $request->user();

        $player = Player::firstOrCreate(['user_id' => $user->id], ['club_id' => $user->club_id]);

        if (! $user->hasRole('player')) {
            $user->assignRole('player');
        }

        return $player;
    }
}
