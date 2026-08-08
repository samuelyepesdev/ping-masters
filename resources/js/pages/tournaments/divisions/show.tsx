import { BracketTree } from '@/components/tournaments/bracket-tree';
import { MatchCard } from '@/components/tournaments/match-card';
import { StandingsTable } from '@/components/tournaments/standings-table';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type BracketMatch, type StandingRow, type Tournament, type TournamentDivision } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Shuffle } from 'lucide-react';

const FORMAT_LABELS: Record<string, string> = {
    single_elimination: 'Eliminación directa',
    double_elimination: 'Doble eliminación',
    round_robin: 'Todos contra todos',
    swiss: 'Sistema suizo',
    group_knockout: 'Grupos + eliminación',
};

interface Props {
    tournament: Tournament;
    division: TournamentDivision;
    matches: BracketMatch[];
    groupsStandings: Record<number, StandingRow[]>;
    divisionStandings: StandingRow[] | null;
    swissRoundsGenerated: number;
}

export default function DivisionShow({ tournament, division, matches, groupsStandings, divisionStandings, swissRoundsGenerated }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Torneos', href: '/tournaments' },
        { title: tournament.name, href: `/tournaments/${tournament.id}` },
        { title: division.name, href: `/tournaments/${tournament.id}/divisions/${division.id}` },
    ];

    function generateDraw() {
        router.post(route('tournaments.divisions.draw', [tournament.id, division.id]), {}, { preserveScroll: true });
    }

    function generateNextSwissRound() {
        router.post(route('tournaments.divisions.swiss-next-round', [tournament.id, division.id]), {}, { preserveScroll: true });
    }

    const roundsByNumber = (roundMatches: BracketMatch[]) => {
        const numbers = [...new Set(roundMatches.map((m) => m.round_number ?? 0))].sort((a, b) => a - b);
        return numbers.map((n) => ({ number: n, matches: roundMatches.filter((m) => m.round_number === n) }));
    };

    const currentSwissRound = roundsByNumber(matches).at(-1);
    const swissRoundComplete = currentSwissRound?.matches.every((m) => m.status === 'completed' || m.status === 'walkover') ?? false;
    const canGenerateNextSwissRound =
        division.format === 'swiss' && division.status === 'drawn' && swissRoundComplete && swissRoundsGenerated < (division.swiss_rounds ?? 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={division.name} />
            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{division.name}</h1>
                        <p className="text-muted-foreground">
                            {FORMAT_LABELS[division.format]} · {tournament.name}
                        </p>
                    </div>

                    {division.status === 'pending_draw' && (
                        <Button onClick={generateDraw}>
                            <Shuffle className="size-4" />
                            Generar sorteo
                        </Button>
                    )}

                    {canGenerateNextSwissRound && <Button onClick={generateNextSwissRound}>Generar ronda {swissRoundsGenerated + 1}</Button>}
                </div>

                {division.status === 'pending_draw' && (
                    <Card className="border-dashed">
                        <CardContent className="py-10 text-center text-sm text-muted-foreground">
                            Todavía no se ha generado el sorteo para esta categoría.
                        </CardContent>
                    </Card>
                )}

                {division.status !== 'pending_draw' && (division.format === 'single_elimination' || division.format === 'double_elimination') && (
                    <BracketTree matches={matches} tournamentId={tournament.id} divisionId={division.id} canRecordResult />
                )}

                {division.status !== 'pending_draw' && (division.format === 'round_robin' || division.format === 'swiss') && (
                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                        <div className="space-y-6">
                            {roundsByNumber(matches).map(({ number, matches: roundMatches }) => (
                                <div key={number}>
                                    <h3 className="mb-3 text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                        {roundMatches[0]?.round_name ?? `Ronda ${number}`}
                                    </h3>
                                    <div className="flex flex-wrap gap-4">
                                        {roundMatches.map((match) => (
                                            <MatchCard
                                                key={match.id}
                                                match={match}
                                                canRecordResult
                                                resultRoute={route('tournaments.divisions.matches.result', [tournament.id, division.id, match.id])}
                                            />
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Tabla de posiciones</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <StandingsTable standings={divisionStandings ?? []} />
                            </CardContent>
                        </Card>
                    </div>
                )}

                {division.status !== 'pending_draw' && division.format === 'group_knockout' && (
                    <div className="space-y-10">
                        <div>
                            <h2 className="mb-4 text-lg font-semibold">Fase de grupos</h2>
                            <div className="grid gap-6 md:grid-cols-2">
                                {(division.groups ?? []).map((group) => (
                                    <Card key={group.id}>
                                        <CardHeader>
                                            <CardTitle className="text-base">{group.name}</CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <StandingsTable standings={groupsStandings[group.id] ?? []} />
                                            <div className="flex flex-wrap gap-3">
                                                {matches
                                                    .filter((m) => m.group_id === group.id)
                                                    .map((match) => (
                                                        <MatchCard
                                                            key={match.id}
                                                            match={match}
                                                            canRecordResult
                                                            resultRoute={route('tournaments.divisions.matches.result', [
                                                                tournament.id,
                                                                division.id,
                                                                match.id,
                                                            ])}
                                                        />
                                                    ))}
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </div>

                        <div>
                            <h2 className="mb-4 text-lg font-semibold">Fase eliminatoria</h2>
                            <BracketTree
                                matches={matches.filter((m) => m.stage === 'main_bracket')}
                                tournamentId={tournament.id}
                                divisionId={division.id}
                                canRecordResult
                            />
                        </div>
                    </div>
                )}

                <div>
                    <Link href={route('tournaments.show', tournament.id)} className="text-sm text-muted-foreground hover:underline">
                        ← Volver al torneo
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
