<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentGroup;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\BracketAdvancementService;
use App\Services\Brackets\GroupKnockoutGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupKnockoutDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_feed_a_knockout_bracket_through_to_a_champion(): void
    {
        $organizer = User::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Copa grupos',
            'slug' => 'copa-grupos',
            'created_by' => $organizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        /** @var TournamentDivision $division */
        $division = $tournament->divisions()->create([
            'name' => 'Individual',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'group_knockout',
            'best_of' => 5,
            'points_to_win' => 11,
            'group_size' => 3,
            'advance_per_group' => 2,
            'seed_by_rating' => true,
        ]);

        $entrants = [];
        foreach (range(1, 9) as $i) {
            $user = User::factory()->create();
            $player = Player::create(['user_id' => $user->id, 'rating_current' => 2000 - $i * 10]);
            $registration = TournamentRegistration::create([
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'status' => 'approved',
                'submitted_at' => now(),
            ]);
            $entrants["p{$i}"] = TournamentRegistrationDivision::create([
                'tournament_registration_id' => $registration->id,
                'tournament_division_id' => $division->id,
                'seed_rating_snapshot' => $player->rating_current,
            ]);
        }

        app(GroupKnockoutGenerator::class)->generate($division);

        $this->assertSame('drawn', $division->fresh()->status);
        $this->assertSame(3, TournamentGroup::where('tournament_division_id', $division->id)->count());

        $groupMatches = TournamentMatch::where('tournament_division_id', $division->id)->whereNotNull('tournament_group_id')->get();
        $this->assertCount(9, $groupMatches); // 3 groups x 3 matches (round robin of 3)

        $advancement = app(BracketAdvancementService::class);

        // Play out every group match: within each group, the entrant object created earlier
        // beats the one with a numerically higher id (arbitrary but deterministic).
        foreach ($groupMatches as $match) {
            $winnerId = min($match->entrant1_id, $match->entrant2_id);
            $advancement->recordResult($match->fresh(), $winnerId);
        }

        $koFirstRound = Round::where('tournament_division_id', $division->id)->where('stage', 'main_bracket')->where('round_number', 1)->first();
        $koMatches = TournamentMatch::where('round_id', $koFirstRound->id)->get();

        $this->assertCount(4, $koMatches); // bracketSize(8)/2 = 4 (6 real qualifiers + 2 byes)

        $realSlots = $koMatches->sum(fn (TournamentMatch $m) => ($m->entrant1_id ? 1 : 0) + ($m->entrant2_id ? 1 : 0));
        $this->assertSame(6, $realSlots);

        $byeMatches = $koMatches->filter(fn (TournamentMatch $m) => $m->isBye());
        $this->assertCount(2, $byeMatches);
        $this->assertTrue($byeMatches->every(fn (TournamentMatch $m) => $m->status === 'completed'));

        $readyMatches = $koMatches->filter(fn (TournamentMatch $m) => $m->status === 'ready');
        $this->assertCount(2, $readyMatches);

        foreach ($readyMatches as $match) {
            $advancement->recordResult($match->fresh(), $match->entrant1_id);
        }

        foreach ($byeMatches as $match) {
            $this->assertNotNull($match->fresh()->winner_entrant_id);
        }

        $koSemis = Round::where('tournament_division_id', $division->id)->where('stage', 'main_bracket')->where('round_number', 2)->first();
        $semiMatches = TournamentMatch::where('round_id', $koSemis->id)->get();
        $this->assertCount(2, $semiMatches);
        $this->assertTrue($semiMatches->every(fn (TournamentMatch $m) => $m->fresh()->entrant1_id !== null && $m->fresh()->entrant2_id !== null));

        foreach ($semiMatches as $match) {
            $advancement->recordResult($match->fresh(), $match->fresh()->entrant1_id);
        }

        $koFinalRound = Round::where('tournament_division_id', $division->id)->where('stage', 'main_bracket')->where('round_number', 3)->first();
        $finalMatch = TournamentMatch::where('round_id', $koFinalRound->id)->first()->fresh();
        $this->assertSame('ready', $finalMatch->status);

        $advancement->recordResult($finalMatch, $finalMatch->entrant1_id);

        $this->assertTrue($advancement->isDivisionComplete($division));
    }
}
