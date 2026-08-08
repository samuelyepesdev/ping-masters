<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Achievements\AchievementEngine;
use App\Services\Xp\LevelService;
use App\Services\Xp\XpService;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\LevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpAndAchievementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LevelSeeder::class);
        $this->seed(AchievementSeeder::class);
    }

    private function player(): Player
    {
        $user = User::factory()->create();

        return Player::create(['user_id' => $user->id]);
    }

    public function test_level_service_maps_xp_to_the_correct_level(): void
    {
        $levels = app(LevelService::class);

        $this->assertSame(1, $levels->levelForXp(0));
        $this->assertSame(1, $levels->levelForXp(99));
        $this->assertSame(2, $levels->levelForXp(100));
        $this->assertSame(2, $levels->levelForXp(249));
        $this->assertSame(3, $levels->levelForXp(250));
        $this->assertSame(10, $levels->levelForXp(999999));
    }

    public function test_awarding_xp_updates_total_and_recalculates_level(): void
    {
        $player = $this->player();
        $xp = app(XpService::class);

        $tournament = Tournament::create([
            'name' => 'Copa XP',
            'slug' => 'copa-xp-'.uniqid(),
            'created_by' => $player->user_id,
            'status' => 'registration_open',
            'start_date' => now(),
            'end_date' => now()->addDay(),
        ]);

        $xp->awardForRegistration($player, $tournament);

        $player->refresh();
        $this->assertSame(10, $player->xp_total);
        $this->assertSame(1, $player->level);
        $this->assertDatabaseHas('player_xp_events', [
            'player_id' => $player->id,
            'type' => 'registration',
            'amount' => 10,
        ]);

        // Push them over the level 2 threshold (100 xp).
        for ($i = 0; $i < 9; $i++) {
            $xp->awardForRegistration($player, $tournament);
        }

        $player->refresh();
        $this->assertSame(100, $player->xp_total);
        $this->assertSame(2, $player->level);
    }

    public function test_first_win_achievement_unlocks_and_grants_bonus_xp(): void
    {
        $player = $this->player();
        $player->update(['matches_won' => 1, 'matches_played' => 1]);

        app(AchievementEngine::class)->evaluateForPlayer($player);

        $this->assertTrue($player->achievements()->where('code', 'first_win')->exists());
        $player->refresh();
        $this->assertSame(20, $player->xp_total); // first_win's xp_reward

        // Evaluating again must not double-unlock or double-award.
        app(AchievementEngine::class)->evaluateForPlayer($player);
        $this->assertSame(1, $player->achievements()->where('code', 'first_win')->count());
        $this->assertSame(20, $player->fresh()->xp_total);
    }

    public function test_veteran_achievement_requires_twenty_matches_played(): void
    {
        $player = $this->player();
        $player->update(['matches_played' => 19]);

        app(AchievementEngine::class)->evaluateForPlayer($player);
        $this->assertFalse($player->achievements()->where('code', 'veteran')->exists());

        $player->update(['matches_played' => 20]);
        app(AchievementEngine::class)->evaluateForPlayer($player);
        $this->assertTrue($player->fresh()->achievements()->where('code', 'veteran')->exists());
    }
}
