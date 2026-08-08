<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\BracketAdvancementService;
use App\Services\Brackets\SingleEliminationGenerator;
use App\Services\Scoring\MatchScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private function readyMatch(int $bestOf = 5, int $pointsToWin = 11): array
    {
        $organizer = User::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Copa marcador',
            'slug' => 'copa-marcador-'.uniqid(),
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
            'best_of' => $bestOf,
            'points_to_win' => $pointsToWin,
            'seed_by_rating' => true,
        ]);

        $entrantIds = [];
        foreach (range(1, 4) as $i) {
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

        app(SingleEliminationGenerator::class)->generate($division);

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $match = TournamentMatch::where('round_id', $round1->id)->first();

        return [$match, $division];
    }

    public function test_playing_out_games_completes_a_match_and_advances_the_bracket(): void
    {
        [$match, $division] = $this->readyMatch(bestOf: 5, pointsToWin: 11);
        $scoring = app(MatchScoringService::class);

        $game = $scoring->startMatch($match, $match->entrant1_id);
        $this->assertSame(1, $game->game_number);
        $this->assertSame('in_progress', $match->fresh()->status);

        // Entrant1 wins games 1, 2, 3 (11-0 each) -> wins the match 3-0.
        for ($g = 1; $g <= 3; $g++) {
            for ($p = 1; $p <= 11; $p++) {
                $scoring->recordPoint($match, $match->entrant1_id);
            }
        }

        $match->refresh();
        $this->assertSame('completed', $match->status);
        $this->assertSame($match->entrant1_id, $match->winner_entrant_id);
        $this->assertSame($match->entrant2_id, $match->loser_entrant_id);
        $this->assertSame('3-0', $match->score_summary);
        $this->assertCount(3, $match->games()->get());

        $advancement = app(BracketAdvancementService::class);
        $this->assertFalse($advancement->isDivisionComplete($division)); // final round not yet played
    }

    public function test_deuce_requires_a_two_point_lead_to_win_the_game(): void
    {
        [$match] = $this->readyMatch(pointsToWin: 11);
        $scoring = app(MatchScoringService::class);

        $game = $scoring->startMatch($match, $match->entrant1_id);

        for ($i = 0; $i < 10; $i++) {
            $scoring->recordPoint($match, $match->entrant1_id);
            $scoring->recordPoint($match, $match->entrant2_id);
        }

        $game = $game->fresh();
        $this->assertSame(10, $game->entrant1_points);
        $this->assertSame(10, $game->entrant2_points);
        $this->assertNull($game->winner_entrant_id);

        // 11-10: not a win yet, only a 1 point lead.
        $game = $scoring->recordPoint($match, $match->entrant1_id);
        $this->assertNull($game->winner_entrant_id);

        // 11-11
        $game = $scoring->recordPoint($match, $match->entrant2_id);
        $this->assertNull($game->winner_entrant_id);

        // 12-11: still not enough.
        $game = $scoring->recordPoint($match, $match->entrant1_id);
        $this->assertNull($game->winner_entrant_id);

        // 13-11: two point lead, game over.
        $game = $scoring->recordPoint($match, $match->entrant1_id);
        $this->assertSame($match->entrant1_id, $game->winner_entrant_id);
        $this->assertSame(13, $game->entrant1_points);
        $this->assertSame(11, $game->entrant2_points);
    }

    public function test_first_server_alternates_each_new_game(): void
    {
        [$match] = $this->readyMatch(bestOf: 5, pointsToWin: 11);
        $scoring = app(MatchScoringService::class);

        $scoring->startMatch($match, $match->entrant1_id);

        for ($p = 1; $p <= 11; $p++) {
            $scoring->recordPoint($match, $match->entrant1_id);
        }

        $game2 = $match->games()->where('game_number', 2)->first();
        $this->assertSame($match->entrant2_id, $game2->first_server_entrant_id);

        for ($p = 1; $p <= 11; $p++) {
            $scoring->recordPoint($match, $match->entrant2_id);
        }

        $game3 = $match->games()->where('game_number', 3)->first();
        $this->assertSame($match->entrant1_id, $game3->first_server_entrant_id);
    }

    public function test_undo_last_point_reverses_the_score(): void
    {
        [$match] = $this->readyMatch();
        $scoring = app(MatchScoringService::class);

        $scoring->startMatch($match, $match->entrant1_id);
        $scoring->recordPoint($match, $match->entrant1_id);
        $game = $scoring->recordPoint($match, $match->entrant1_id);

        $this->assertSame(2, $game->entrant1_points);

        $scoring->undoLastPoint($match);

        $game = $match->currentGame();
        $this->assertSame(1, $game->entrant1_points);
        $this->assertSame(1, $game->points()->count());
    }

    public function test_undo_reopens_a_game_that_had_just_been_completed(): void
    {
        [$match] = $this->readyMatch(pointsToWin: 11);
        $scoring = app(MatchScoringService::class);

        $scoring->startMatch($match, $match->entrant1_id);

        for ($p = 1; $p <= 11; $p++) {
            $scoring->recordPoint($match, $match->entrant1_id);
        }

        $game1 = $match->games()->where('game_number', 1)->first();
        $this->assertNotNull($game1->winner_entrant_id);
        $this->assertSame(2, $match->games()->count()); // game 2 auto-created

        $scoring->undoLastPoint($match);

        $this->assertSame(1, $match->games()->count()); // the empty game 2 shell is removed
        $game1 = $match->games()->where('game_number', 1)->first();
        $this->assertNull($game1->winner_entrant_id);
        $this->assertSame(10, $game1->entrant1_points);
    }

    public function test_walkover_completes_the_match_without_playing_games(): void
    {
        [$match, $division] = $this->readyMatch();
        $scoring = app(MatchScoringService::class);

        $scoring->recordWalkover($match, $match->entrant2_id);

        $match->refresh();
        $this->assertSame('walkover', $match->status);
        $this->assertSame($match->entrant2_id, $match->winner_entrant_id);
        $this->assertSame($match->entrant1_id, $match->loser_entrant_id);
    }

    public function test_cannot_undo_after_the_match_is_complete(): void
    {
        [$match] = $this->readyMatch(bestOf: 1, pointsToWin: 11);
        $scoring = app(MatchScoringService::class);

        $scoring->startMatch($match, $match->entrant1_id);
        for ($p = 1; $p <= 11; $p++) {
            $scoring->recordPoint($match, $match->entrant1_id);
        }

        $this->assertSame('completed', $match->fresh()->status);

        $this->expectException(\RuntimeException::class);
        $scoring->undoLastPoint($match);
    }
}
