<?php

namespace Tests\Feature;

use App\Models\DivisionTemplate;
use App\Models\FormTemplate;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentCreationPrerequisitesTest extends TestCase
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

    public function test_tournament_create_page_reports_both_templates_missing_for_a_brand_new_organizer(): void
    {
        $organizer = $this->organizer();

        $this->actingAs($organizer)->get(route('tournaments.create'))->assertInertia(fn ($page) => $page
            ->component('tournaments/create')
            ->has('missingPrerequisites', 2)
            ->where('missingPrerequisites.0.satisfied', false)
        );
    }

    public function test_tournament_create_page_reports_no_missing_prerequisites_once_both_template_types_exist(): void
    {
        $organizer = $this->organizer();

        DivisionTemplate::create([
            'created_by' => $organizer->id,
            'name' => 'Individual Abierto',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'single_elimination',
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
        ]);

        FormTemplate::create([
            'created_by' => $organizer->id,
            'name' => 'Formulario estándar',
        ]);

        $this->actingAs($organizer)->get(route('tournaments.create'))->assertInertia(fn ($page) => $page
            ->component('tournaments/create')
            ->has('missingPrerequisites', 0)
        );
    }

    public function test_creating_a_division_template_honors_redirect_to_and_sends_the_user_back(): void
    {
        $organizer = $this->organizer();

        $this->actingAs($organizer)->post(route('templates.divisions.store'), [
            'name' => 'Individual Abierto',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'single_elimination',
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
            'redirect_to' => '/tournaments/create',
        ])->assertRedirect('/tournaments/create');
    }

    public function test_redirect_to_is_ignored_if_it_does_not_point_to_a_local_path(): void
    {
        $organizer = $this->organizer();

        $this->actingAs($organizer)->post(route('templates.divisions.store'), [
            'name' => 'Individual Abierto',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'single_elimination',
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
            'redirect_to' => '//evil.example.com',
        ])->assertRedirect(route('templates.divisions.index'));
    }
}
