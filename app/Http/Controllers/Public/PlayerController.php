<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Player;
use App\Services\Scouting\ScoutingReportService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function __construct(private readonly ScoutingReportService $scoutingReportService) {}

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

        $ratedMatches = $player->ratingHistory()->with('opponent.user')->get();

        $ratingHistory = $ratedMatches->map(fn ($row) => ['rating' => $row->rating_after, 'date' => $row->created_at->toDateString()]);

        $recentForm = $ratedMatches->sortByDesc('created_at')->take(10)->reverse()->values()
            ->map(fn ($row) => [
                'won' => $row->rating_after >= $row->rating_before,
                'opponent' => $row->opponent?->user?->name,
                'date' => $row->created_at->toDateString(),
                'delta' => $row->rating_after - $row->rating_before,
            ]);

        $monthlyForm = $ratedMatches
            ->groupBy(fn ($row) => $row->created_at->format('Y-m'))
            ->map(fn ($rows, $month) => [
                'month' => $month,
                'wins' => $rows->filter(fn ($r) => $r->rating_after >= $r->rating_before)->count(),
                'losses' => $rows->filter(fn ($r) => $r->rating_after < $r->rating_before)->count(),
            ])
            ->sortKeys()
            ->values()
            ->take(-6);

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
            'recentForm' => $recentForm,
            'monthlyForm' => $monthlyForm,
            'scoutingReport' => $this->scoutingReportService->forPlayer($player),
            'registrations' => $registrations,
            'levelName' => $level?->name,
            'currentLevelXp' => $level?->xp_required ?? 0,
            'nextLevelXp' => $nextLevel?->xp_required,
            'isFollowing' => $viewer ? $viewer->isFollowing($player) : false,
            'isOwnProfile' => $viewer?->id === $player->id,
        ]);
    }

    public function followers(Request $request, Player $player): Response
    {
        return $this->renderConnections($request, $player, $player->followers(), 'followers');
    }

    public function followingList(Request $request, Player $player): Response
    {
        return $this->renderConnections($request, $player, $player->following(), 'following');
    }

    private function renderConnections(Request $request, Player $player, BelongsToMany $relation, string $type): Response
    {
        $players = $relation->with('user')->latest('player_follows.created_at')->paginate(30)->withQueryString();

        $viewer = $request->user()?->player;
        $followingIds = $viewer ? $viewer->following()->pluck('players.id')->all() : [];

        $players->getCollection()->each(
            fn (Player $p) => $p->setAttribute('is_following', in_array($p->id, $followingIds, true))
        );

        return Inertia::render('public/players/connections', [
            'owner' => $player->load('user'),
            'type' => $type,
            'players' => $players,
            'viewerPlayerId' => $viewer?->id,
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
