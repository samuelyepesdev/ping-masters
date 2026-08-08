<?php

namespace Tests\Unit;

use App\Models\MatchGame;
use App\Services\Scoring\ExpediteService;
use App\Services\Scoring\ServeRotationCalculator;
use PHPUnit\Framework\TestCase;

class ServeRotationCalculatorTest extends TestCase
{
    private function game(int $e1, int $e2, int $firstServer): MatchGame
    {
        $game = new MatchGame();
        $game->entrant1_points = $e1;
        $game->entrant2_points = $e2;
        $game->first_server_entrant_id = $firstServer;
        $game->started_at = null;

        return $game;
    }

    private function calculator(): ServeRotationCalculator
    {
        return new ServeRotationCalculator(new ExpediteService());
    }

    public function test_serves_alternate_every_two_points_before_deuce(): void
    {
        $calc = $this->calculator();
        $A = 1;
        $B = 2;

        // Points 0,1 -> A serves. Points 2,3 -> B serves. Points 4,5 -> A serves.
        $this->assertSame($A, $calc->currentServer($this->game(0, 0, $A), $A, $B));
        $this->assertSame($A, $calc->currentServer($this->game(1, 0, $A), $A, $B));
        $this->assertSame($B, $calc->currentServer($this->game(2, 0, $A), $A, $B));
        $this->assertSame($B, $calc->currentServer($this->game(2, 1, $A), $A, $B));
        $this->assertSame($A, $calc->currentServer($this->game(3, 1, $A), $A, $B));
    }

    public function test_serves_alternate_every_point_once_deuce_is_reached(): void
    {
        $calc = $this->calculator();
        $A = 1;
        $B = 2;

        // 10-10: total=20, even -> A serves (continuing the natural rotation).
        $this->assertSame($A, $calc->currentServer($this->game(10, 10, $A), $A, $B));
        // 11-10: total=21, odd -> B serves (alternates every single point now).
        $this->assertSame($B, $calc->currentServer($this->game(11, 10, $A), $A, $B));
        // 11-11: total=22, even -> A serves.
        $this->assertSame($A, $calc->currentServer($this->game(11, 11, $A), $A, $B));
        // 12-11: total=23, odd -> B serves.
        $this->assertSame($B, $calc->currentServer($this->game(12, 11, $A), $A, $B));
    }

    public function test_deuce_detection_requires_both_players_to_reach_ten(): void
    {
        $calc = $this->calculator();
        $A = 1;
        $B = 2;

        // 10-9: total=19, not deuce yet (only one side at 10) -> normal 2-per-serve rule applies.
        // floor(19/2)=9, odd -> B serves.
        $this->assertSame($B, $calc->currentServer($this->game(10, 9, $A), $A, $B));
    }
}
