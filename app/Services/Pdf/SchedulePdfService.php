<?php

namespace App\Services\Pdf;

use App\Models\GeneratedDocument;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\Pdf\Concerns\FormatsEntrantNames;
use Barryvdh\DomPDF\Facade\Pdf;

class SchedulePdfService
{
    use FormatsEntrantNames;

    public function build(Tournament $tournament, ?User $generatedBy = null)
    {
        $tournament->load(['divisions' => fn ($q) => $q->orderBy('display_order')]);

        $divisions = $tournament->divisions->map(function ($division) {
            $matches = $division->matches()
                ->with(['entrant1.registration.player.user', 'entrant2.registration.player.user', 'round', 'group'])
                ->orderBy('match_number')
                ->get()
                ->map(fn (TournamentMatch $m) => [
                    'round' => $m->round?->name ?? ($m->group?->name ?? '—'),
                    'entrant1' => $this->entrantLabel($m->entrant1, $m->entrant1_is_bye),
                    'entrant2' => $this->entrantLabel($m->entrant2, $m->entrant2_is_bye),
                    'table_number' => $m->table_number,
                    'scheduled_at' => $m->scheduled_at?->format('d/m H:i'),
                    'status' => $m->status,
                ]);

            return [
                'name' => $division->name,
                'matches' => $matches,
            ];
        });

        $pdf = Pdf::loadView('pdf.schedule', [
            'tournament' => $tournament,
            'divisions' => $divisions,
        ])->setPaper('a4', 'landscape');

        GeneratedDocument::create([
            'type' => 'schedule',
            'documentable_type' => Tournament::class,
            'documentable_id' => $tournament->id,
            'generated_by' => $generatedBy?->id,
            'title' => "Cronograma — {$tournament->name}",
        ]);

        return $pdf;
    }
}
