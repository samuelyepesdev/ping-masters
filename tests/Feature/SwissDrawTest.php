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
use App\Services\Brackets\SwissGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwissDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_round_one_pairs_top_half_against_bottom_half_and_round_two_avoids_rematches(): void
    {
        $organizer = User::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Copa suiza',
            'slug' => 'copa-suiza',
            'created_by' => $organizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        $division = $tournament->divisions()->create([
            'name' => 'Individual',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'swiss',
            'best_of' => 5,
            'points_to_win' => 11,
            'swiss_rounds' => 3,
            'seed_by_rating' => true,
        ]);

        $entrants = [];
        foreach (range(1, 6) as $i) {
            $user = User::factory()->create();
            $player = Player::create(['user_id' => $user->id, 'rating_current' => 2000 - $i * 10]);
            $registration = TournamentRegistration::create([
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'status' => 'approved',
                'submitted_at' => now(),
            ]);
            $entrants[] = TournamentRegistrationDivision::create([
                'tournament_registration_id' => $registration->id,
                'tournament_division_id' => $division->id,
                'seed_rating_snapshot' => $player->rating_current,
            ]);
        }

        $generator = app(SwissGenerator::class);
        $advancement = app(BracketAdvancementService::class);

        $generator->generate($division);

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $round1Matches = TournamentMatch::where('round_id', $round1->id)->get();
        $this->assertCount(3, $round1Matches);

        // Top half (seeds 1-3) must be paired against bottom half (seeds 4-6).
        $topIds = collect(array_slice($entrants, 0, 3))->pluck('id');
        $bottomIds = collect(array_slice($entrants, 3, 3))->pluck('id');

        foreach ($round1Matches as $match) {
            $inTop = $topIds->contains($match->entrant1_id) || $topIds->contains($match->entrant2_id);
            $inBottom = $bottomIds->contains($match->entrant1_id) || $bottomIds->contains($match->entrant2_id);
            $this->assertTrue($inTop && $inBottom);
        }

        // Everyone wins their match against the lower seed (deterministic outcome for the test).
        foreach ($round1Matches as $match) {
            $winner = $topIds->contains($match->entrant1_id) ? $match->entrant1_id : $match->entrant2_id;
            $advancement->recordResult($match, $winner);
        }

        $generator->generateNextRound($division);

        $round2 = Round::where('tournament_division_id', $division->id)->where('round_number', 2)->first();
        $this->assertNotNull($round2);
        $round2Matches = TournamentMatch::where('round_id', $round2->id)->get();

        $playedInRound1 = $round1Matches->map(fn (TournamentMatch $m) => collect([$m->entrant1_id, $m->entrant2_id])->sort()->implode('-'));

        foreach ($round2Matches as $match) {
            $key = collect([$match->entrant1_id, $match->entrant2_id])->sort()->implode('-');
            $this->assertFalse($playedInRound1->contains($key), 'Round 2 replayed a round 1 pairing: '.$key);
        }
    }
}
