<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\PlayerRatingHistory;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\Ratings\EloRatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloRatingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function player(int $rating, int $matchesPlayedRated = 0): Player
    {
        $user = User::factory()->create();

        return Player::create([
            'user_id' => $user->id,
            'rating_current' => $rating,
            'matches_played_rated' => $matchesPlayedRated,
        ]);
    }

    private function dummyMatch(): TournamentMatch
    {
        $organizer = User::factory()->create();
        $tournament = Tournament::create([
            'name' => 'Copa ELO',
            'slug' => 'copa-elo-'.uniqid(),
            'created_by' => $organizer->id,
            'status' => 'in_progress',
            'start_date' => now(),
            'end_date' => now()->addDay(),
        ]);
        $division = $tournament->divisions()->create([
            'name' => 'Individual',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'single_elimination',
            'best_of' => 5,
            'points_to_win' => 11,
        ]);

        return TournamentMatch::create(['tournament_division_id' => $division->id, 'match_number' => 1, 'status' => 'completed']);
    }

    public function test_equal_rated_new_players_exchange_the_full_k_factor_gap(): void
    {
        $elo = app(EloRatingService::class);

        $winner = $this->player(1000);
        $loser = $this->player(1000);
        $match = $this->dummyMatch();

        $elo->applyMatchResult($match, $winner, $loser);

        // K=40 for new players, expected score 0.5 each way -> +/- 20.
        $this->assertSame(1020, $winner->fresh()->rating_current);
        $this->assertSame(980, $loser->fresh()->rating_current);
    }

    public function test_an_underdog_gains_more_than_a_favorite_would_for_the_same_win(): void
    {
        $elo = app(EloRatingService::class);

        $underdog = $this->player(1000);
        $favorite = $this->player(1400);
        $match = $this->dummyMatch();

        $elo->applyMatchResult($match, $underdog, $favorite);

        $underdogGain = $underdog->fresh()->rating_current - 1000;

        $matchingdog = $this->player(1000);
        $matchingFavorite = $this->player(1000);
        $match2 = $this->dummyMatch();
        $elo->applyMatchResult($match2, $matchingdog, $matchingFavorite);
        $evenGain = $matchingdog->fresh()->rating_current - 1000;

        $this->assertGreaterThan($evenGain, $underdogGain);
    }

    public function test_k_factor_decreases_as_players_gain_experience(): void
    {
        $elo = app(EloRatingService::class);

        $this->assertSame(40, $elo->kFactor($this->player(1000, 0)));
        $this->assertSame(32, $elo->kFactor($this->player(1000, 30)));
        $this->assertSame(24, $elo->kFactor($this->player(1000, 60)));
        $this->assertSame(16, $elo->kFactor($this->player(2200, 100)));
        // High rating alone isn't enough for the elite tier without enough rated matches.
        $this->assertSame(24, $elo->kFactor($this->player(2200, 61)));
    }

    public function test_rating_history_rows_are_recorded_for_both_players(): void
    {
        $elo = app(EloRatingService::class);

        $winner = $this->player(1000);
        $loser = $this->player(1000);
        $match = $this->dummyMatch();

        $elo->applyMatchResult($match, $winner, $loser);

        $this->assertDatabaseHas('player_ratings_history', [
            'player_id' => $winner->id,
            'opponent_player_id' => $loser->id,
            'rating_before' => 1000,
            'rating_after' => 1020,
        ]);
        $this->assertDatabaseHas('player_ratings_history', [
            'player_id' => $loser->id,
            'opponent_player_id' => $winner->id,
            'rating_before' => 1000,
            'rating_after' => 980,
        ]);
        $this->assertSame(2, PlayerRatingHistory::count());
    }
}
