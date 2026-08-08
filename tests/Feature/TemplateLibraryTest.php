<?php

namespace Tests\Feature;

use App\Models\DivisionTemplate;
use App\Models\FormTemplate;
use App\Models\Tournament;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateLibraryTest extends TestCase
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

    public function test_organizer_can_create_and_list_their_own_division_template(): void
    {
        $organizer = $this->organizer();

        $this->actingAs($organizer)->post(route('templates.divisions.store'), [
            'name' => 'Individual Masculino Sub-18',
            'category_type' => 'singles',
            'gender_category' => 'male',
            'max_age' => 18,
            'format' => 'single_elimination',
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
        ])->assertRedirect(route('templates.divisions.index'));

        $this->assertDatabaseHas('division_templates', [
            'created_by' => $organizer->id,
            'name' => 'Individual Masculino Sub-18',
        ]);

        $this->actingAs($organizer)->get(route('templates.divisions.index'))->assertInertia(fn ($page) => $page
            ->component('templates/divisions/index')
            ->has('templates', 1)
        );
    }

    public function test_an_organizer_cannot_edit_another_organizers_division_template(): void
    {
        $owner = $this->organizer();
        $template = DivisionTemplate::create([
            'created_by' => $owner->id,
            'name' => 'Plantilla privada',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'single_elimination',
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
        ]);

        $outsider = $this->organizer();

        $this->actingAs($outsider)
            ->get(route('templates.divisions.edit', $template))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->delete(route('templates.divisions.destroy', $template))
            ->assertForbidden();
    }

    public function test_organizer_can_create_a_form_template_with_fields_and_edit_them(): void
    {
        $organizer = $this->organizer();

        $this->actingAs($organizer)->post(route('templates.forms.store'), [
            'name' => 'Formulario estándar',
            'description' => 'Campos comunes para todos los torneos',
            'fields' => [
                ['label' => 'Talla de camiseta', 'field_type' => 'select', 'options' => ['S', 'M', 'L'], 'is_required' => true],
                ['label' => 'Alergias', 'field_type' => 'textarea', 'is_required' => false],
            ],
        ])->assertRedirect(route('templates.forms.index'));

        $template = FormTemplate::where('name', 'Formulario estándar')->firstOrFail();
        $this->assertCount(2, $template->fields);

        // Edit: remove one field, change the other's label.
        $remainingField = $template->fields()->where('label', 'Talla de camiseta')->first();

        $this->actingAs($organizer)->put(route('templates.forms.update', $template), [
            'name' => 'Formulario estándar',
            'description' => 'Campos comunes',
            'fields' => [
                ['id' => $remainingField->id, 'label' => 'Talla de camiseta (S/M/L/XL)', 'field_type' => 'select', 'options' => ['S', 'M', 'L', 'XL'], 'is_required' => true],
            ],
        ])->assertRedirect(route('templates.forms.index'));

        $template->refresh();
        $this->assertCount(1, $template->fields);
        $this->assertSame('Talla de camiseta (S/M/L/XL)', $template->fields->first()->label);
    }

    public function test_creating_a_tournament_from_template_data_produces_an_independent_copy(): void
    {
        $organizer = $this->organizer();

        $divisionTemplate = DivisionTemplate::create([
            'created_by' => $organizer->id,
            'name' => 'Individual Abierto',
            'category_type' => 'singles',
            'gender_category' => 'open',
            'format' => 'single_elimination',
            'best_of' => 5,
            'points_to_win' => 11,
            'seed_by_rating' => true,
        ]);

        // Confirm the create page actually exposes this organizer's templates for the wizard to use.
        $this->actingAs($organizer)->get(route('tournaments.create'))->assertInertia(fn ($page) => $page
            ->component('tournaments/create')
            ->has('divisionTemplates', 1)
            ->where('divisionTemplates.0.name', 'Individual Abierto')
        );

        // Simulate the frontend copying the template's fields into a new tournament's division payload.
        $this->actingAs($organizer)->post(route('tournaments.store'), [
            'name' => 'Copa desde plantilla',
            'status' => 'registration_open',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'divisions' => [[
                'name' => $divisionTemplate->name,
                'category_type' => $divisionTemplate->category_type,
                'gender_category' => $divisionTemplate->gender_category,
                'format' => $divisionTemplate->format,
                'best_of' => $divisionTemplate->best_of,
                'points_to_win' => $divisionTemplate->points_to_win,
                'seed_by_rating' => $divisionTemplate->seed_by_rating,
            ]],
        ])->assertRedirect();

        $tournament = Tournament::where('name', 'Copa desde plantilla')->firstOrFail();
        $division = $tournament->divisions()->firstOrFail();

        $this->assertSame('Individual Abierto', $division->name);

        // The copy is fully independent: deleting the template must not touch the tournament's division.
        $divisionTemplate->delete();
        $this->assertDatabaseHas('tournament_divisions', ['id' => $division->id, 'name' => 'Individual Abierto']);
    }
}
