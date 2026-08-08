<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_branded_home_page_renders_with_live_data(): void
    {
        $organizer = User::factory()->create();
        Tournament::create([
            'name' => 'Copa Home',
            'slug' => 'copa-home',
            'created_by' => $organizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        $player = Player::create(['user_id' => User::factory()->create()->id, 'rating_current' => 1200]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('home')
            ->has('tournaments', 1)
            ->has('topPlayers', 1)
            ->where('stats.tournaments', 1)
            ->where('stats.players', 1)
        );

        $this->assertNotNull($player);
    }
}
