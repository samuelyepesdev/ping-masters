<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksPrerequisites;
use App\Models\DivisionTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DivisionTemplateController extends Controller
{
    use ChecksPrerequisites;

    public function index(Request $request): Response
    {
        $this->authorizeOrganizer($request);

        $query = DivisionTemplate::orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('created_by', $request->user()->id);
        }

        return Inertia::render('templates/divisions/index', [
            'templates' => $query->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeOrganizer($request);

        return Inertia::render('templates/divisions/create', [
            'redirectTo' => $request->query('redirect_to'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeOrganizer($request);

        $validated = $this->validateTemplate($request);

        $template = DivisionTemplate::create([...$validated, 'created_by' => $request->user()->id]);

        return $this->redirectAfterSave($request, route('templates.divisions.index'), "Plantilla «{$template->name}» creada.");
    }

    public function edit(Request $request, DivisionTemplate $divisionTemplate): Response
    {
        $this->authorizeOwnership($request, $divisionTemplate);

        return Inertia::render('templates/divisions/edit', [
            'template' => $divisionTemplate,
        ]);
    }

    public function update(Request $request, DivisionTemplate $divisionTemplate)
    {
        $this->authorizeOwnership($request, $divisionTemplate);

        $divisionTemplate->update($this->validateTemplate($request));

        return redirect()->route('templates.divisions.index')->with('success', 'Plantilla actualizada.');
    }

    public function destroy(Request $request, DivisionTemplate $divisionTemplate)
    {
        $this->authorizeOwnership($request, $divisionTemplate);

        $divisionTemplate->delete();

        return back()->with('success', 'Plantilla eliminada.');
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'category_type' => 'required|in:singles,doubles,team',
            'gender_category' => 'required|in:open,male,female,mixed',
            'min_age' => 'nullable|integer|min:0|max:120',
            'max_age' => 'nullable|integer|min:0|max:120',
            'format' => 'required|in:single_elimination,double_elimination,round_robin,swiss,group_knockout',
            'best_of' => 'required|integer|in:5,7',
            'points_to_win' => 'required|integer|min:1|max:99',
            'group_size' => 'nullable|integer|min:3|max:16',
            'advance_per_group' => 'nullable|integer|min:1|max:8',
            'swiss_rounds' => 'nullable|integer|min:1|max:20',
            'max_participants' => 'nullable|integer|min:2',
            'seed_by_rating' => 'boolean',
        ]);
    }

    private function authorizeOrganizer(Request $request): void
    {
        abort_unless($request->user()->isOrganizer() || $request->user()->isSuperAdmin(), 403);
    }

    private function authorizeOwnership(Request $request, DivisionTemplate $template): void
    {
        abort_unless($request->user()->isSuperAdmin() || $template->created_by === $request->user()->id, 403);
    }
}
