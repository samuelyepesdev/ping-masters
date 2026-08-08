<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentCapacityAndVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function organizer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('organizer');

        return $user;
    }

    public function test_a_division_blocks_registration_once_it_reaches_its_own_capacity(): void
    {
        $organizer = $this->organizer();

        $this->actingAs($organizer)->post(route('tournaments.store'), [
            'name' => 'Copa Cupo Limitado',
            'status' => 'registration_open',
            'divisions' => [
                [
                    'name' => 'Individual Masculino',
                    'category_type' => 'singles',
                    'gender_category' => 'male',
                    'format' => 'single_elimination',
                    'best_of' => 5,
                    'points_to_win' => 11,
                    'max_participants' => 2,
                    'seed_by_rating' => true,
                ],
            ],
        ]);

        $tournament = Tournament::firstOrFail()->load('divisions');
        $division = $tournament->divisions->first();

        $firstPlayer = User::factory()->create();
        $this->actingAs($firstPlayer)->post(route('public.tournaments.store', $tournament), [
            'divisions' => [['division_id' => $division->id]],
            'responses' => [],
        ])->assertRedirect(route('public.tournaments.show', $tournament));

        $secondPlayer = User::factory()->create();
        $this->actingAs($secondPlayer)->post(route('public.tournaments.store', $tournament), [
            'divisions' => [['division_id' => $division->id]],
            'responses' => [],
        ])->assertRedirect(route('public.tournaments.show', $tournament));

        $thirdPlayer = User::factory()->create();
        $blockedResponse = $this->actingAs($thirdPlayer)->post(route('public.tournaments.store', $tournament), [
            'divisions' => [['division_id' => $division->id]],
            'responses' => [],
        ]);

        $blockedResponse->assertSessionHas('error');
        $this->assertStringContainsString('Inscripciones agotadas', session('error'));
        $this->assertDatabaseCount('tournament_registrations', 2);

        $registerPage = $this->actingAs($thirdPlayer)->get(route('public.tournaments.register', $tournament));
        $registerPage->assertInertia(fn ($page) => $page->where('tournament.divisions.0.is_full', true));
    }

    public function test_tournament_dates_can_be_left_as_tbd(): void
    {
        $organizer = $this->organizer();

        $response = $this->actingAs($organizer)->post(route('tournaments.store'), [
            'name' => 'Copa Fechas por Definir',
            'status' => 'draft',
            'start_date' => null,
            'end_date' => null,
            'divisions' => [
                [
                    'name' => 'Individual Masculino',
                    'category_type' => 'singles',
                    'gender_category' => 'male',
                    'format' => 'single_elimination',
                    'best_of' => 5,
                    'points_to_win' => 11,
                    'seed_by_rating' => true,
                ],
            ],
        ]);

        $tournament = Tournament::firstOrFail();
        $response->assertRedirect(route('tournaments.show', $tournament));
        $this->assertNull($tournament->start_date);
        $this->assertNull($tournament->end_date);
    }

    public function test_inactive_tournaments_are_hidden_from_the_public_but_visible_to_their_organizer(): void
    {
        $organizer = $this->organizer();

        $this->actingAs($organizer)->post(route('tournaments.store'), [
            'name' => 'Copa Inactiva',
            'status' => 'registration_open',
            'is_active' => false,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'divisions' => [
                [
                    'name' => 'Individual Masculino',
                    'category_type' => 'singles',
                    'gender_category' => 'male',
                    'format' => 'single_elimination',
                    'best_of' => 5,
                    'points_to_win' => 11,
                    'seed_by_rating' => true,
                ],
            ],
        ]);

        $tournament = Tournament::firstOrFail();
        $this->assertFalse($tournament->is_active);

        $this->get(route('public.tournaments.show', $tournament))->assertNotFound();
        $this->get(route('public.tournaments.index'))->assertInertia(fn ($page) => $page->has('tournaments.data', 0));

        $this->actingAs($organizer)->get(route('tournaments.show', $tournament))->assertOk();
    }
}
