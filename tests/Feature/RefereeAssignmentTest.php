<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\SingleEliminationGenerator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefereeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function readyMatch(): array
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $tournament = Tournament::create([
            'name' => 'Copa arbitraje',
            'slug' => 'copa-arbitraje-'.uniqid(),
            'created_by' => $organizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        $division = $tournament->divisions()->create([
            'name' => 'Individual',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'single_elimination',
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
        ]);

        foreach (range(1, 4) as $i) {
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

        app(SingleEliminationGenerator::class)->generate($division);

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $match = TournamentMatch::where('round_id', $round1->id)->first();

        return [$organizer, $tournament, $division, $match];
    }

    public function test_organizer_can_assign_a_referee_to_a_match(): void
    {
        [$organizer, $tournament, $division, $match] = $this->readyMatch();

        $referee = User::factory()->create();
        $referee->assignRole('referee');

        $this->actingAs($organizer)
            ->patch(route('tournaments.divisions.matches.referee', [$tournament, $division, $match]), ['referee_id' => $referee->id])
            ->assertRedirect();

        $this->assertSame($referee->id, $match->fresh()->referee_id);
    }

    public function test_an_unassigned_referee_cannot_score_the_match(): void
    {
        [, $tournament, $division, $match] = $this->readyMatch();

        $referee = User::factory()->create();
        $referee->assignRole('referee');

        $this->actingAs($referee)
            ->post(route('tournaments.divisions.matches.start', [$tournament, $division, $match]), [
                'first_server_entrant_id' => $match->entrant1_id,
            ])
            ->assertForbidden();
    }

    public function test_the_assigned_referee_can_score_the_match_even_though_they_are_not_the_organizer(): void
    {
        [$organizer, $tournament, $division, $match] = $this->readyMatch();

        $referee = User::factory()->create();
        $referee->assignRole('referee');
        $match->update(['referee_id' => $referee->id]);

        $this->actingAs($referee)
            ->post(route('tournaments.divisions.matches.start', [$tournament, $division, $match]), [
                'first_server_entrant_id' => $match->entrant1_id,
            ])
            ->assertRedirect();

        $this->assertSame('in_progress', $match->fresh()->status);
    }

    public function test_a_referee_cannot_assign_referees_to_matches(): void
    {
        [, $tournament, $division, $match] = $this->readyMatch();

        $referee = User::factory()->create();
        $referee->assignRole('referee');

        $this->actingAs($referee)
            ->patch(route('tournaments.divisions.matches.referee', [$tournament, $division, $match]), ['referee_id' => $referee->id])
            ->assertForbidden();
    }

    public function test_the_referee_dashboard_only_shows_matches_assigned_to_the_current_user(): void
    {
        [, , , $match] = $this->readyMatch();

        $referee = User::factory()->create();
        $referee->assignRole('referee');
        $match->update(['referee_id' => $referee->id]);

        $otherReferee = User::factory()->create();
        $otherReferee->assignRole('referee');

        $this->actingAs($referee)->get(route('referee.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('referee/index')
            ->has('upcoming', 1)
        );

        $this->actingAs($otherReferee)->get(route('referee.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('referee/index')
            ->has('upcoming', 0)
        );
    }
}
