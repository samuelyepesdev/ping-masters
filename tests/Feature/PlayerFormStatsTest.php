<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\PlayerRatingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerFormStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_reports_recent_form_and_monthly_win_loss_counts(): void
    {
        $player = Player::create(['user_id' => User::factory()->create()->id]);
        $opponent = Player::create(['user_id' => User::factory()->create(['name' => 'Rival Uno'])->id]);

        // A win in July, a loss in August — same opponent, different months.
        $this->createRatingHistoryRow($player->id, $opponent->id, 1000, 1020, '2026-07-15 10:00:00');
        $this->createRatingHistoryRow($player->id, $opponent->id, 1020, 1000, '2026-08-01 10:00:00');

        $response = $this->get(route('public.players.show', $player));

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $props = $page->toArray()['props'];

            $this->assertCount(2, $props['recentForm']);
            $this->assertTrue($props['recentForm'][0]['won']);
            $this->assertSame('Rival Uno', $props['recentForm'][0]['opponent']);
            $this->assertFalse($props['recentForm'][1]['won']);

            $monthly = collect($props['monthlyForm'])->keyBy('month');
            $this->assertSame(1, $monthly['2026-07']['wins']);
            $this->assertSame(0, $monthly['2026-07']['losses']);
            $this->assertSame(0, $monthly['2026-08']['wins']);
            $this->assertSame(1, $monthly['2026-08']['losses']);
        });
    }

    private function createRatingHistoryRow(int $playerId, int $opponentId, int $before, int $after, string $createdAt): void
    {
        $row = new PlayerRatingHistory([
            'player_id' => $playerId,
            'opponent_player_id' => $opponentId,
            'rating_before' => $before,
            'rating_after' => $after,
        ]);
        $row->timestamps = false;
        $row->created_at = $createdAt;
        $row->updated_at = $createdAt;
        $row->save();
    }
}
