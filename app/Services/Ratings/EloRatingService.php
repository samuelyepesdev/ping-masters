<?php

namespace App\Services\Ratings;

use App\Models\Player;
use App\Models\PlayerRatingHistory;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

class EloRatingService
{
    public function kFactor(Player $player): int
    {
        $eliteThreshold = config('rating.elite_rating_threshold');
        $eliteMinMatches = config('rating.elite_min_matches');

        if ($player->rating_current >= $eliteThreshold && $player->matches_played_rated >= $eliteMinMatches) {
            return config('rating.k_factor_elite');
        }

        if ($player->matches_played_rated < config('rating.k_factor_new_player_max_matches')) {
            return config('rating.k_factor_new_player');
        }

        if ($player->matches_played_rated < config('rating.k_factor_intermediate_max_matches')) {
            return config('rating.k_factor_intermediate');
        }

        return config('rating.k_factor_standard');
    }

    public function expectedScore(int $ratingA, int $ratingB): float
    {
        return 1 / (1 + 10 ** (($ratingB - $ratingA) / 400));
    }

    /**
     * Apply a completed (non-walkover, non-bye) match result to both players' ELO ratings.
     */
    public function applyMatchResult(TournamentMatch $match, Player $winner, Player $loser): void
    {
        DB::transaction(function () use ($match, $winner, $loser) {
            $winnerK = $this->kFactor($winner);
            $loserK = $this->kFactor($loser);

            $winnerExpected = $this->expectedScore($winner->rating_current, $loser->rating_current);
            $loserExpected = $this->expectedScore($loser->rating_current, $winner->rating_current);

            $winnerRatingBefore = $winner->rating_current;
            $loserRatingBefore = $loser->rating_current;

            $winnerRatingAfter = (int) round($winnerRatingBefore + $winnerK * (1 - $winnerExpected));
            $loserRatingAfter = max(0, (int) round($loserRatingBefore + $loserK * (0 - $loserExpected)));

            $eliteThreshold = config('rating.elite_rating_threshold');
            $eliteMinMatches = config('rating.elite_min_matches');

            $winner->rating_current = $winnerRatingAfter;
            $winner->matches_played_rated++;
            $winner->matches_played++;
            $winner->matches_won++;
            $winner->is_elite = $winner->rating_current >= $eliteThreshold && $winner->matches_played_rated >= $eliteMinMatches;
            $winner->save();

            $loser->rating_current = $loserRatingAfter;
            $loser->matches_played_rated++;
            $loser->matches_played++;
            $loser->is_elite = $loser->rating_current >= $eliteThreshold && $loser->matches_played_rated >= $eliteMinMatches;
            $loser->save();

            PlayerRatingHistory::create([
                'player_id' => $winner->id,
                'match_id' => $match->id,
                'opponent_player_id' => $loser->id,
                'rating_before' => $winnerRatingBefore,
                'rating_after' => $winnerRatingAfter,
            ]);

            PlayerRatingHistory::create([
                'player_id' => $loser->id,
                'match_id' => $match->id,
                'opponent_player_id' => $winner->id,
                'rating_before' => $loserRatingBefore,
                'rating_after' => $loserRatingAfter,
            ]);
        });
    }
}
