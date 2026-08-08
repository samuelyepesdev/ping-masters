<?php

namespace Tests\Feature;

use App\Models\MatchPoint;
use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\SingleEliminationGenerator;
use App\Services\Scoring\MatchScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchScoringEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private function drawnDivision(int $entrantCount = 4)
    {
        $organizer = User::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Copa e2e',
            'slug' => 'copa-e2e-'.uniqid(),
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

        foreach (range(1, $entrantCount) as $i) {
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

        return $division;
    }

    public function test_a_full_match_played_through_deuce_advances_the_next_round_match_to_ready(): void
    {
        $division = $this->drawnDivision(4);
        $scoring = app(MatchScoringService::class);

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $matches = TournamentMatch::where('round_id', $round1->id)->orderBy('match_number')->get();

        foreach ($matches as $match) {
            $scoring->startMatch($match, $match->entrant1_id);

            // Game 1 & 2: straightforward win for entrant1.
            for ($g = 0; $g < 2; $g++) {
                for ($p = 0; $p < 11; $p++) {
                    $scoring->recordPoint($match, $match->entrant1_id);
                }
            }

            // Game 3: fight it out to deuce, entrant2 pulls it back to keep the match alive... but
            // let entrant1 close it out 3-0 so we can assert the match (not just the game) is done.
            for ($i = 0; $i < 10; $i++) {
                $scoring->recordPoint($match, $match->entrant1_id);
                $scoring->recordPoint($match, $match->entrant2_id);
            }
            $game3 = $match->currentGame();
            $this->assertSame(10, $game3->entrant1_points);
            $this->assertSame(10, $game3->entrant2_points);

            $scoring->recordPoint($match, $match->entrant1_id); // 11-10: not enough, only a 1 point lead
            $scoring->recordPoint($match, $match->entrant1_id); // 12-10: two point lead, game (and match) over

            $match->refresh();
            $this->assertSame('completed', $match->status);
            $this->assertSame('3-0', $match->score_summary);
        }

        $final = Round::where('tournament_division_id', $division->id)->where('round_number', 2)->first();
        $finalMatch = TournamentMatch::where('round_id', $final->id)->first()->fresh();

        $this->assertSame('ready', $finalMatch->status);
        $this->assertNotNull($finalMatch->entrant1_id);
        $this->assertNotNull($finalMatch->entrant2_id);

        // Play the final out via walkover and confirm the division is now fully complete.
        $scoring->recordWalkover($finalMatch, $finalMatch->entrant1_id);

        $this->assertSame('walkover', $finalMatch->fresh()->status);
        $this->assertTrue(app(\App\Services\Brackets\BracketAdvancementService::class)->isDivisionComplete($division));
    }

    public function test_expedite_kicks_in_after_ten_minutes_and_forces_serve_every_point(): void
    {
        $division = $this->drawnDivision(4);
        $scoring = app(MatchScoringService::class);

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $match = TournamentMatch::where('round_id', $round1->id)->first();

        $game = $scoring->startMatch($match, $match->entrant1_id);

        // Simulate the game having been running for 11 minutes with a low, deadlocked score (2-2).
        $game->started_at = now()->subMinutes(11);
        $game->save();

        $scoring->recordPoint($match, $match->entrant1_id);

        $point = MatchPoint::where('match_game_id', $game->id)->latest('point_number')->first();
        $this->assertTrue((bool) $point->was_expedite);

        // Before expedite (normal 2-per-serve), total points would need to hit an even boundary
        // to change server; under expedite it must alternate on every single point.
        $serverBefore = $point->server_entrant_id;

        $scoring->recordPoint($match, $match->entrant2_id);
        $pointAfter = MatchPoint::where('match_game_id', $game->id)->latest('point_number')->first();

        $this->assertNotSame($serverBefore, $pointAfter->server_entrant_id);
    }
}
