<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerFollowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_user_can_follow_and_unfollow_another_player(): void
    {
        $user = User::factory()->create();
        $target = Player::create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($user)->post(route('public.players.follow', $target))->assertRedirect();

        $viewerPlayer = Player::where('user_id', $user->id)->first();
        $this->assertNotNull($viewerPlayer);
        $this->assertTrue($viewerPlayer->isFollowing($target));
        $this->assertSame(1, $target->fresh()->followers()->count());

        $this->actingAs($user)->delete(route('public.players.unfollow', $target))->assertRedirect();

        $this->assertFalse($viewerPlayer->fresh()->isFollowing($target));
        $this->assertSame(0, $target->fresh()->followers()->count());
    }

    public function test_following_the_same_player_twice_does_not_duplicate_the_row(): void
    {
        $user = User::factory()->create();
        $target = Player::create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($user)->post(route('public.players.follow', $target));
        $this->actingAs($user)->post(route('public.players.follow', $target));

        $this->assertSame(1, $target->fresh()->followers()->count());
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();
        $player = Player::create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('public.players.follow', $player));

        $response->assertSessionHas('error');
        $this->assertSame(0, $player->fresh()->followers()->count());
    }

    public function test_player_profile_reports_follower_counts_and_whether_the_viewer_follows_them(): void
    {
        $viewerUser = User::factory()->create();
        $viewerPlayer = Player::create(['user_id' => $viewerUser->id]);

        $target = Player::create(['user_id' => User::factory()->create()->id]);
        $viewerPlayer->following()->attach($target->id);

        $response = $this->actingAs($viewerUser)->get(route('public.players.show', $target));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('isFollowing', true)
            ->where('isOwnProfile', false)
            ->where('player.followers_count', 1));
    }

    public function test_guests_can_view_a_profile_without_following_state(): void
    {
        $target = Player::create(['user_id' => User::factory()->create()->id]);

        $response = $this->get(route('public.players.show', $target));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('isFollowing', false));
    }
}
