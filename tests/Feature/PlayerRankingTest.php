<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_excludes_players_whose_account_was_soft_deleted()
    {
        $active = User::factory()->create(['name' => 'Jugador Activo']);
        Player::create(['user_id' => $active->id, 'rating_current' => 1200]);

        $removed = User::factory()->create(['name' => 'Jugador Eliminado']);
        $removedPlayer = Player::create(['user_id' => $removed->id, 'rating_current' => 1500]);
        $removed->delete();

        $response = $this->get(route('public.players.ranking'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('players.data', 1)
            ->where('players.data.0.user.name', 'Jugador Activo'));

        $this->assertSoftDeleted($removed);
        $this->assertNotNull(Player::find($removedPlayer->id));
    }

    public function test_a_removed_players_name_still_shows_on_their_own_profile_page()
    {
        $removed = User::factory()->create(['name' => 'Jugador Eliminado']);
        $player = Player::create(['user_id' => $removed->id, 'rating_current' => 1500]);
        $removed->delete();

        $response = $this->get(route('public.players.show', $player->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('player.user.name', 'Jugador Eliminado'));
    }
}
