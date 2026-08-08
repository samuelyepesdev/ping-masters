<?php

namespace App\Services\Pdf;

use App\Models\GeneratedDocument;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Pdf\Concerns\FormatsEntrantNames;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificatePdfService
{
    use FormatsEntrantNames;

    public function build(TournamentRegistrationDivision $entrant, string $placement = 'Participante', ?User $generatedBy = null)
    {
        $entrant->load(['division.tournament', 'registration.player.user']);
        $division = $entrant->division;
        $tournament = $division->tournament;

        $pdf = Pdf::loadView('pdf.certificate', [
            'tournament' => $tournament,
            'division' => $division,
            'name' => $this->entrantLabel($entrant),
            'placement' => $placement,
            'date' => $tournament->end_date,
        ])->setPaper('a4', 'landscape');

        GeneratedDocument::create([
            'type' => 'certificate',
            'documentable_type' => TournamentRegistrationDivision::class,
            'documentable_id' => $entrant->id,
            'generated_by' => $generatedBy?->id,
            'title' => "Certificado — {$tournament->name} / {$division->name}",
        ]);

        return $pdf;
    }
}
