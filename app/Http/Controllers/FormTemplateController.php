<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksPrerequisites;
use App\Models\FormTemplate;
use App\Models\TournamentRegistrationField;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormTemplateController extends Controller
{
    use ChecksPrerequisites;

    public function index(Request $request): Response
    {
        $this->authorizeOrganizer($request);

        $query = FormTemplate::withCount('fields')->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('created_by', $request->user()->id);
        }

        return Inertia::render('templates/forms/index', [
            'templates' => $query->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeOrganizer($request);

        return Inertia::render('templates/forms/create', [
            'redirectTo' => $request->query('redirect_to'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeOrganizer($request);

        $validated = $this->validateTemplate($request);

        $template = FormTemplate::create([
            'created_by' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        foreach ($validated['fields'] ?? [] as $index => $field) {
            $template->fields()->create($this->fieldFields($field, $index));
        }

        return $this->redirectAfterSave($request, route('templates.forms.index'), "Plantilla «{$template->name}» creada.");
    }

    public function edit(Request $request, FormTemplate $formTemplate): Response
    {
        $this->authorizeOwnership($request, $formTemplate);

        $formTemplate->load('fields');

        return Inertia::render('templates/forms/edit', [
            'template' => $formTemplate,
        ]);
    }

    public function update(Request $request, FormTemplate $formTemplate)
    {
        $this->authorizeOwnership($request, $formTemplate);

        $validated = $this->validateTemplate($request);

        $formTemplate->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $existingIds = $formTemplate->fields()->pluck('id')->toArray();
        $incomingIds = collect($validated['fields'] ?? [])->pluck('id')->filter()->toArray();
        $toDelete = array_diff($existingIds, $incomingIds);

        if ($toDelete !== []) {
            $formTemplate->fields()->whereIn('id', $toDelete)->delete();
        }

        foreach ($validated['fields'] ?? [] as $index => $field) {
            $data = $this->fieldFields($field, $index);

            if (! empty($field['id'])) {
                $formTemplate->fields()->where('id', $field['id'])->update($data);
            } else {
                $formTemplate->fields()->create($data);
            }
        }

        return redirect()->route('templates.forms.index')->with('success', 'Plantilla actualizada.');
    }

    public function destroy(Request $request, FormTemplate $formTemplate)
    {
        $this->authorizeOwnership($request, $formTemplate);

        $formTemplate->delete();

        return back()->with('success', 'Plantilla eliminada.');
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'fields' => 'nullable|array',
            'fields.*.id' => 'nullable|integer',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.field_type' => 'required|in:'.implode(',', TournamentRegistrationField::TYPES),
            'fields.*.options' => 'nullable|array',
            'fields.*.options.*' => 'string|max:255',
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.help_text' => 'nullable|string',
            'fields.*.is_required' => 'boolean',
        ]);
    }

    private function fieldFields(array $field, int $index): array
    {
        return [
            'label' => $field['label'],
            'field_type' => $field['field_type'],
            'options' => in_array($field['field_type'], TournamentRegistrationField::CHOICE_TYPES, true)
                ? ($field['options'] ?? [])
                : null,
            'placeholder' => $field['placeholder'] ?? null,
            'help_text' => $field['help_text'] ?? null,
            'is_required' => $field['is_required'] ?? true,
            'display_order' => $index,
        ];
    }

    private function authorizeOrganizer(Request $request): void
    {
        abort_unless($request->user()->isOrganizer() || $request->user()->isSuperAdmin(), 403);
    }

    private function authorizeOwnership(Request $request, FormTemplate $template): void
    {
        abort_unless($request->user()->isSuperAdmin() || $template->created_by === $request->user()->id, 403);
    }
}
