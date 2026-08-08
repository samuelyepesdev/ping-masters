<?php

namespace Tests\Feature;

use App\Models\GeneratedDocument;
use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\BracketGeneratorFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function drawnDivision(User $organizer, string $format, int $entrantCount, array $extra = []): TournamentDivision
    {
        $tournament = Tournament::create([
            'name' => 'Copa PDF',
            'slug' => 'copa-pdf-'.uniqid(),
            'created_by' => $organizer->id,
            'venue' => 'Polideportivo',
            'city' => 'Bogotá',
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

        app(BracketGeneratorFactory::class)->make($division)->generate($division);

        return $division;
    }

    public function test_bracket_pdf_renders_for_every_format(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $cases = [
            ['single_elimination', 4, []],
            ['double_elimination', 4, []],
            ['round_robin', 4, []],
            ['swiss', 4, ['swiss_rounds' => 3]],
            ['group_knockout', 6, ['group_size' => 3, 'advance_per_group' => 2]],
        ];

        foreach ($cases as [$format, $count, $extra]) {
            $division = $this->drawnDivision($organizer, $format, $count, $extra);

            $response = $this->actingAs($organizer)->get(route('tournaments.divisions.pdf.bracket', [$division->tournament_id, $division->id]));

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }

        $this->assertSame(5, GeneratedDocument::where('type', 'bracket')->count());
    }

    public function test_scorecard_and_schedule_and_certificate_pdfs_render(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $division = $this->drawnDivision($organizer, 'single_elimination', 4);
        $tournament = $division->tournament;

        $round1 = Round::where('tournament_division_id', $division->id)->where('round_number', 1)->first();
        $match = TournamentMatch::where('round_id', $round1->id)->first();

        $this->actingAs($organizer)
            ->get(route('tournaments.divisions.matches.pdf.scorecard', [$tournament, $division, $match]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($organizer)
            ->get(route('tournaments.pdf.schedule', $tournament))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $entrant = TournamentRegistrationDivision::where('tournament_division_id', $division->id)->first();

        $this->actingAs($organizer)
            ->get(route('tournaments.divisions.entrants.pdf.certificate', [$tournament, $division, $entrant]).'?placement=campeon')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('generated_documents', ['type' => 'scorecard', 'documentable_id' => $match->id]);
        $this->assertDatabaseHas('generated_documents', ['type' => 'schedule', 'documentable_id' => $tournament->id]);
        $this->assertDatabaseHas('generated_documents', ['type' => 'certificate', 'documentable_id' => $entrant->id]);
    }

    public function test_outsider_organizer_cannot_download_someone_elses_pdfs(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');
        $division = $this->drawnDivision($organizer, 'single_elimination', 4);

        $outsider = User::factory()->create();
        $outsider->assignRole('organizer');

        $this->actingAs($outsider)
            ->get(route('tournaments.divisions.pdf.bracket', [$division->tournament_id, $division->id]))
            ->assertForbidden();
    }
}
