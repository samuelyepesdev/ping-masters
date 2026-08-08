<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_organizer_can_create_a_tournament_with_divisions_and_registration_fields(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $response = $this->actingAs($organizer)->post(route('tournaments.store'), [
            'name' => 'Copa Ping Masters',
            'description' => 'Torneo de prueba',
            'venue' => 'Polideportivo Central',
            'city' => 'Bogotá',
            'status' => 'registration_open',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
            'registration_opens_at' => now()->subDay()->toDateString(),
            'registration_closes_at' => now()->addDays(9)->toDateString(),
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
                [
                    'name' => 'Dobles Mixto',
                    'category_type' => 'doubles',
                    'gender_category' => 'mixed',
                    'format' => 'round_robin',
                    'best_of' => 5,
                    'points_to_win' => 11,
                    'seed_by_rating' => true,
                ],
            ],
            'registration_fields' => [
                [
                    'label' => 'Talla de camiseta',
                    'field_type' => 'select',
                    'options' => ['S', 'M', 'L', 'XL'],
                    'is_required' => true,
                ],
                [
                    'label' => 'Alergias o condiciones médicas',
                    'field_type' => 'textarea',
                    'is_required' => false,
                ],
            ],
        ]);

        $tournament = Tournament::firstOrFail();
        $response->assertRedirect(route('tournaments.show', $tournament));

        $this->assertDatabaseCount('tournaments', 1);
        $this->assertDatabaseCount('tournament_divisions', 2);
        $this->assertDatabaseCount('tournament_registration_fields', 2);
        $this->assertSame('copa-ping-masters', $tournament->slug);

        $this->actingAs($organizer)->get(route('tournaments.index'))->assertOk();
        $this->actingAs($organizer)->get(route('tournaments.show', $tournament))->assertOk();
        $this->actingAs($organizer)->get(route('tournaments.edit', $tournament))->assertOk();
        $this->actingAs($organizer)->get(route('public.tournaments.index'))->assertOk();
        $this->actingAs($organizer)->get(route('public.tournaments.show', $tournament))->assertOk();

        $outsider = User::factory()->create();
        $outsider->assignRole('organizer');
        $this->actingAs($outsider)->get(route('tournaments.show', $tournament))->assertForbidden();
    }

    public function test_organizer_can_delete_their_own_tournament_but_not_someone_elses(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $this->actingAs($organizer)->post(route('tournaments.store'), [
            'name' => 'Copa a Eliminar',
            'status' => 'draft',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
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

        $outsider = User::factory()->create();
        $outsider->assignRole('organizer');
        $this->actingAs($outsider)->delete(route('tournaments.destroy', $tournament))->assertForbidden();
        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id]);

        $this->actingAs($organizer)->delete(route('tournaments.destroy', $tournament))
            ->assertRedirect(route('tournaments.index'));
        $this->assertDatabaseMissing('tournaments', ['id' => $tournament->id]);
    }

    public function test_player_can_register_for_a_tournament_and_organizer_can_review_it(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $this->actingAs($organizer)->post(route('tournaments.store'), [
            'name' => 'Copa Ping Masters',
            'status' => 'registration_open',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
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
            'registration_fields' => [
                [
                    'label' => 'Talla de camiseta',
                    'field_type' => 'select',
                    'options' => ['S', 'M', 'L'],
                    'is_required' => true,
                ],
            ],
        ]);

        $tournament = Tournament::firstOrFail()->load('divisions', 'registrationFields');
        $division = $tournament->divisions->first();
        $field = $tournament->registrationFields->first();

        $player = User::factory()->create();

        $registerResponse = $this->actingAs($player)->get(route('public.tournaments.register', $tournament));
        $registerResponse->assertOk();

        $storeResponse = $this->actingAs($player)->post(route('public.tournaments.store', $tournament), [
            'divisions' => [
                ['division_id' => $division->id],
            ],
            'responses' => [
                $field->id => 'M',
            ],
        ]);

        $storeResponse->assertRedirect(route('public.tournaments.show', $tournament));

        $player->refresh();
        $this->assertTrue($player->hasRole('player'));
        $this->assertNotNull($player->player);

        $registration = TournamentRegistration::firstOrFail();
        $this->assertSame('pending', $registration->status);
        $this->assertSame($player->player->id, $registration->player_id);
        $this->assertDatabaseHas('tournament_registration_divisions', [
            'tournament_registration_id' => $registration->id,
            'tournament_division_id' => $division->id,
        ]);
        $this->assertDatabaseHas('tournament_registration_responses', [
            'tournament_registration_id' => $registration->id,
            'tournament_registration_field_id' => $field->id,
            'value' => 'M',
        ]);

        $reviewResponse = $this->actingAs($organizer)->patch(
            route('tournaments.registrations.update', [$tournament, $registration]),
            ['status' => 'approved'],
        );
        $reviewResponse->assertRedirect();

        $registration->refresh();
        $this->assertSame('approved', $registration->status);
        $this->assertSame($organizer->id, $registration->reviewed_by);

        $this->actingAs($organizer)->get(route('tournaments.show', $tournament))
            ->assertInertia(fn ($page) => $page
                ->where('registrations.data.0.responses.0.value', 'M')
                ->where('registrations.data.0.responses.0.field.label', 'Talla de camiseta')
            );
    }

    public function test_player_cannot_register_twice_for_the_same_tournament(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $this->actingAs($organizer)->post(route('tournaments.store'), [
            'name' => 'Copa Ping Masters',
            'status' => 'registration_open',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
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

        $tournament = Tournament::firstOrFail()->load('divisions');
        $division = $tournament->divisions->first();
        $player = User::factory()->create();

        $payload = [
            'divisions' => [['division_id' => $division->id]],
            'responses' => [],
        ];

        $this->actingAs($player)->post(route('public.tournaments.store', $tournament), $payload)
            ->assertRedirect(route('public.tournaments.show', $tournament));

        $this->actingAs($player)->post(route('public.tournaments.store', $tournament), $payload)
            ->assertSessionHas('error');

        $this->assertDatabaseCount('tournament_registrations', 1);
    }
}
