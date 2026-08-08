<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\RoundRobinGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoundRobinDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_entrant_plays_every_other_entrant_exactly_once(): void
    {
        $organizer = User::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Copa round robin',
            'slug' => 'copa-round-robin',
            'created_by' => $organizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        $division = $tournament->divisions()->create([
            'name' => 'Individual',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'round_robin',
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
        ]);

        $entrantIds = [];
        foreach (range(1, 5) as $i) {
            $user = User::factory()->create();
            $player = Player::create(['user_id' => $user->id, 'rating_current' => 1000 + $i]);
            $registration = TournamentRegistration::create([
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'status' => 'approved',
                'submitted_at' => now(),
            ]);
            $entrantIds[] = TournamentRegistrationDivision::create([
                'tournament_registration_id' => $registration->id,
                'tournament_division_id' => $division->id,
                'seed_rating_snapshot' => $player->rating_current,
            ])->id;
        }

        app(RoundRobinGenerator::class)->generate($division);

        $this->assertSame('drawn', $division->fresh()->status);

        // 5 entrants (odd) -> circle method pads to 6 -> 5 rounds, one bye (skipped match) per round.
        $this->assertSame(5, Round::where('tournament_division_id', $division->id)->count());

        $matches = TournamentMatch::where('tournament_division_id', $division->id)->get();
        $this->assertCount(10, $matches); // n*(n-1)/2 = 5*4/2

        $pairsSeen = [];
        foreach ($matches as $match) {
            $this->assertSame('ready', $match->status);
            $key = collect([$match->entrant1_id, $match->entrant2_id])->sort()->implode('-');
            $this->assertArrayNotHasKey($key, $pairsSeen, 'Duplicate pairing detected: '.$key);
            $pairsSeen[$key] = true;
        }

        $playCounts = array_fill_keys($entrantIds, 0);
        foreach ($matches as $match) {
            $playCounts[$match->entrant1_id]++;
            $playCounts[$match->entrant2_id]++;
        }

        foreach ($playCounts as $count) {
            $this->assertSame(4, $count);
        }
    }
}
