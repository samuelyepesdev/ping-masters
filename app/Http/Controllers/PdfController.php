<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistrationDivision;
use App\Services\Pdf\BracketPdfService;
use App\Services\Pdf\CertificatePdfService;
use App\Services\Pdf\SchedulePdfService;
use App\Services\Pdf\ScorecardPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PdfController extends Controller
{
    public function bracket(Tournament $tournament, TournamentDivision $division, BracketPdfService $service): Response
    {
        $this->authorize('view', $tournament);
        abort_if($division->tournament_id !== $tournament->id, 404);

        $pdf = $service->build($division, request()->user());

        return $pdf->stream("llave-{$division->id}.pdf");
    }

    public function scorecard(Tournament $tournament, TournamentDivision $division, TournamentMatch $match, ScorecardPdfService $service): Response
    {
        $this->authorize('view', $tournament);
        abort_if($division->tournament_id !== $tournament->id, 404);
        abort_if($match->tournament_division_id !== $division->id, 404);

        $pdf = $service->build($match, request()->user());

        return $pdf->stream("planilla-{$match->id}.pdf");
    }

    public function schedule(Tournament $tournament, SchedulePdfService $service): Response
    {
        $this->authorize('view', $tournament);

        $pdf = $service->build($tournament, request()->user());

        return $pdf->stream("cronograma-{$tournament->id}.pdf");
    }

    public function certificate(
        Request $request,
        Tournament $tournament,
        TournamentDivision $division,
        TournamentRegistrationDivision $entrant,
        CertificatePdfService $service,
    ): Response {
        $this->authorize('view', $tournament);
        abort_if($division->tournament_id !== $tournament->id, 404);
        abort_if($entrant->tournament_division_id !== $division->id, 404);

        $placement = $request->query('placement') === 'campeon' ? 'Campeón' : 'Participante';

        $pdf = $service->build($entrant, $placement, $request->user());

        return $pdf->stream("certificado-{$entrant->id}.pdf");
    }
}
