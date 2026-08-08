import { MatchScoringConsole } from '@/components/matches/match-scoring-console';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type MatchScoreState, type Tournament, type TournamentDivision } from '@/types';
import { Head } from '@inertiajs/react';

export default function ScoringConsole({
    tournament,
    division,
    match,
}: {
    tournament: Tournament;
    division: TournamentDivision;
    match: MatchScoreState;
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Torneos', href: '/tournaments' },
        { title: tournament.name, href: `/tournaments/${tournament.id}` },
        { title: division.name, href: `/tournaments/${tournament.id}/divisions/${division.id}` },
        { title: 'Marcador', href: '#' },
    ];

    const routeArgs = [tournament.id, division.id, match.id];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Marcador en vivo" />
            <div className="p-4">
                <MatchScoringConsole
                    match={match}
                    routes={{
                        start: route('tournaments.divisions.matches.start', routeArgs),
                        point: route('tournaments.divisions.matches.point', routeArgs),
                        undo: route('tournaments.divisions.matches.undo', routeArgs),
                        forfeit: route('tournaments.divisions.matches.walkover', routeArgs),
                    }}
                    backUrl={route('tournaments.divisions.show', [tournament.id, division.id])}
                    backLabel="Volver a las llaves"
                />
            </div>
        </AppLayout>
    );
}
