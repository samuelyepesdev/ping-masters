<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileMeRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_it_creates_a_player_profile_on_first_visit_and_redirects_to_it(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseMissing('players', ['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('profile.me'));

        $player = Player::where('user_id', $user->id)->firstOrFail();
        $response->assertRedirect(route('public.players.show', $player->id));
        $this->assertTrue($user->fresh()->hasRole('player'));
    }

    public function test_it_reuses_the_existing_player_profile_on_subsequent_visits(): void
    {
        $user = User::factory()->create();
        $existing = Player::create(['user_id' => $user->id, 'rating_current' => 1234]);

        $response = $this->actingAs($user)->get(route('profile.me'));

        $response->assertRedirect(route('public.players.show', $existing->id));
        $this->assertSame(1, Player::where('user_id', $user->id)->count());
    }
}
