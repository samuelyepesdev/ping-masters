<?php

namespace App\Services\Pdf;

use App\Models\GeneratedDocument;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\Pdf\Concerns\FormatsEntrantNames;
use Barryvdh\DomPDF\Facade\Pdf;

class ScorecardPdfService
{
    use FormatsEntrantNames;

    public function build(TournamentMatch $match, ?User $generatedBy = null)
    {
        $match->load(['entrant1.registration.player.user', 'entrant2.registration.player.user', 'division.tournament', 'round', 'games']);

        $games = $match->games->map(fn ($g) => [
            'game_number' => $g->game_number,
            'entrant1_points' => $g->entrant1_points,
            'entrant2_points' => $g->entrant2_points,
        ]);

        $blankRowsNeeded = max(0, $match->division->best_of - $games->count());

        $pdf = Pdf::loadView('pdf.scorecard', [
            'tournament' => $match->division->tournament,
            'division' => $match->division,
            'match' => $match,
            'entrant1' => $this->entrantLabel($match->entrant1, $match->entrant1_is_bye),
            'entrant2' => $this->entrantLabel($match->entrant2, $match->entrant2_is_bye),
            'games' => $games,
            'blankRowsNeeded' => $blankRowsNeeded,
        ])->setPaper('a4', 'portrait');

        GeneratedDocument::create([
            'type' => 'scorecard',
            'documentable_type' => TournamentMatch::class,
            'documentable_id' => $match->id,
            'generated_by' => $generatedBy?->id,
            'title' => "Planilla — {$match->division->tournament->name} / {$match->division->name}",
        ]);

        return $pdf;
    }
}
