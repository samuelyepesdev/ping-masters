<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\BracketAdvancementService;
use App\Services\Brackets\DoubleEliminationGenerator;
use App\Services\Brackets\SingleEliminationGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LargeBracketPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function division(int $entrantCount, string $format): TournamentDivision
    {
        $organizer = User::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Copa a gran escala',
            'slug' => 'copa-gran-escala-'.uniqid(),
            'created_by' => $organizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        $division = $tournament->divisions()->create([
            'name' => 'Individual',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => $format,
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
        ]);

        for ($i = 1; $i <= $entrantCount; $i++) {
            $user = User::factory()->create();
            $player = Player::create(['user_id' => $user->id, 'rating_current' => 1000 + $i]);
            $registration = TournamentRegistration::create([
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'status' => 'approved',
                'submitted_at' => now(),
            ]);
            TournamentRegistrationDivision::create([
                'tournament_registration_id' => $registration->id,
                'tournament_division_id' => $division->id,
                'seed_rating_snapshot' => $player->rating_current,
            ]);
        }

        return $division;
    }

    public function test_a_64_entrant_single_elimination_bracket_generates_correctly_and_quickly(): void
    {
        $division = $this->division(64, 'single_elimination');

        $start = microtime(true);
        app(SingleEliminationGenerator::class)->generate($division);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(10.0, $elapsed, "Generating a 64-entrant bracket took {$elapsed}s, which is unreasonably slow.");

        // 64 -> 32 -> 16 -> 8 -> 4 -> 2 -> 1 = 6 rounds, 63 total matches, no byes (64 is a power of two).
        $this->assertSame(6, Round::where('tournament_division_id', $division->id)->count());
        $this->assertSame(63, TournamentMatch::where('tournament_division_id', $division->id)->count());
        $this->assertSame(0, TournamentMatch::where('tournament_division_id', $division->id)
            ->where(fn ($q) => $q->where('entrant1_is_bye', true)->orWhere('entrant2_is_bye', true))
            ->count());

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $this->assertSame(32, TournamentMatch::where('round_id', $round1->id)->count());

        // Play the entire bracket out and confirm a single champion emerges without error.
        $advancement = app(BracketAdvancementService::class);
        $playedRounds = 0;

        while (! $advancement->isDivisionComplete($division)) {
            $readyMatches = TournamentMatch::where('tournament_division_id', $division->id)->where('status', 'ready')->get();
            $this->assertGreaterThan(0, $readyMatches->count(), 'No ready matches but the division is not complete — advancement stalled.');

            foreach ($readyMatches as $match) {
                $advancement->recordResult($match, $match->entrant1_id);
            }

            $playedRounds++;
            $this->assertLessThan(10, $playedRounds, 'Too many iterations — advancement likely stuck in a loop.');
        }

        $this->assertSame(6, $playedRounds);
        $this->assertSame(63, TournamentMatch::where('tournament_division_id', $division->id)->where('status', 'completed')->count());
    }

    public function test_a_32_entrant_double_elimination_bracket_completes_without_error(): void
    {
        $division = $this->division(32, 'double_elimination');

        app(DoubleEliminationGenerator::class)->generate($division);

        // WB: 5 rounds (16,8,4,2,1). LB: 2*(5-1)=8 rounds. GF: 1 round. Total matches = 2*32-2 = 62.
        $this->assertSame(62, TournamentMatch::where('tournament_division_id', $division->id)->count());

        $advancement = app(BracketAdvancementService::class);
        $iterations = 0;

        while (! $advancement->isDivisionComplete($division)) {
            $readyMatches = TournamentMatch::where('tournament_division_id', $division->id)->where('status', 'ready')->get();
            $this->assertGreaterThan(0, $readyMatches->count(), 'No ready matches but the division is not complete — advancement stalled.');

            foreach ($readyMatches as $match) {
                $advancement->recordResult($match, $match->entrant1_id);
            }

            $iterations++;
            $this->assertLessThan(30, $iterations, 'Too many iterations — advancement likely stuck in a loop.');
        }

        $this->assertSame(62, TournamentMatch::where('tournament_division_id', $division->id)->where('status', 'completed')->count());
    }
}
