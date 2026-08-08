<?php

namespace Tests\Feature;

use App\Mail\TournamentRegistrationConfirmation;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestTournamentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function tournamentWithDivision(): Tournament
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

        // actingAs() leaves the organizer resolved on the auth guard for the rest of the test;
        // clear it so the subsequent guest request is genuinely unauthenticated.
        $this->app['auth']->forgetGuards();

        return $tournament;
    }

    public function test_guest_can_register_and_gets_an_account_created_with_a_confirmation_email(): void
    {
        Mail::fake();

        $tournament = $this->tournamentWithDivision();
        $division = $tournament->divisions->first();

        $response = $this->post(route('public.tournaments.store', $tournament), [
            'name' => 'Nueva Jugadora',
            'email' => 'nueva@example.com',
            'phone' => '3001234567',
            'divisions' => [
                ['division_id' => $division->id],
            ],
            'responses' => [],
        ]);

        $response->assertRedirect(route('public.tournaments.show', $tournament));

        $user = User::where('email', 'nueva@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('player'));
        $this->assertNotNull($user->player);
        $this->assertSame('3001234567', $user->phone);
        $this->assertAuthenticatedAs($user);

        $registration = TournamentRegistration::firstOrFail();
        $this->assertSame($user->player->id, $registration->player_id);

        Mail::assertSent(TournamentRegistrationConfirmation::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->setPasswordUrl !== null;
        });
    }

    public function test_guest_registering_with_an_email_that_already_has_an_account_is_rejected(): void
    {
        Mail::fake();

        $tournament = $this->tournamentWithDivision();
        $division = $tournament->divisions->first();

        User::factory()->create(['email' => 'existente@example.com']);

        $response = $this->post(route('public.tournaments.store', $tournament), [
            'name' => 'Quien Sea',
            'email' => 'existente@example.com',
            'divisions' => [
                ['division_id' => $division->id],
            ],
            'responses' => [],
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('tournament_registrations', 0);
        Mail::assertNothingSent();
    }

    public function test_already_authenticated_user_registers_without_guest_fields_and_still_gets_confirmation_email(): void
    {
        Mail::fake();

        $tournament = $this->tournamentWithDivision();
        $division = $tournament->divisions->first();
        $player = User::factory()->create();

        $response = $this->actingAs($player)->post(route('public.tournaments.store', $tournament), [
            'divisions' => [
                ['division_id' => $division->id],
            ],
            'responses' => [],
        ]);

        $response->assertRedirect(route('public.tournaments.show', $tournament));

        Mail::assertSent(TournamentRegistrationConfirmation::class, function ($mail) use ($player) {
            return $mail->hasTo($player->email) && $mail->setPasswordUrl === null;
        });
    }
}
