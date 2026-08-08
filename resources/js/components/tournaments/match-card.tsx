import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type BracketMatch } from '@/types';
import { router } from '@inertiajs/react';
import { Trophy } from 'lucide-react';

export function MatchCard({
    match,
    resultRoute,
    canRecordResult,
}: {
    match: BracketMatch;
    resultRoute: string;
    canRecordResult: boolean;
}) {
    function declareWinner(entrantId: number | null) {
        if (!entrantId) return;

        router.patch(
            resultRoute,
            { winner_entrant_id: entrantId },
            { preserveScroll: true },
        );
    }

    const isReady = match.status === 'ready';
    const isDone = match.status === 'completed' || match.status === 'walkover';

    return (
        <div className="w-64 rounded-lg border bg-card p-2.5 shadow-sm">
            <EntrantRow
                name={match.entrant1_name}
                isWinner={isDone && match.winner_entrant_id === match.entrant1_id}
                showButton={isReady && canRecordResult}
                onClick={() => declareWinner(match.entrant1_id)}
            />
            <div className="my-1 border-t" />
            <EntrantRow
                name={match.entrant2_name}
                isWinner={isDone && match.winner_entrant_id === match.entrant2_id}
                showButton={isReady && canRecordResult}
                onClick={() => declareWinner(match.entrant2_id)}
            />
        </div>
    );
}

function EntrantRow({
    name,
    isWinner,
    showButton,
    onClick,
}: {
    name: string;
    isWinner: boolean;
    showButton: boolean;
    onClick: () => void;
}) {
    return (
        <div className="flex items-center justify-between gap-2 py-0.5">
            <span className={cn('truncate text-sm', isWinner && 'font-semibold', name === 'Por definir' && 'italic text-muted-foreground')}>
                {isWinner && <Trophy className="mr-1 inline size-3.5 text-amber-500" />}
                {name}
            </span>
            {showButton && (
                <Button type="button" size="sm" variant="outline" className="h-6 px-2 text-xs" onClick={onClick}>
                    Ganó
                </Button>
            )}
        </div>
    );
}
