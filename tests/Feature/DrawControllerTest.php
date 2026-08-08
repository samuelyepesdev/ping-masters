<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_organizer_can_generate_a_draw_and_record_match_results_over_http(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $tournament = Tournament::create([
            'name' => 'Copa HTTP',
            'slug' => 'copa-http',
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

        $outsider = User::factory()->create();
        $outsider->assignRole('organizer');
        $this->actingAs($outsider)
            ->post(route('tournaments.divisions.draw', [$tournament, $division]))
            ->assertForbidden();

        $this->actingAs($organizer)
            ->post(route('tournaments.divisions.draw', [$tournament, $division]))
            ->assertRedirect();

        $this->assertSame('drawn', $division->fresh()->status);

        // Drawing twice should be rejected now that the division is no longer pending_draw.
        $this->actingAs($organizer)
            ->post(route('tournaments.divisions.draw', [$tournament, $division]))
            ->assertSessionHas('error');

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $matches = TournamentMatch::where('round_id', $round1->id)->get();

        foreach ($matches as $match) {
            $this->actingAs($organizer)
                ->patch(route('tournaments.divisions.matches.result', [$tournament, $division, $match]), [
                    'winner_entrant_id' => $match->entrant1_id,
                ])
                ->assertRedirect();
        }

        $final = Round::where('tournament_division_id', $division->id)->where('round_number', 2)->first();
        $finalMatch = TournamentMatch::where('round_id', $final->id)->first()->fresh();
        $this->assertSame('ready', $finalMatch->status);

        // Wrong winner id (not one of the two entrants) must be rejected by validation.
        $this->actingAs($organizer)
            ->patch(route('tournaments.divisions.matches.result', [$tournament, $division, $finalMatch]), [
                'winner_entrant_id' => 999999,
            ])
            ->assertSessionHasErrors('winner_entrant_id');

        $this->actingAs($organizer)
            ->patch(route('tournaments.divisions.matches.result', [$tournament, $division, $finalMatch]), [
                'winner_entrant_id' => $finalMatch->entrant1_id,
            ])
            ->assertRedirect();

        $this->assertSame($finalMatch->entrant1_id, $finalMatch->fresh()->winner_entrant_id);
    }
}
