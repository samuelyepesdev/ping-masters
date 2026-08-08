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
use App\Services\Scoring\MatchScoringService;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PlayerProgressionEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([MatchScoreUpdated::class]);

        $this->seed(RoleSeeder::class);
        $this->seed(LevelSeeder::class);
        $this->seed(AchievementSeeder::class);
    }

    public function test_playing_out_a_bracket_updates_ratings_xp_achievements_and_champion_bonus(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $tournament = Tournament::create([
            'name' => 'Copa progresión',
            'slug' => 'copa-progresion',
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

        $players = [];
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
            $players[] = $player;
        }

        app(SingleEliminationGenerator::class)->generate($division);

        $scoring = app(MatchScoringService::class);

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $round1Matches = TournamentMatch::where('round_id', $round1->id)->orderBy('match_number')->get();

        // Entrant1 always wins in this test's scripted playthrough, so resolve winner/loser
        // players from each match's entrant1/entrant2 before any points are scored.
        $firstMatch = $round1Matches->first();
        $winner1 = $firstMatch->entrant1->registration->player;
        $loser = $firstMatch->entrant2->registration->player;
        $winnerRatingBeforeRound1 = $winner1->rating_current;
        $loserRatingBeforeRound1 = $loser->rating_current;

        // Play round 1 over HTTP through the real scoring console so progression wiring is exercised end to end.
        foreach ($round1Matches as $match) {
            $this->actingAs($organizer)->post(route('tournaments.divisions.matches.start', [$tournament, $division, $match]), [
                'first_server_entrant_id' => $match->entrant1_id,
            ]);

            for ($g = 0; $g < 3; $g++) {
                for ($p = 0; $p < 11; $p++) {
                    $this->actingAs($organizer)->post(route('tournaments.divisions.matches.point', [$tournament, $division, $match]), [
                        'entrant_id' => $match->entrant1_id,
                    ]);
                }
            }
        }

        $this->assertGreaterThan($winnerRatingBeforeRound1, $winner1->fresh()->rating_current);
        $this->assertSame(1, $winner1->fresh()->matches_won);
        $this->assertGreaterThanOrEqual(20, $winner1->fresh()->xp_total); // match_played+match_won+first_win achievement
        $this->assertTrue($winner1->fresh()->achievements()->where('code', 'first_win')->exists());

        $this->assertLessThan($loserRatingBeforeRound1, $loser->fresh()->rating_current);
        $this->assertSame(1, $loser->fresh()->matches_played);
        $this->assertSame(0, $loser->fresh()->matches_won);

        // Play the final.
        $final = Round::where('tournament_division_id', $division->id)->where('round_number', 2)->first();
        $finalMatch = TournamentMatch::where('round_id', $final->id)->first()->fresh();

        $this->actingAs($organizer)->post(route('tournaments.divisions.matches.start', [$tournament, $division, $finalMatch]), [
            'first_server_entrant_id' => $finalMatch->entrant1_id,
        ]);

        for ($g = 0; $g < 3; $g++) {
            for ($p = 0; $p < 11; $p++) {
                $this->actingAs($organizer)->post(route('tournaments.divisions.matches.point', [$tournament, $division, $finalMatch]), [
                    'entrant_id' => $finalMatch->entrant1_id,
                ]);
            }
        }

        $champion = $winner1->fresh();
        $this->assertDatabaseHas('player_xp_events', [
            'player_id' => $champion->id,
            'type' => 'division_champion',
            'amount' => 100,
        ]);

        // Verify the public ranking and profile pages render with this real data.
        $this->get(route('public.players.ranking'))->assertOk();
        $this->get(route('public.players.show', $champion))->assertOk();
    }
}
