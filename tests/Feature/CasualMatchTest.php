<?php

namespace Tests\Feature;

use App\Models\CasualMatch;
use App\Models\Player;
use App\Models\PlayerRatingHistory;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasualMatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function playFullMatch(User $creator, User $opponent, string $matchType): CasualMatch
    {
        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => $matchType,
            'best_of' => 5,
            'points_to_win' => 11,
        ]);

        $match = CasualMatch::where('creator_player_id', $creator->player->id)->latest()->firstOrFail();

        $this->actingAs($opponent)->post(route('games.join'), ['code' => $match->code]);
        $match->refresh();

        $creatorPlayerId = $creator->player->id;

        $this->actingAs($creator)->post(route('games.start', $match->code), [
            'first_server_entrant_id' => $creatorPlayerId,
        ]);

        // Straight 3-0 win (11-0, 11-0, 11-0) for the creator.
        for ($game = 1; $game <= 3; $game++) {
            for ($point = 1; $point <= 11; $point++) {
                $this->actingAs($creator)->post(route('games.point', $match->code), [
                    'entrant_id' => $creatorPlayerId,
                ]);
            }
        }

        return $match->fresh();
    }

    public function test_two_players_can_create_join_and_play_a_ranked_casual_match_and_elo_and_xp_are_applied(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();

        $match = $this->playFullMatch($creator, $opponent, 'ranked');

        $this->assertSame('completed', $match->status);
        $this->assertSame('3-0', $match->score_summary);
        $this->assertSame($creator->player->id, $match->winner_player_id);
        $this->assertSame($opponent->player->id, $match->loser_player_id);

        $creatorPlayer = Player::find($creator->player->id);
        $opponentPlayer = Player::find($opponent->player->id);

        $this->assertGreaterThan(1000, $creatorPlayer->rating_current);
        $this->assertLessThan(1000, $opponentPlayer->rating_current);

        $this->assertDatabaseHas('player_ratings_history', [
            'player_id' => $creatorPlayer->id,
            'casual_match_id' => $match->id,
        ]);
        $this->assertDatabaseHas('player_ratings_history', [
            'player_id' => $opponentPlayer->id,
            'casual_match_id' => $match->id,
        ]);

        $this->assertDatabaseHas('player_xp_events', [
            'player_id' => $creatorPlayer->id,
            'type' => 'casual_match_won',
            'casual_match_id' => $match->id,
        ]);
        $this->assertDatabaseHas('player_xp_events', [
            'player_id' => $opponentPlayer->id,
            'type' => 'casual_match_played',
            'casual_match_id' => $match->id,
        ]);
    }

    public function test_friendly_casual_match_grants_xp_but_never_touches_elo(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();

        $match = $this->playFullMatch($creator, $opponent, 'friendly');

        $this->assertSame('completed', $match->status);

        $creatorPlayer = Player::find($creator->player->id);
        $opponentPlayer = Player::find($opponent->player->id);

        $this->assertSame(1000, $creatorPlayer->rating_current);
        $this->assertSame(1000, $opponentPlayer->rating_current);

        $this->assertSame(0, PlayerRatingHistory::where('casual_match_id', $match->id)->count());

        $this->assertDatabaseHas('player_xp_events', [
            'player_id' => $creatorPlayer->id,
            'type' => 'casual_match_won',
            'casual_match_id' => $match->id,
        ]);
    }

    public function test_a_player_cannot_join_their_own_match_or_join_twice(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'friendly',
            'best_of' => 5,
            'points_to_win' => 11,
        ]);

        $match = CasualMatch::firstOrFail();

        $this->actingAs($creator)->post(route('games.join'), ['code' => $match->code])
            ->assertSessionHasErrors('code');

        $this->actingAs($opponent)->post(route('games.join'), ['code' => $match->code])
            ->assertRedirect(route('games.show', $match->code));

        $this->actingAs($stranger)->post(route('games.join'), ['code' => $match->code])
            ->assertSessionHasErrors('code');
    }

    public function test_either_player_can_cancel_a_reto_before_it_finishes(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'friendly',
            'best_of' => 5,
            'points_to_win' => 11,
        ]);
        $match = CasualMatch::firstOrFail();
        $this->actingAs($opponent)->post(route('games.join'), ['code' => $match->code]);

        $this->actingAs($opponent)->post(route('games.cancel', $match->code))
            ->assertRedirect(route('games.index'));

        $match->refresh();
        $this->assertSame('cancelled', $match->status);
        $this->assertNotNull($match->completed_at);
        $this->assertNull($match->winner_player_id);

        $this->assertSame(0, PlayerRatingHistory::where('casual_match_id', $match->id)->count());
    }

    public function test_a_cancelled_reto_cannot_be_cancelled_again(): void
    {
        $creator = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'friendly',
            'best_of' => 5,
            'points_to_win' => 11,
        ]);
        $match = CasualMatch::firstOrFail();

        $this->actingAs($creator)->post(route('games.cancel', $match->code));
        $this->actingAs($creator)->post(route('games.cancel', $match->code))
            ->assertSessionHas('error');
    }

    public function test_cancelled_retos_are_excluded_from_the_lobby_history(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'friendly',
            'best_of' => 5,
            'points_to_win' => 11,
        ]);
        $cancelled = CasualMatch::firstOrFail();
        $this->actingAs($opponent)->post(route('games.join'), ['code' => $cancelled->code]);
        $this->actingAs($creator)->post(route('games.cancel', $cancelled->code));

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'friendly',
            'best_of' => 5,
            'points_to_win' => 11,
        ]);
        $active = CasualMatch::where('id', '!=', $cancelled->id)->firstOrFail();

        $response = $this->actingAs($creator)->get(route('games.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('games/index')
            ->has('matches', 1)
            ->where('matches.0.code', $active->code)
        );
    }

    public function test_a_player_who_is_not_part_of_the_match_cannot_score_it(): void
    {
        $creator = User::factory()->create();
        $opponent = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($creator)->post(route('games.store'), [
            'match_type' => 'friendly',
            'best_of' => 5,
            'points_to_win' => 11,
        ]);
        $match = CasualMatch::firstOrFail();
        $this->actingAs($opponent)->post(route('games.join'), ['code' => $match->code]);

        $this->actingAs($stranger)->get(route('games.show', $match->code))->assertForbidden();
        $this->actingAs($stranger)->post(route('games.start', $match->code), [
            'first_server_entrant_id' => $creator->player->id,
        ])->assertForbidden();
    }
}
