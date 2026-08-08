import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useMatchChannel } from '@/hooks/use-match-channel';
import { celebrateVictory } from '@/lib/confetti';
import { cn } from '@/lib/utils';
import { type MatchScoreState } from '@/types';
import { router } from '@inertiajs/react';
import { Circle, Flag, RotateCcw, Timer } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export interface MatchScoringConsoleRoutes {
    start: string;
    point: string;
    undo: string;
    forfeit: string;
}

export function MatchScoringConsole({
    match: initialMatch,
    routes,
    channel = 'match',
    forfeitLabel = 'Retiro / walkover',
    onMatchChange,
}: {
    match: MatchScoreState;
    routes: MatchScoringConsoleRoutes;
    channel?: string;
    forfeitLabel?: string;
    onMatchChange?: (match: MatchScoreState) => void;
}) {
    const [match, setMatch] = useState(initialMatch);
    const [forfeitOpen, setForfeitOpen] = useState(false);

    useEffect(() => setMatch(initialMatch), [initialMatch]);
    useMatchChannel(match.id, setMatch, channel);

    useEffect(() => onMatchChange?.(match), [match, onMatchChange]);

    const previousStatus = useRef(match.status);
    useEffect(() => {
        if (
            previousStatus.current !== 'completed' &&
            previousStatus.current !== 'walkover' &&
            (match.status === 'completed' || match.status === 'walkover')
        ) {
            celebrateVictory();
        }
        previousStatus.current = match.status;
    }, [match.status]);

    function start(firstServerEntrantId: number) {
        router.post(routes.start, { first_server_entrant_id: firstServerEntrantId }, { preserveScroll: true });
    }

    function point(entrantId: number) {
        router.post(routes.point, { entrant_id: entrantId }, { preserveScroll: true, preserveState: true });
    }

    function undo() {
        router.post(routes.undo, {}, { preserveScroll: true, preserveState: true });
    }

    function forfeit(winnerEntrantId: number) {
        router.post(routes.forfeit, { winner_entrant_id: winnerEntrantId }, { preserveScroll: true });
        setForfeitOpen(false);
    }

    const currentGame = match.games.find((g) => g.game_number === match.current_game_number);

    return (
        <div className="mx-auto max-w-3xl space-y-6">
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
                            <Button variant="outline" size="sm" onClick={() => setForfeitOpen(true)}>
                                <Flag className="size-4" />
                                {forfeitLabel}
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {(match.status === 'pending' || match.status === 'waiting') && (
                <p className="py-10 text-center text-muted-foreground">Esperando a que el partido esté listo para comenzar...</p>
            )}

            {match.status === 'cancelled' && <p className="py-10 text-center text-muted-foreground">Este partido fue cancelado.</p>}

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

            <Dialog open={forfeitOpen} onOpenChange={setForfeitOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Registrar {forfeitLabel.toLowerCase()}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">Selecciona quién avanza como ganador.</p>
                    <div className="grid grid-cols-2 gap-3">
                        <Button variant="outline" onClick={() => forfeit(match.entrant1_id!)}>
                            {match.entrant1_name}
                        </Button>
                        <Button variant="outline" onClick={() => forfeit(match.entrant2_id!)}>
                            {match.entrant2_name}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
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
