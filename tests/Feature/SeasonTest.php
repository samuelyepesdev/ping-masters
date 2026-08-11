<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Season;
use App\Models\User;
use App\Services\Seasons\SeasonService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_season_exists_by_default(): void
    {
        $season = Season::current();

        $this->assertSame('Temporada 1', $season->name);
        $this->assertTrue($season->isCurrent());
    }

    public function test_starting_a_new_season_snapshots_standings_and_resets_ratings(): void
    {
        $top = Player::create(['user_id' => User::factory()->create()->id, 'rating_current' => 1200, 'matches_played_rated' => 10, 'is_elite' => false]);
        $bottom = Player::create(['user_id' => User::factory()->create()->id, 'rating_current' => 900, 'matches_played_rated' => 5]);
        $inactive = Player::create(['user_id' => User::factory()->create()->id, 'rating_current' => 1000, 'matches_played_rated' => 0]);

        $oldSeason = Season::current();

        $newSeason = app(SeasonService::class)->startNewSeason('Temporada 2');

        $oldSeason->refresh();
        $this->assertNotNull($oldSeason->ended_at);
        $this->assertFalse($oldSeason->isCurrent());

        $this->assertSame('Temporada 2', $newSeason->name);
        $this->assertTrue($newSeason->isCurrent());
        $this->assertSame($newSeason->id, Season::current()->id);

        // Standings snapshot: only players who actually played rated matches, ranked by rating.
        $standings = $oldSeason->standings()->get();
        $this->assertCount(2, $standings);

        $first = $standings->firstWhere('player_id', $top->id);
        $this->assertSame(1, $first->rank);
        $this->assertSame(1200, $first->final_rating);
        $this->assertSame(10, $first->matches_played);

        $second = $standings->firstWhere('player_id', $bottom->id);
        $this->assertSame(2, $second->rank);
        $this->assertSame(900, $second->final_rating);

        $this->assertNull($standings->firstWhere('player_id', $inactive->id));

        // Ratings reset for everyone, including the inactive player.
        foreach ([$top, $bottom, $inactive] as $player) {
            $player->refresh();
            $this->assertSame(1000, $player->rating_current);
            $this->assertSame(350, $player->rating_deviation);
            $this->assertSame(0, $player->matches_played_rated);
            $this->assertFalse($player->is_elite);
        }
    }

    public function test_starting_a_new_season_without_a_name_auto_numbers_it(): void
    {
        $newSeason = app(SeasonService::class)->startNewSeason();

        $this->assertSame('Temporada 2', $newSeason->name);
    }

    public function test_only_super_admin_can_reset_the_season(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $this->actingAs($organizer)->post(route('admin.seasons.reset'))->assertForbidden();
        $this->assertSame('Temporada 1', Season::current()->name);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)->post(route('admin.seasons.reset'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Temporada 2', Season::current()->name);
    }

    public function test_public_season_index_and_show_pages_render(): void
    {
        $player = Player::create(['user_id' => User::factory()->create()->id, 'rating_current' => 1100, 'matches_played_rated' => 3]);
        $oldSeason = Season::current();
        $newSeason = app(SeasonService::class)->startNewSeason();

        $this->get(route('public.seasons.index'))->assertOk();

        $frozenResponse = $this->get(route('public.seasons.show', $oldSeason));
        $frozenResponse->assertOk();
        $frozenResponse->assertInertia(fn ($page) => $page
            ->where('season.id', $oldSeason->id)
            ->has('standings', 1)
            ->where('standings.0.player_id', $player->id)
            ->where('standings.0.rating', 1100));

        $liveResponse = $this->get(route('public.seasons.show', $newSeason));
        $liveResponse->assertOk();
        $liveResponse->assertInertia(fn ($page) => $page
            ->where('season.id', $newSeason->id)
            ->has('standings', 0));
    }
}
