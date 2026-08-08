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
use App\Services\Brackets\SingleEliminationGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleEliminationDrawTest extends TestCase
{
    use RefreshDatabase;

    private function makeDivision(int $entrantCount, string $format = 'single_elimination'): TournamentDivision
    {
        $organizer = User::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Copa de prueba',
            'slug' => 'copa-de-prueba-'.uniqid(),
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
            $player = Player::create(['user_id' => $user->id, 'rating_current' => 1000 + ($entrantCount - $i) * 10]);

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

    public function test_generates_a_clean_bracket_for_a_power_of_two_entrant_count(): void
    {
        $division = $this->makeDivision(8);

        app(SingleEliminationGenerator::class)->generate($division);

        $this->assertSame('drawn', $division->fresh()->status);
        $this->assertSame(3, Round::where('tournament_division_id', $division->id)->count());

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $round1Matches = TournamentMatch::where('round_id', $round1->id)->get();

        $this->assertCount(4, $round1Matches);
        $this->assertTrue($round1Matches->every(fn (TournamentMatch $m) => $m->status === 'ready'));
        $this->assertTrue($round1Matches->every(fn (TournamentMatch $m) => $m->entrant1_id !== null && $m->entrant2_id !== null));

        $final = Round::where('tournament_division_id', $division->id)->where('round_number', 3)->first();
        $this->assertSame('Final', $final->name);
    }

    public function test_seed_one_plays_the_lowest_seed_and_seed_two_is_on_the_opposite_side(): void
    {
        $division = $this->makeDivision(8);

        app(SingleEliminationGenerator::class)->generate($division);

        $entrants = $division->approvedEntrants()->orderByDesc('seed_rating_snapshot')->get();
        $seed1 = $entrants[0];
        $seed2 = $entrants[1];
        $seed8 = $entrants[7];

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $matches = TournamentMatch::where('round_id', $round1->id)->orderBy('match_number')->get();

        $seed1Match = $matches->first(fn (TournamentMatch $m) => in_array($seed1->id, [$m->entrant1_id, $m->entrant2_id]));
        $this->assertTrue(in_array($seed8->id, [$seed1Match->entrant1_id, $seed1Match->entrant2_id]));

        $seed2Match = $matches->first(fn (TournamentMatch $m) => in_array($seed2->id, [$m->entrant1_id, $m->entrant2_id]));
        $this->assertNotSame($seed1Match->id, $seed2Match->id);
        $this->assertSame($matches->first()->id, $seed1Match->id);

        // Seed 1 and seed 2 must sit in opposite halves of the bracket so they can only meet in the final.
        $seed1HalfIndex = $matches->search(fn (TournamentMatch $m) => $m->id === $seed1Match->id);
        $seed2HalfIndex = $matches->search(fn (TournamentMatch $m) => $m->id === $seed2Match->id);
        $this->assertTrue($seed1HalfIndex < 2 && $seed2HalfIndex >= 2);
    }

    public function test_byes_are_awarded_to_top_seeds_and_propagate_into_round_two(): void
    {
        $division = $this->makeDivision(5);

        app(SingleEliminationGenerator::class)->generate($division);

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $round1Matches = TournamentMatch::where('round_id', $round1->id)->orderBy('match_number')->get();

        $this->assertCount(4, $round1Matches);

        $byeMatches = $round1Matches->filter(fn (TournamentMatch $m) => $m->isBye());
        $this->assertCount(3, $byeMatches);
        $this->assertTrue($byeMatches->every(fn (TournamentMatch $m) => $m->status === 'completed'));
        $this->assertTrue($byeMatches->every(fn (TournamentMatch $m) => $m->winner_entrant_id !== null));

        $round2 = Round::where('tournament_division_id', $division->id)->where('round_number', 2)->first();
        $round2Matches = TournamentMatch::where('round_id', $round2->id)->get();

        $filledSlots = $round2Matches->sum(fn (TournamentMatch $m) => ($m->entrant1_id ? 1 : 0) + ($m->entrant2_id ? 1 : 0));
        $this->assertSame(3, $filledSlots);
    }

    public function test_recording_results_advances_the_bracket_to_a_champion(): void
    {
        $division = $this->makeDivision(4);

        app(SingleEliminationGenerator::class)->generate($division);

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $round1Matches = TournamentMatch::where('round_id', $round1->id)->orderBy('match_number')->get();

        $advancement = app(BracketAdvancementService::class);
        $winners = [];

        foreach ($round1Matches as $match) {
            $winnerId = $match->entrant1_id;
            $advancement->recordResult($match, $winnerId);
            $winners[] = $winnerId;
        }

        $final = Round::where('tournament_division_id', $division->id)->where('round_number', 2)->first();
        $finalMatch = TournamentMatch::where('round_id', $final->id)->first();

        $this->assertSame('ready', $finalMatch->fresh()->status);
        $this->assertEqualsCanonicalizing($winners, [$finalMatch->fresh()->entrant1_id, $finalMatch->fresh()->entrant2_id]);

        $advancement->recordResult($finalMatch->fresh(), $winners[0]);

        $this->assertSame($winners[0], $finalMatch->fresh()->winner_entrant_id);
        $this->assertTrue($advancement->isDivisionComplete($division));
    }
}
