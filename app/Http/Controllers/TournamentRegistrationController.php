<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Illuminate\Http\Request;

class TournamentRegistrationController extends Controller
{
    public function update(Request $request, Tournament $tournament, TournamentRegistration $registration)
    {
        $this->authorize('update', $tournament);

        abort_if($registration->tournament_id !== $tournament->id, 404);

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,waitlisted,cancelled',
            'review_notes' => 'nullable|string',
        ]);

        $registration->update([
            'status' => $validated['status'],
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Inscripción actualizada.');
    }
}
