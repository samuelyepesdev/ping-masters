<?php

namespace App\Http\Controllers;

use App\Models\TournamentMatch;
use App\Models\TournamentRegistrationDivision;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefereeController extends Controller
{
    public function index(Request $request): Response
    {
        $matches = TournamentMatch::where('referee_id', $request->user()->id)
            ->with([
                'entrant1.registration.player.user',
                'entrant2.registration.player.user',
                'division.tournament',
                'round',
            ])
            ->orderByDesc('scheduled_at')
            ->get()
            ->map(fn (TournamentMatch $match) => [
                'id' => $match->id,
                'tournament_id' => $match->division->tournament_id,
                'tournament_name' => $match->division->tournament->name,
                'division_id' => $match->tournament_division_id,
                'division_name' => $match->division->name,
                'round_name' => $match->round?->name,
                'entrant1_name' => $this->entrantLabel($match->entrant1, $match->entrant1_is_bye),
                'entrant2_name' => $this->entrantLabel($match->entrant2, $match->entrant2_is_bye),
                'status' => $match->status,
                'table_number' => $match->table_number,
                'scheduled_at' => $match->scheduled_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('referee/index', [
            'upcoming' => $matches->whereIn('status', ['ready', 'in_progress'])->values(),
            'completed' => $matches->whereIn('status', ['completed', 'walkover'])->values(),
        ]);
    }

    private function entrantLabel(?TournamentRegistrationDivision $entrant, bool $isBye): string
    {
        if (! $entrant) {
            return $isBye ? 'BYE' : 'Por definir';
        }

        $name = $entrant->registration->player->user->name;

        return $entrant->partner_name ? "{$name} / {$entrant->partner_name}" : $name;
    }
}
