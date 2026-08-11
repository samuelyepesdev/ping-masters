import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useMatchChannel } from '@/hooks/use-match-channel';
import { celebrateVictory } from '@/lib/confetti';
import { cn } from '@/lib/utils';
import { type MatchScoreState } from '@/types';
import { Link, router } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { ArrowLeft, Flag, RotateCcw, Swords, Timer, Trophy, Zap } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export interface MatchScoringConsoleRoutes {
    start: string;
    point: string;
    undo: string;
    forfeit: string;
}

const PLAYER_THEME = {
    1: {
        ring: 'ring-blue-500/60',
        border: 'border-blue-500/50',
        text: 'text-blue-600 dark:text-blue-400',
        bg: 'bg-blue-500/10',
        glow: 'shadow-blue-500/20',
        dot: 'bg-blue-500',
    },
    2: {
        ring: 'ring-rose-500/60',
        border: 'border-rose-500/50',
        text: 'text-rose-600 dark:text-rose-400',
        bg: 'bg-rose-500/10',
        glow: 'shadow-rose-500/20',
        dot: 'bg-rose-500',
    },
} as const;

export function MatchScoringConsole({
    match: initialMatch,
    routes,
    channel = 'match',
    forfeitLabel = 'Retiro / walkover',
    onMatchChange,
    backUrl,
    backLabel = 'Volver',
}: {
    match: MatchScoreState;
    routes: MatchScoringConsoleRoutes;
    channel?: string;
    forfeitLabel?: string;
    onMatchChange?: (match: MatchScoreState) => void;
    backUrl?: string;
    backLabel?: string;
}) {
    const [match, setMatch] = useState(initialMatch);
    const [forfeitOpen, setForfeitOpen] = useState(false);

    useEffect(() => setMatch(initialMatch), [initialMatch]);
    useMatchChannel(match.id, match.status, setMatch, channel);

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
    const wonGames = match.games.filter((g) => g.winner_entrant_id);
    const gamesToWin = Math.ceil(match.best_of / 2);

    return (
        <div className="mx-auto max-w-3xl space-y-6">
            {match.status === 'ready' && (
                <div className="space-y-6 text-center">
                    <div className="flex items-center justify-center gap-2 text-muted-foreground">
                        <Swords className="size-5" />
                        <h1 className="text-xl font-semibold text-foreground">¿Quién saca primero?</h1>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <button
                            type="button"
                            onClick={() => start(match.entrant1_id!)}
                            className={cn(
                                'group flex h-28 flex-col items-center justify-center gap-2 rounded-2xl border-2 bg-gradient-to-b transition-all active:scale-[0.97]',
                                PLAYER_THEME[1].border,
                                PLAYER_THEME[1].bg,
                                'to-transparent hover:brightness-110',
                            )}
                        >
                            <span className={cn('text-lg font-bold', PLAYER_THEME[1].text)}>{match.entrant1_name}</span>
                            <span className="text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100">Saca primero</span>
                        </button>
                        <button
                            type="button"
                            onClick={() => start(match.entrant2_id!)}
                            className={cn(
                                'group flex h-28 flex-col items-center justify-center gap-2 rounded-2xl border-2 bg-gradient-to-b transition-all active:scale-[0.97]',
                                PLAYER_THEME[2].border,
                                PLAYER_THEME[2].bg,
                                'to-transparent hover:brightness-110',
                            )}
                        >
                            <span className={cn('text-lg font-bold', PLAYER_THEME[2].text)}>{match.entrant2_name}</span>
                            <span className="text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100">Saca primero</span>
                        </button>
                    </div>
                </div>
            )}

            {match.status === 'in_progress' && currentGame && (
                <div className="space-y-5">
                    <div className="flex flex-wrap items-center justify-center gap-2">
                        <Badge className="border-transparent bg-primary/10 px-3 py-1 text-sm font-semibold text-primary">
                            Juego {match.current_game_number} de {match.best_of}
                        </Badge>
                        {match.expedite_active && (
                            <Badge className="animate-pulse border-transparent bg-amber-500/15 text-amber-700 dark:text-amber-400">
                                <Timer className="mr-1 size-3.5" />
                                Expedite
                            </Badge>
                        )}
                        {match.deciding_game_ends_switched && <Badge variant="outline">Cambio de lado</Badge>}
                    </div>

                    {/* Set-score dots: how many games each player has already won */}
                    <div className="flex items-center justify-center gap-6">
                        <GameDots count={gamesToWin} won={wonGames.filter((g) => g.winner_entrant_id === match.entrant1_id).length} theme={1} />
                        <span className="text-xs font-medium tracking-widest text-muted-foreground uppercase">Sets</span>
                        <GameDots count={gamesToWin} won={wonGames.filter((g) => g.winner_entrant_id === match.entrant2_id).length} theme={2} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <ScoreButton
                            name={match.entrant1_name}
                            points={currentGame.entrant1_points}
                            opponentPoints={currentGame.entrant2_points}
                            isServing={match.current_server_entrant_id === match.entrant1_id}
                            theme={1}
                            onClick={() => point(match.entrant1_id!)}
                        />
                        <ScoreButton
                            name={match.entrant2_name}
                            points={currentGame.entrant2_points}
                            opponentPoints={currentGame.entrant1_points}
                            isServing={match.current_server_entrant_id === match.entrant2_id}
                            theme={2}
                            onClick={() => point(match.entrant2_id!)}
                        />
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
                        <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                            {wonGames.map((g) => (
                                <span key={g.id} className="rounded-md border px-2 py-1 tabular-nums">
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
                <motion.div
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4 }}
                    className="space-y-5 rounded-2xl border bg-gradient-to-b from-primary/5 to-transparent py-10 text-center"
                >
                    <div className="mx-auto flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <Trophy className="size-7" />
                    </div>
                    <h1 className="text-xl font-semibold">Partido finalizado</h1>
                    <p className="text-2xl font-bold tabular-nums">
                        {match.entrant1_name} <span className="text-primary">{match.score_summary}</span> {match.entrant2_name}
                    </p>
                    <p className="text-muted-foreground">
                        Ganador:{' '}
                        <span className="font-semibold text-foreground">
                            {match.winner_entrant_id === match.entrant1_id ? match.entrant1_name : match.entrant2_name}
                        </span>
                        {match.status === 'walkover' && ' (walkover)'}
                    </p>
                    {backUrl && (
                        <Button asChild>
                            <Link href={backUrl}>
                                <ArrowLeft className="size-4" />
                                {backLabel}
                            </Link>
                        </Button>
                    )}
                </motion.div>
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

function GameDots({ count, won, theme }: { count: number; won: number; theme: 1 | 2 }) {
    return (
        <div className="flex gap-1.5">
            {Array.from({ length: count }).map((_, i) => (
                <span
                    key={i}
                    className={cn('size-2.5 rounded-full transition-colors', i < won ? PLAYER_THEME[theme].dot : 'bg-muted')}
                />
            ))}
        </div>
    );
}

function ScoreButton({
    name,
    points,
    opponentPoints,
    isServing,
    theme,
    onClick,
}: {
    name: string;
    points: number;
    opponentPoints: number;
    isServing: boolean;
    theme: 1 | 2;
    onClick: () => void;
}) {
    const matchPoint = points >= 10 && points - opponentPoints >= 1;
    const colors = PLAYER_THEME[theme];

    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'relative flex h-56 flex-col items-center justify-center gap-3 overflow-hidden rounded-2xl border-2 bg-card transition-all active:scale-[0.97]',
                isServing ? cn(colors.border, 'shadow-lg', colors.glow) : 'border-transparent',
            )}
        >
            {isServing && <span className={cn('absolute inset-x-0 top-0 h-1', colors.dot)} />}

            <span className="flex items-center gap-1.5 px-4 text-center text-sm font-semibold">
                {isServing && (
                    <span className="relative flex size-2.5">
                        <span className={cn('absolute inline-flex h-full w-full animate-ping rounded-full opacity-75', colors.dot)} />
                        <span className={cn('relative inline-flex size-2.5 rounded-full', colors.dot)} />
                    </span>
                )}
                {name}
                {isServing && (
                    <span className={cn('inline-flex items-center gap-0.5 text-xs font-bold uppercase', colors.text)}>
                        <Zap className="size-3" />
                        Saca
                    </span>
                )}
            </span>

            <AnimatePresence mode="popLayout">
                <motion.span
                    key={points}
                    initial={{ scale: 1.3, opacity: 0 }}
                    animate={{ scale: 1, opacity: 1 }}
                    transition={{ type: 'spring', stiffness: 400, damping: 20 }}
                    className={cn('text-7xl font-black tabular-nums', matchPoint && colors.text)}
                >
                    {points}
                </motion.span>
            </AnimatePresence>
        </button>
    );
}
