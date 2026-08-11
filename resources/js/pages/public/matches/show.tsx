import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useMatchChannel } from '@/hooks/use-match-channel';
import SmartLayout from '@/layouts/smart-layout';
import { celebrateVictory } from '@/lib/confetti';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type MatchScoreState, type Tournament, type TournamentDivision } from '@/types';
import { Head } from '@inertiajs/react';
import { Circle, Timer } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export default function PublicMatchShow({
    tournament,
    division,
    match: initialMatch,
}: {
    tournament: Tournament;
    division: TournamentDivision;
    match: MatchScoreState;
}) {
    const [match, setMatch] = useState(initialMatch);

    useEffect(() => setMatch(initialMatch), [initialMatch]);
    useMatchChannel(match.id, match.status, setMatch);

    const previousStatus = useRef(match.status);
    useEffect(() => {
        if (previousStatus.current !== 'completed' && previousStatus.current !== 'walkover' && (match.status === 'completed' || match.status === 'walkover')) {
            celebrateVictory();
        }
        previousStatus.current = match.status;
    }, [match.status]);

    const currentGame = match.games.find((g) => g.game_number === match.current_game_number);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Explorar torneos', href: '/torneos' },
        { title: tournament.name, href: route('public.tournaments.show', tournament.slug) },
        { title: `${match.entrant1_name} vs ${match.entrant2_name}`, href: '#' },
    ];

    return (
        <SmartLayout breadcrumbs={breadcrumbs}>
            <Head title={`${match.entrant1_name} vs ${match.entrant2_name}`} />
            <div className="mx-auto max-w-2xl space-y-6">
                <div className="text-center">
                    <p className="text-sm text-muted-foreground">
                        {division.name} · {tournament.name}
                    </p>
                </div>

                {match.status === 'pending' || match.status === 'ready' ? (
                    <Card>
                        <CardContent className="py-16 text-center text-muted-foreground">Este partido aún no ha comenzado.</CardContent>
                    </Card>
                ) : (
                    <>
                        <div className="flex items-center justify-center gap-2">
                            <Badge variant="secondary">
                                Juego {match.current_game_number ?? match.games.length} de {match.best_of} · {match.score_summary ?? '0-0'}
                            </Badge>
                            {match.expedite_active && (
                                <Badge className="border-transparent bg-amber-500/15 text-amber-700 dark:text-amber-400">
                                    <Timer className="mr-1 size-3.5" />
                                    Expedite
                                </Badge>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <PlayerScore
                                name={match.entrant1_name}
                                points={currentGame?.entrant1_points ?? 0}
                                isServing={match.current_server_entrant_id === match.entrant1_id}
                                isWinner={match.winner_entrant_id === match.entrant1_id}
                            />
                            <PlayerScore
                                name={match.entrant2_name}
                                points={currentGame?.entrant2_points ?? 0}
                                isServing={match.current_server_entrant_id === match.entrant2_id}
                                isWinner={match.winner_entrant_id === match.entrant2_id}
                            />
                        </div>

                        {match.games.filter((g) => g.winner_entrant_id).length > 0 && (
                            <div className="flex flex-wrap justify-center gap-2 text-sm text-muted-foreground">
                                {match.games
                                    .filter((g) => g.winner_entrant_id)
                                    .map((g) => (
                                        <span key={g.id} className="rounded-md border px-2 py-1">
                                            J{g.game_number}: {g.entrant1_points}-{g.entrant2_points}
                                        </span>
                                    ))}
                            </div>
                        )}

                        {(match.status === 'completed' || match.status === 'walkover') && (
                            <p className="text-center text-lg font-semibold">
                                {match.winner_entrant_id === match.entrant1_id ? match.entrant1_name : match.entrant2_name} gana el partido
                            </p>
                        )}
                    </>
                )}
            </div>
        </SmartLayout>
    );
}

function PlayerScore({ name, points, isServing, isWinner }: { name: string; points: number; isServing: boolean; isWinner: boolean }) {
    return (
        <div className={cn('flex flex-col items-center gap-2 rounded-xl border-2 py-8', isWinner ? 'border-primary' : 'border-transparent')}>
            <span className="flex items-center gap-1.5 text-center font-medium">
                {isServing && <Circle className="size-2.5 fill-primary text-primary" />}
                {name}
            </span>
            <span className="text-6xl font-bold tabular-nums">{points}</span>
        </div>
    );
}
