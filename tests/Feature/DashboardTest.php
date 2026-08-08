<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertOk();
    }

    public function test_super_admin_sees_platform_wide_stats(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');
        Tournament::create([
            'name' => 'Copa Panel',
            'slug' => 'copa-panel',
            'created_by' => $organizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('admin.tournaments_total', 1)
            ->has('organizer')
        );
    }

    public function test_organizer_sees_only_their_own_tournaments(): void
    {
        $this->seed(RoleSeeder::class);

        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $otherOrganizer = User::factory()->create();
        $otherOrganizer->assignRole('organizer');
        Tournament::create([
            'name' => 'Torneo de otro organizador',
            'slug' => 'torneo-otro',
            'created_by' => $otherOrganizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        $response = $this->actingAs($organizer)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('organizer.tournaments_total', 0)
            ->missing('admin')
        );
    }

    public function test_player_sees_their_progression_stats(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $player = Player::create(['user_id' => $user->id, 'rating_current' => 1200]);
        $user->assignRole('player');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('player.rating_current', 1200)
            ->where('player.player_id', $player->id)
        );
    }

    public function test_user_with_no_activity_sees_an_empty_dashboard(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->missing('admin')
            ->missing('organizer')
            ->missing('referee')
            ->missing('player')
        );
    }
}
