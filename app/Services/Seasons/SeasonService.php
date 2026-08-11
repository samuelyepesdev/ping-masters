<?php

namespace App\Services\Seasons;

use App\Models\Player;
use App\Models\Season;
use App\Models\SeasonStanding;
use Illuminate\Support\Facades\DB;

class SeasonService
{
    /**
     * Snapshot the current season's standings, then reset every player's rating so the
     * next season starts even. XP, levels, achievements, and lifetime match totals are
     * untouched — those track career progress, not a single season's ranking.
     */
    public function startNewSeason(?string $name = null): Season
    {
        return DB::transaction(function () use ($name) {
            $current = Season::current();

            $ranked = Player::where('matches_played_rated', '>', 0)
                ->orderByDesc('rating_current')
                ->get(['id', 'rating_current', 'matches_played_rated']);

            $ranked->each(function (Player $player, int $index) use ($current) {
                SeasonStanding::create([
                    'season_id' => $current->id,
                    'player_id' => $player->id,
                    'final_rating' => $player->rating_current,
                    'matches_played' => $player->matches_played_rated,
                    'rank' => $index + 1,
                ]);
            });

            $current->update(['ended_at' => now()]);

            Player::query()->update([
                'rating_current' => config('rating.starting_rating'),
                'rating_deviation' => config('rating.starting_rating_deviation'),
                'matches_played_rated' => 0,
                'is_elite' => false,
            ]);

            return Season::create([
                'name' => $name ?: 'Temporada '.(Season::count() + 1),
                'started_at' => now(),
                'ended_at' => null,
            ]);
        });
    }
}
