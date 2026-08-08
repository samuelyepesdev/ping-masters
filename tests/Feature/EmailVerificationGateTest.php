<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function tournamentWithDivision(string $name = 'Copa Ping Masters'): Tournament
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $this->actingAs($organizer)->post(route('tournaments.store'), [
            'name' => $name,
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

        $tournament = Tournament::where('name', $name)->firstOrFail()->load('divisions');

        $this->app['auth']->forgetGuards();

        return $tournament;
    }

    public function test_registering_a_new_account_sends_the_email_verification_notification(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Nuevo Jugador',
            'email' => 'nuevo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'nuevo@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_an_authenticated_user_with_an_unverified_email_cannot_register_for_a_tournament(): void
    {
        $tournament = $this->tournamentWithDivision();
        $division = $tournament->divisions->first();

        $player = User::factory()->unverified()->create();

        $response = $this->actingAs($player)->post(route('public.tournaments.store', $tournament), [
            'divisions' => [['division_id' => $division->id]],
            'responses' => [],
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('tournament_registrations', 0);

        $this->actingAs($player)->get(route('public.tournaments.register', $tournament))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_guest_registration_grandfathers_the_account_creating_registration_but_blocks_a_second_one(): void
    {
        $tournamentA = $this->tournamentWithDivision('Copa A');
        $tournamentB = $this->tournamentWithDivision('Copa B');

        $divisionA = $tournamentA->divisions->first();
        $divisionB = $tournamentB->divisions->first();

        $firstResponse = $this->post(route('public.tournaments.store', $tournamentA), [
            'name' => 'Jugador Nuevo',
            'email' => 'jugador.nuevo@example.com',
            'divisions' => [['division_id' => $divisionA->id]],
            'responses' => [],
        ]);

        $firstResponse->assertRedirect(route('public.tournaments.show', $tournamentA));

        $user = User::where('email', 'jugador.nuevo@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertSame(1, TournamentRegistration::where('player_id', $user->player->id)->count());

        // Still unverified, same (now-authenticated) session, trying a second tournament.
        $secondResponse = $this->post(route('public.tournaments.store', $tournamentB), [
            'divisions' => [['division_id' => $divisionB->id]],
            'responses' => [],
        ]);

        $secondResponse->assertRedirect(route('verification.notice'));
        $this->assertSame(1, TournamentRegistration::where('player_id', $user->player->id)->count());
    }
}
