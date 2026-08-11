<?php

namespace Tests\Feature;

use App\Models\CasualMatch;
use App\Models\Player;
use App\Models\User;
use App\Services\Ratings\EloRatingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasualMatchWagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_creating_a_ranked_reto_can_set_a_wager(): void
    {
        $creator = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'ranked',
            'best_of' => 5,
            'points_to_win' => 11,
            'wager_points' => 25,
        ]);

        $match = CasualMatch::where('creator_player_id', $creator->player->id)->latest()->firstOrFail();

        $this->assertSame(25, $match->wager_points);
    }

    public function test_a_friendly_reto_ignores_a_submitted_wager(): void
    {
        $creator = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'friendly',
            'best_of' => 5,
            'points_to_win' => 11,
            'wager_points' => 25,
        ]);

        $match = CasualMatch::where('creator_player_id', $creator->player->id)->latest()->firstOrFail();

        $this->assertNull($match->wager_points);
    }

    public function test_wager_amount_must_be_within_bounds(): void
    {
        $creator = User::factory()->create();

        $tooLow = $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'ranked',
            'best_of' => 5,
            'points_to_win' => 11,
            'wager_points' => 0,
        ]);
        $tooLow->assertSessionHasErrors('wager_points');

        $tooHigh = $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'ranked',
            'best_of' => 5,
            'points_to_win' => 11,
            'wager_points' => 501,
        ]);
        $tooHigh->assertSessionHasErrors('wager_points');
    }

    public function test_joining_a_wagered_reto_requires_explicit_acceptance(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'ranked',
            'best_of' => 5,
            'points_to_win' => 11,
            'wager_points' => 25,
        ]);
        $match = CasualMatch::where('creator_player_id', $creator->player->id)->latest()->firstOrFail();

        // Joining without accepting the wager must not attach the opponent.
        $response = $this->actingAs($opponent)->post(route('games.join'), ['code' => $match->code]);
        $response->assertSessionHas('pendingWager', ['code' => $match->code, 'wager_points' => 25]);

        $match->refresh();
        $this->assertSame('waiting', $match->status);
        $this->assertNull($match->opponent_player_id);

        // Accepting the wager completes the join.
        $this->actingAs($opponent)->post(route('games.join'), ['code' => $match->code, 'accept_wager' => true]);

        $match->refresh();
        $this->assertSame('ready', $match->status);
        $this->assertSame($opponent->player->id, $match->opponent_player_id);
    }

    public function test_joining_a_reto_without_a_wager_does_not_require_acceptance(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'ranked',
            'best_of' => 5,
            'points_to_win' => 11,
        ]);
        $match = CasualMatch::where('creator_player_id', $creator->player->id)->latest()->firstOrFail();

        $this->actingAs($opponent)->post(route('games.join'), ['code' => $match->code]);

        $match->refresh();
        $this->assertSame('ready', $match->status);
    }

    public function test_the_wager_transfers_on_top_of_the_normal_elo_change(): void
    {
        $winner = Player::create(['user_id' => User::factory()->create()->id, 'rating_current' => 1000]);
        $loser = Player::create(['user_id' => User::factory()->create()->id, 'rating_current' => 1000]);

        $match = CasualMatch::create([
            'code' => 'WAGER01',
            'match_type' => 'ranked',
            'status' => 'completed',
            'best_of' => 5,
            'points_to_win' => 11,
            'wager_points' => 15,
            'creator_player_id' => $winner->id,
            'opponent_player_id' => $loser->id,
            'winner_player_id' => $winner->id,
            'loser_player_id' => $loser->id,
        ]);

        app(EloRatingService::class)->applyCasualMatchResult($match, $winner, $loser);

        // Two fresh 1000-rated players (K=40, expected score 0.5 each) gain/lose 20 from
        // the normal ELO swing alone, plus the 15-point wager on top.
        $this->assertSame(1035, $winner->fresh()->rating_current);
        $this->assertSame(965, $loser->fresh()->rating_current);
    }

    public function test_a_full_wagered_match_played_over_http_applies_the_wager(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'ranked',
            'best_of' => 5,
            'points_to_win' => 11,
            'wager_points' => 15,
        ]);
        $match = CasualMatch::where('creator_player_id', $creator->player->id)->latest()->firstOrFail();

        $this->actingAs($opponent)->post(route('games.join'), ['code' => $match->code, 'accept_wager' => true]);

        $creatorPlayerId = $creator->player->id;
        $this->actingAs($creator)->post(route('games.start', $match->code), ['first_server_entrant_id' => $creatorPlayerId]);

        for ($game = 1; $game <= 3; $game++) {
            for ($point = 1; $point <= 11; $point++) {
                $this->actingAs($creator)->post(route('games.point', $match->code), ['entrant_id' => $creatorPlayerId]);
            }
        }

        $this->assertSame(1035, $creator->player->fresh()->rating_current);
        $this->assertSame(965, $opponent->player->fresh()->rating_current);
    }
}
