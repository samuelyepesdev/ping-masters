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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoubleEliminationDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_losers_bracket_finalist_can_still_lose_the_single_grand_final(): void
    {
        $organizer = User::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Copa doble eliminación',
            'slug' => 'copa-doble-eliminacion',
            'created_by' => $organizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        $division = $tournament->divisions()->create([
            'name' => 'Individual',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'double_elimination',
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
        ]);

        $entrants = [];
        foreach (['A', 'B', 'C', 'D'] as $i => $label) {
            $user = User::factory()->create();
            $player = Player::create(['user_id' => $user->id, 'rating_current' => 1000 - $i * 10]);
            $registration = TournamentRegistration::create([
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'status' => 'approved',
                'submitted_at' => now(),
            ]);
            $entrants[$label] = TournamentRegistrationDivision::create([
                'tournament_registration_id' => $registration->id,
                'tournament_division_id' => $division->id,
                'seed_rating_snapshot' => $player->rating_current,
            ]);
        }

        app(DoubleEliminationGenerator::class)->generate($division);

        $advancement = app(BracketAdvancementService::class);

        $wb1 = Round::where('tournament_division_id', $division->id)->where('stage', 'winners_bracket')->where('round_number', 1)->first();
        $wb1Matches = TournamentMatch::where('round_id', $wb1->id)->orderBy('match_number')->get();

        // Seeding pairs seed1 vs seed4 and seed2 vs seed3: A/D and B/C.
        $wb1MatchAD = $wb1Matches->first(fn ($m) => in_array($entrants['A']->id, [$m->entrant1_id, $m->entrant2_id]));
        $wb1MatchBC = $wb1Matches->first(fn ($m) => $m->id !== $wb1MatchAD->id);

        $advancement->recordResult($wb1MatchAD, $entrants['A']->id); // A beats D
        $advancement->recordResult($wb1MatchBC, $entrants['B']->id); // B beats C

        $wbFinal = Round::where('tournament_division_id', $division->id)->where('stage', 'winners_bracket')->where('round_number', 2)->first();
        $wbFinalMatch = TournamentMatch::where('round_id', $wbFinal->id)->first();
        $this->assertSame('ready', $wbFinalMatch->fresh()->status);

        $advancement->recordResult($wbFinalMatch->fresh(), $entrants['B']->id); // B beats A -> B is WB champion, A drops to LB

        $lb1 = Round::where('tournament_division_id', $division->id)->where('stage', 'losers_bracket')->where('round_number', 1)->first();
        $lb1Match = TournamentMatch::where('round_id', $lb1->id)->first()->fresh();
        $this->assertSame('ready', $lb1Match->status);
        $this->assertEqualsCanonicalizing([$entrants['C']->id, $entrants['D']->id], [$lb1Match->entrant1_id, $lb1Match->entrant2_id]);

        $advancement->recordResult($lb1Match, $entrants['D']->id); // D beats C

        $lbFinal = Round::where('tournament_division_id', $division->id)->where('stage', 'losers_bracket')->where('round_number', 2)->first();
        $lbFinalMatch = TournamentMatch::where('round_id', $lbFinal->id)->first()->fresh();
        $this->assertSame('ready', $lbFinalMatch->status);
        $this->assertEqualsCanonicalizing([$entrants['A']->id, $entrants['D']->id], [$lbFinalMatch->entrant1_id, $lbFinalMatch->entrant2_id]);

        $advancement->recordResult($lbFinalMatch, $entrants['A']->id); // A (WB finalist) beats D -> A becomes LB champion

        $grandFinal = Round::where('tournament_division_id', $division->id)->where('stage', 'grand_final')->first();
        $grandFinalMatch = TournamentMatch::where('round_id', $grandFinal->id)->first()->fresh();
        $this->assertSame('ready', $grandFinalMatch->status);
        $this->assertEqualsCanonicalizing([$entrants['A']->id, $entrants['B']->id], [$grandFinalMatch->entrant1_id, $grandFinalMatch->entrant2_id]);

        $advancement->recordResult($grandFinalMatch, $entrants['B']->id);

        $this->assertSame($entrants['B']->id, $grandFinalMatch->fresh()->winner_entrant_id);
        $this->assertTrue($advancement->isDivisionComplete($division));
    }
}
