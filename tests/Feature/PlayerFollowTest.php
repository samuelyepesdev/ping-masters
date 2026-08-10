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

    public function test_followers_and_following_pages_list_the_right_players(): void
    {
        $target = Player::create(['user_id' => User::factory()->create()->id]);

        $followerUser = User::factory()->create();
        $followerPlayer = Player::create(['user_id' => $followerUser->id]);
        $followerPlayer->following()->attach($target->id);

        $followedPlayer = Player::create(['user_id' => User::factory()->create()->id]);
        $target->following()->attach($followedPlayer->id);

        $followersResponse = $this->get(route('public.players.followers', $target));
        $followersResponse->assertOk();
        $followersResponse->assertInertia(fn ($page) => $page
            ->where('type', 'followers')
            ->has('players.data', 1)
            ->where('players.data.0.id', $followerPlayer->id));

        $followingResponse = $this->get(route('public.players.following', $target));
        $followingResponse->assertOk();
        $followingResponse->assertInertia(fn ($page) => $page
            ->where('type', 'following')
            ->has('players.data', 1)
            ->where('players.data.0.id', $followedPlayer->id));
    }

    public function test_connections_page_flags_which_players_the_viewer_already_follows(): void
    {
        $viewerUser = User::factory()->create();
        $viewerPlayer = Player::create(['user_id' => $viewerUser->id]);

        $target = Player::create(['user_id' => User::factory()->create()->id]);
        $alreadyFollowed = Player::create(['user_id' => User::factory()->create()->id]);
        $notFollowed = Player::create(['user_id' => User::factory()->create()->id]);

        $target->following()->attach([$alreadyFollowed->id, $notFollowed->id]);
        $viewerPlayer->following()->attach($alreadyFollowed->id);

        $response = $this->actingAs($viewerUser)->get(route('public.players.following', $target));

        $response->assertOk();
        $response->assertInertia(function ($page) use ($viewerPlayer, $alreadyFollowed, $notFollowed) {
            $page->where('viewerPlayerId', $viewerPlayer->id);

            $byId = collect($page->toArray()['props']['players']['data'])->keyBy('id');

            $this->assertTrue($byId[$alreadyFollowed->id]['is_following']);
            $this->assertFalse($byId[$notFollowed->id]['is_following']);
        });
    }
}
