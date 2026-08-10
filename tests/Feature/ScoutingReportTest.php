<?php

namespace Tests\Feature;

use App\Models\CasualMatch;
use App\Models\CasualMatchGame;
use App\Models\CasualMatchPoint;
use App\Models\MatchGame;
use App\Models\MatchPoint;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Scouting\ScoutingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoutingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_derives_deuce_decider_and_streak_stats_from_a_casual_match(): void
    {
        $player = Player::create(['user_id' => User::factory()->create()->id]);
        $opponent = Player::create(['user_id' => User::factory()->create(['name' => 'Rival Casual'])->id]);

        $match = CasualMatch::create([
            'code' => 'ABC123',
            'match_type' => 'friendly',
            'status' => 'completed',
            'best_of' => 3,
            'points_to_win' => 11,
            'creator_player_id' => $player->id,
            'opponent_player_id' => $opponent->id,
            'winner_player_id' => $player->id,
            'loser_player_id' => $opponent->id,
        ]);

        // Game 1: clean win, not deuce, not a decider — but has the longest scoring streak (9).
        $game1 = CasualMatchGame::create([
            'casual_match_id' => $match->id,
            'game_number' => 1,
            'creator_points' => 11,
            'opponent_points' => 2,
            'winner_player_id' => $player->id,
            'completed_at' => '2026-01-01 10:00:00',
        ]);
        $scorers = array_merge(array_fill(0, 9, $player->id), [$opponent->id]);
        $this->recordCasualPoints($match, $game1, $player, $scorers);

        // Game 2: deuce, opponent wins.
        $game2 = CasualMatchGame::create([
            'casual_match_id' => $match->id,
            'game_number' => 2,
            'creator_points' => 10,
            'opponent_points' => 12,
            'winner_player_id' => $opponent->id,
            'completed_at' => '2026-01-02 10:00:00',
        ]);
        $this->recordCasualPoints($match, $game2, $player, [$player->id]);

        // Game 3: the decider (game_number === best_of) — also deuce, player wins.
        $game3 = CasualMatchGame::create([
            'casual_match_id' => $match->id,
            'game_number' => 3,
            'creator_points' => 14,
            'opponent_points' => 12,
            'winner_player_id' => $player->id,
            'completed_at' => '2026-01-03 10:00:00',
        ]);
        $this->recordCasualPoints($match, $game3, $player, [$player->id, $player->id]);

        $report = app(ScoutingReportService::class)->forPlayer($player);

        $this->assertSame(3, $report['games_analyzed']);

        $this->assertSame(2, $report['deuce']['played']);
        $this->assertSame(1, $report['deuce']['won']);
        $this->assertSame(50, $report['deuce']['win_rate']);

        $this->assertSame(1, $report['decider']['played']);
        $this->assertSame(1, $report['decider']['won']);
        $this->assertSame(100, $report['decider']['win_rate']);

        $this->assertSame(9, $report['best_streak']['points']);
        $this->assertSame('Rival Casual', $report['best_streak']['opponent']);
        $this->assertSame('2026-01-01', $report['best_streak']['date']);
    }

    public function test_derives_stats_from_a_singles_tournament_match_and_ignores_doubles(): void
    {
        $organizer = User::factory()->create();
        $player = Player::create(['user_id' => User::factory()->create()->id]);
        $opponent = Player::create(['user_id' => User::factory()->create(['name' => 'Rival Torneo'])->id]);

        $tournament = Tournament::create([
            'name' => 'Copa scouting',
            'slug' => 'copa-scouting',
            'created_by' => $organizer->id,
            'status' => 'in_progress',
            'start_date' => now(),
            'end_date' => now()->addDay(),
        ]);

        $singles = $tournament->divisions()->create([
            'name' => 'Individual',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'single_elimination',
            'best_of' => 3,
            'points_to_win' => 11,
        ]);

        $registration = TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
            'status' => 'approved',
            'submitted_at' => now(),
        ]);
        $entrant = TournamentRegistrationDivision::create([
            'tournament_registration_id' => $registration->id,
            'tournament_division_id' => $singles->id,
        ]);

        $opponentRegistration = TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'player_id' => $opponent->id,
            'status' => 'approved',
            'submitted_at' => now(),
        ]);
        $opponentEntrant = TournamentRegistrationDivision::create([
            'tournament_registration_id' => $opponentRegistration->id,
            'tournament_division_id' => $singles->id,
        ]);

        $match = TournamentMatch::create([
            'tournament_division_id' => $singles->id,
            'entrant1_id' => $entrant->id,
            'entrant2_id' => $opponentEntrant->id,
            'winner_entrant_id' => $entrant->id,
            'loser_entrant_id' => $opponentEntrant->id,
            'status' => 'completed',
        ]);

        // Only game, and it's the decider (best_of 3 → game_number 3 is the maximum distance).
        $game = MatchGame::create([
            'match_id' => $match->id,
            'game_number' => 3,
            'entrant1_points' => 11,
            'entrant2_points' => 9,
            'winner_entrant_id' => $entrant->id,
            'completed_at' => '2026-02-01 10:00:00',
        ]);
        $this->recordTournamentPoints($match, $game, $entrant, [$entrant->id, $entrant->id, $entrant->id]);

        $report = app(ScoutingReportService::class)->forPlayer($player);

        $this->assertSame(1, $report['games_analyzed']);
        $this->assertSame(1, $report['decider']['played']);
        $this->assertSame(1, $report['decider']['won']);
        $this->assertSame(3, $report['best_streak']['points']);
        $this->assertSame('Rival Torneo', $report['best_streak']['opponent']);

        // A doubles division match must not contaminate a singles player's report.
        $doubles = $tournament->divisions()->create([
            'name' => 'Dobles',
            'category_type' => 'doubles',
            'gender_category' => 'open',
            'format' => 'single_elimination',
            'best_of' => 3,
            'points_to_win' => 11,
        ]);
        $doublesEntrant = TournamentRegistrationDivision::create([
            'tournament_registration_id' => $registration->id,
            'tournament_division_id' => $doubles->id,
            'partner_name' => 'Compañero',
        ]);
        $doublesOpponentEntrant = TournamentRegistrationDivision::create([
            'tournament_registration_id' => $opponentRegistration->id,
            'tournament_division_id' => $doubles->id,
            'partner_name' => 'Rival compañero',
        ]);
        $doublesMatch = TournamentMatch::create([
            'tournament_division_id' => $doubles->id,
            'entrant1_id' => $doublesEntrant->id,
            'entrant2_id' => $doublesOpponentEntrant->id,
            'winner_entrant_id' => $doublesEntrant->id,
            'loser_entrant_id' => $doublesOpponentEntrant->id,
            'status' => 'completed',
        ]);
        $doublesGame = MatchGame::create([
            'match_id' => $doublesMatch->id,
            'game_number' => 1,
            'entrant1_points' => 11,
            'entrant2_points' => 0,
            'winner_entrant_id' => $doublesEntrant->id,
            'completed_at' => '2026-02-02 10:00:00',
        ]);
        $this->recordTournamentPoints($doublesMatch, $doublesGame, $doublesEntrant, array_fill(0, 11, $doublesEntrant->id));

        $reportAfterDoubles = app(ScoutingReportService::class)->forPlayer($player);

        $this->assertSame(1, $reportAfterDoubles['games_analyzed'], 'the doubles match must be excluded');
    }

    /**
     * @param  int[]  $scorerIds
     */
    private function recordCasualPoints(CasualMatch $match, CasualMatchGame $game, Player $server, array $scorerIds): void
    {
        foreach ($scorerIds as $i => $scorerId) {
            CasualMatchPoint::create([
                'casual_match_id' => $match->id,
                'casual_match_game_id' => $game->id,
                'point_number' => $i + 1,
                'scoring_player_id' => $scorerId,
                'server_player_id' => $server->id,
                'creator_points_after' => 0,
                'opponent_points_after' => 0,
            ]);
        }
    }

    /**
     * @param  int[]  $scorerIds
     */
    private function recordTournamentPoints(TournamentMatch $match, MatchGame $game, TournamentRegistrationDivision $server, array $scorerIds): void
    {
        foreach ($scorerIds as $i => $scorerId) {
            MatchPoint::create([
                'match_id' => $match->id,
                'match_game_id' => $game->id,
                'point_number' => $i + 1,
                'scoring_entrant_id' => $scorerId,
                'server_entrant_id' => $server->id,
                'entrant1_points_after' => 0,
                'entrant2_points_after' => 0,
            ]);
        }
    }
}
