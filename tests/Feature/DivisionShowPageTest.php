<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\BracketGeneratorFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DivisionShowPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function makeDivision(User $organizer, string $format, int $entrantCount, array $extra = []): TournamentDivision
    {
        $tournament = Tournament::create([
            'name' => "Copa {$format}",
            'slug' => 'copa-'.str_replace('_', '-', $format).'-'.uniqid(),
            'created_by' => $organizer->id,
            'status' => 'registration_open',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        $division = $tournament->divisions()->create(array_merge([
            'name' => 'Individual',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => $format,
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
        ], $extra));

        for ($i = 1; $i <= $entrantCount; $i++) {
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

        return $division;
    }

    public function test_the_division_bracket_page_renders_for_every_format(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $cases = [
            ['single_elimination', 8, []],
            ['double_elimination', 8, []],
            ['round_robin', 5, []],
            ['swiss', 6, ['swiss_rounds' => 3]],
            ['group_knockout', 9, ['group_size' => 3, 'advance_per_group' => 2]],
        ];

        foreach ($cases as [$format, $count, $extra]) {
            $division = $this->makeDivision($organizer, $format, $count, $extra);

            // Before the draw: the page must render the "generate draw" prompt without error.
            $this->actingAs($organizer)
                ->get(route('tournaments.divisions.show', [$division->tournament_id, $division->id]))
                ->assertOk();

            app(BracketGeneratorFactory::class)->make($division)->generate($division);

            // After the draw: the page must render the bracket/standings view without error.
            $this->actingAs($organizer)
                ->get(route('tournaments.divisions.show', [$division->tournament_id, $division->id]))
                ->assertOk();
        }
    }
}
