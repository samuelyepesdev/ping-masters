<?php

namespace App\Services\Pdf;

use App\Models\GeneratedDocument;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\StandingsService;
use App\Services\Pdf\Concerns\FormatsEntrantNames;
use Barryvdh\DomPDF\Facade\Pdf;

class BracketPdfService
{
    use FormatsEntrantNames;

    public function __construct(private readonly StandingsService $standings) {}

    public function build(TournamentDivision $division, ?User $generatedBy = null)
    {
        $division->load(['tournament', 'groups' => fn ($q) => $q->orderBy('display_order')]);

        $matches = $division->matches()
            ->with(['entrant1.registration.player.user', 'entrant2.registration.player.user', 'round', 'group'])
            ->orderBy('match_number')
            ->get();

        $rounds = $matches
            ->whereNull('tournament_group_id')
            ->groupBy(fn (TournamentMatch $m) => $m->round_id)
            ->map(function ($roundMatches) {
                $round = $roundMatches->first()->round;

                return [
                    'title' => $round ? ($round->name ?? "Ronda {$round->round_number}") : 'Ronda',
                    'stage_order' => $round?->round_number ?? 0,
                    'matches' => $roundMatches->map(fn (TournamentMatch $m) => $this->matchRow($m))->values(),
                ];
            })
            ->sortBy('stage_order')
            ->values();

        $groups = $division->groups->map(function ($group) use ($matches) {
            return [
                'name' => $group->name,
                'standings' => $this->standingsRows($this->standings->standingsForGroup($group)),
                'matches' => $matches->where('tournament_group_id', $group->id)->map(fn (TournamentMatch $m) => $this->matchRow($m))->values(),
            ];
        });

        $divisionStandings = in_array($division->format, ['round_robin', 'swiss'], true)
            ? $this->standingsRows($this->standings->standingsForDivision($division))
            : null;

        $pdf = Pdf::loadView('pdf.bracket', [
            'tournament' => $division->tournament,
            'division' => $division,
            'rounds' => $rounds,
            'groups' => $groups,
            'divisionStandings' => $divisionStandings,
        ])->setPaper('a4', 'portrait');

        GeneratedDocument::create([
            'type' => 'bracket',
            'documentable_type' => TournamentDivision::class,
            'documentable_id' => $division->id,
            'generated_by' => $generatedBy?->id,
            'title' => "Llave — {$division->tournament->name} / {$division->name}",
        ]);

        return $pdf;
    }

    private function matchRow(TournamentMatch $match): array
    {
        return [
            'entrant1' => $this->entrantLabel($match->entrant1, $match->entrant1_is_bye),
            'entrant2' => $this->entrantLabel($match->entrant2, $match->entrant2_is_bye),
            'score' => $match->score_summary,
            'winner' => match (true) {
                $match->winner_entrant_id === null => null,
                $match->winner_entrant_id === $match->entrant1_id => 1,
                default => 2,
            },
        ];
    }

    private function standingsRows($standings): array
    {
        $entrantNames = [];

        return collect($standings)->map(function (array $row) use (&$entrantNames) {
            if (! isset($entrantNames[$row['entrant_id']])) {
                $entrant = TournamentRegistrationDivision::with('registration.player.user')->find($row['entrant_id']);
                $entrantNames[$row['entrant_id']] = $this->entrantLabel($entrant);
            }

            return [...$row, 'name' => $entrantNames[$row['entrant_id']]];
        })->values()->all();
    }
}
