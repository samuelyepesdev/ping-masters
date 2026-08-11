<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Season;
use App\Models\SeasonStanding;
use Inertia\Inertia;
use Inertia\Response;

class SeasonController extends Controller
{
    public function index(): Response
    {
        $seasons = Season::withCount('standings')
            ->orderByDesc('started_at')
            ->get();

        return Inertia::render('public/seasons/index', [
            'seasons' => $seasons,
        ]);
    }

    public function show(Season $season): Response
    {
        $standings = $season->isCurrent()
            ? $this->liveStandings()
            : $this->frozenStandings($season);

        return Inertia::render('public/seasons/show', [
            'season' => $season,
            'standings' => $standings,
        ]);
    }

    private function liveStandings(): array
    {
        return Player::with(['user', 'club'])
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->where('matches_played_rated', '>', 0)
            ->orderByDesc('rating_current')
            ->get()
            ->values()
            ->map(fn (Player $player, int $index) => [
                'rank' => $index + 1,
                'player_id' => $player->id,
                'name' => $player->user?->name,
                'avatar' => $player->user?->avatar,
                'club' => $player->club?->name,
                'rating' => $player->rating_current,
                'matches_played' => $player->matches_played_rated,
            ])
            ->all();
    }

    private function frozenStandings(Season $season): array
    {
        return $season->standings()
            ->with(['player.user', 'player.club'])
            ->get()
            ->map(fn (SeasonStanding $standing) => [
                'rank' => $standing->rank,
                'player_id' => $standing->player_id,
                'name' => $standing->player?->user?->name,
                'avatar' => $standing->player?->user?->avatar,
                'club' => $standing->player?->club?->name,
                'rating' => $standing->final_rating,
                'matches_played' => $standing->matches_played,
            ])
            ->all();
    }
}
