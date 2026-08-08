<?php

namespace Tests\Feature;

use App\Events\MatchScoreUpdated;
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
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MatchScoringControllerTest extends TestCase
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
            'name' => 'Copa consola',
            'slug' => 'copa-consola-'.uniqid(),
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

    public function test_scoring_a_match_over_http_broadcasts_score_updates(): void
    {
        Event::fake([MatchScoreUpdated::class]);

        [$organizer, $tournament, $division, $match] = $this->readyMatch();

        $this->actingAs($organizer)
            ->get(route('tournaments.divisions.matches.score', [$tournament, $division, $match]))
            ->assertOk();

        $this->actingAs($organizer)
            ->post(route('tournaments.divisions.matches.start', [$tournament, $division, $match]), [
                'first_server_entrant_id' => $match->entrant1_id,
            ])
            ->assertRedirect();

        $this->assertSame('in_progress', $match->fresh()->status);

        $this->actingAs($organizer)
            ->post(route('tournaments.divisions.matches.point', [$tournament, $division, $match]), [
                'entrant_id' => $match->entrant1_id,
            ])
            ->assertRedirect();

        $game = $match->currentGame();
        $this->assertSame(1, $game->entrant1_points);

        $this->actingAs($organizer)
            ->post(route('tournaments.divisions.matches.undo', [$tournament, $division, $match]))
            ->assertRedirect();

        $this->assertSame(0, $match->currentGame()->entrant1_points);

        Event::assertDispatched(MatchScoreUpdated::class, 3);

        // Public spectator scoreboard is accessible without authentication.
        $this->get(route('public.matches.show', $match))->assertOk();
    }

    public function test_walkover_ends_the_match_and_broadcasts_once(): void
    {
        Event::fake([MatchScoreUpdated::class]);

        [$organizer, $tournament, $division, $match] = $this->readyMatch();

        $this->actingAs($organizer)
            ->post(route('tournaments.divisions.matches.walkover', [$tournament, $division, $match]), [
                'winner_entrant_id' => $match->entrant2_id,
                'reason' => 'Lesión',
            ])
            ->assertRedirect();

        $this->assertSame('walkover', $match->fresh()->status);
        $this->assertSame($match->entrant2_id, $match->fresh()->winner_entrant_id);

        Event::assertDispatched(MatchScoreUpdated::class, 1);
    }

    public function test_an_outsider_organizer_cannot_score_someone_elses_match(): void
    {
        [, $tournament, $division, $match] = $this->readyMatch();

        $outsider = User::factory()->create();
        $outsider->assignRole('organizer');

        $this->actingAs($outsider)
            ->post(route('tournaments.divisions.matches.start', [$tournament, $division, $match]), [
                'first_server_entrant_id' => $match->entrant1_id,
            ])
            ->assertForbidden();
    }
}
