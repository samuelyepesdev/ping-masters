import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useMatchChannel } from '@/hooks/use-match-channel';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type MatchScoreState, type Tournament, type TournamentDivision } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Circle, Flag, RotateCcw, Timer } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function ScoringConsole({
    tournament,
    division,
    match: initialMatch,
}: {
    tournament: Tournament;
    division: TournamentDivision;
    match: MatchScoreState;
}) {
    const [match, setMatch] = useState(initialMatch);
    const [walkoverOpen, setWalkoverOpen] = useState(false);

    useEffect(() => setMatch(initialMatch), [initialMatch]);
    useMatchChannel(match.id, setMatch);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Torneos', href: '/tournaments' },
        { title: tournament.name, href: `/tournaments/${tournament.id}` },
        { title: division.name, href: `/tournaments/${tournament.id}/divisions/${division.id}` },
        { title: 'Marcador', href: '#' },
    ];

    const routeArgs = [tournament.id, division.id, match.id];

    function start(firstServerEntrantId: number) {
        router.post(
            route('tournaments.divisions.matches.start', routeArgs),
            { first_server_entrant_id: firstServerEntrantId },
            { preserveScroll: true },
        );
    }

    function point(entrantId: number) {
        router.post(route('tournaments.divisions.matches.point', routeArgs), { entrant_id: entrantId }, { preserveScroll: true, preserveState: true });
    }

    function undo() {
        router.post(route('tournaments.divisions.matches.undo', routeArgs), {}, { preserveScroll: true, preserveState: true });
    }

    function walkover(winnerEntrantId: number) {
        router.post(route('tournaments.divisions.matches.walkover', routeArgs), { winner_entrant_id: winnerEntrantId }, { preserveScroll: true });
        setWalkoverOpen(false);
    }

    const currentGame = match.games.find((g) => g.game_number === match.current_game_number);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Marcador en vivo" />
            <div className="mx-auto max-w-3xl space-y-6 p-4">
                {match.status === 'ready' && (
                    <div className="space-y-4 text-center">
                        <h1 className="text-xl font-semibold">¿Quién saca primero?</h1>
                        <div className="grid grid-cols-2 gap-4">
                            <Button size="lg" className="h-24 text-lg" onClick={() => start(match.entrant1_id!)}>
                                {match.entrant1_name}
                            </Button>
                            <Button size="lg" className="h-24 text-lg" onClick={() => start(match.entrant2_id!)}>
                                {match.entrant2_name}
                            </Button>
                        </div>
                    </div>
                )}

                {match.status === 'in_progress' && currentGame && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <Badge variant="secondary">
                                Juego {match.current_game_number} de {match.best_of} · Marcador de juegos {match.score_summary ?? '0-0'}
                            </Badge>
                            <div className="flex items-center gap-2">
                                {match.expedite_active && (
                                    <Badge className="border-transparent bg-amber-500/15 text-amber-700 dark:text-amber-400">
                                        <Timer className="mr-1 size-3.5" />
                                        Expedite
                                    </Badge>
                                )}
                                {match.deciding_game_ends_switched && <Badge variant="outline">Cambio de lado</Badge>}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <ScoreButton
                                name={match.entrant1_name}
                                points={currentGame.entrant1_points}
                                isServing={match.current_server_entrant_id === match.entrant1_id}
                                onClick={() => point(match.entrant1_id!)}
                            />
                            <ScoreButton
                                name={match.entrant2_name}
                                points={currentGame.entrant2_points}
                                isServing={match.current_server_entrant_id === match.entrant2_id}
                                onClick={() => point(match.entrant2_id!)}
                            />
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
                            <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                                {match.games
                                    .filter((g) => g.winner_entrant_id)
                                    .map((g) => (
                                        <span key={g.id} className="rounded-md border px-2 py-1">
                                            J{g.game_number}: {g.entrant1_points}-{g.entrant2_points}
                                        </span>
                                    ))}
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" onClick={undo}>
                                    <RotateCcw className="size-4" />
                                    Deshacer
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => setWalkoverOpen(true)}>
                                    <Flag className="size-4" />
                                    Retiro / walkover
                                </Button>
                            </div>
                        </div>
                    </div>
                )}

                {(match.status === 'completed' || match.status === 'walkover') && (
                    <div className="space-y-4 text-center">
                        <h1 className="text-xl font-semibold">Partido finalizado</h1>
                        <p className="text-lg">
                            {match.entrant1_name} {match.score_summary} {match.entrant2_name}
                        </p>
                        <p className="text-muted-foreground">
                            Ganador: {match.winner_entrant_id === match.entrant1_id ? match.entrant1_name : match.entrant2_name}
                            {match.status === 'walkover' && ' (walkover)'}
                        </p>
                    </div>
                )}
            </div>

            <Dialog open={walkoverOpen} onOpenChange={setWalkoverOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Registrar retiro / walkover</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">Selecciona quién avanza como ganador.</p>
                    <div className="grid grid-cols-2 gap-3">
                        <Button variant="outline" onClick={() => walkover(match.entrant1_id!)}>
                            {match.entrant1_name}
                        </Button>
                        <Button variant="outline" onClick={() => walkover(match.entrant2_id!)}>
                            {match.entrant2_name}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function ScoreButton({ name, points, isServing, onClick }: { name: string; points: number; isServing: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex h-48 flex-col items-center justify-center gap-2 rounded-xl border-2 bg-card transition-colors active:scale-[0.98]',
                isServing ? 'border-primary' : 'border-transparent',
            )}
        >
            <span className="flex items-center gap-1.5 text-sm font-medium">
                {isServing && <Circle className="size-2.5 fill-primary text-primary" />}
                {name}
            </span>
            <span className="text-6xl font-bold tabular-nums">{points}</span>
        </button>
    );
}
