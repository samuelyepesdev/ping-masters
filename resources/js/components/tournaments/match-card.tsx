import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type BracketMatch, type RefereeOption } from '@/types';
import { Link, router } from '@inertiajs/react';
import { FileDown, Radio, Trophy } from 'lucide-react';

export function MatchCard({
    match,
    scoreRoute,
    scorecardRoute,
    refereeRoute,
    referees,
    canScore,
}: {
    match: BracketMatch;
    scoreRoute: string;
    scorecardRoute?: string;
    refereeRoute?: string;
    referees?: RefereeOption[];
    canScore: boolean;
}) {
    const isDone = match.status === 'completed' || match.status === 'walkover';
    const isPlayable = match.status === 'ready' || match.status === 'in_progress';
    const canAssignReferee = refereeRoute && referees && referees.length > 0 && (match.status === 'ready' || match.status === 'in_progress');

    function assignReferee(value: string) {
        if (!refereeRoute) return;
        router.patch(refereeRoute, { referee_id: value === 'none' ? null : value }, { preserveScroll: true, preserveState: true });
    }

    return (
        <div className="w-64 rounded-lg border bg-card p-2.5 shadow-sm">
            <EntrantRow name={match.entrant1_name} isWinner={isDone && match.winner_entrant_id === match.entrant1_id} />
            <div className="my-1 border-t" />
            <EntrantRow name={match.entrant2_name} isWinner={isDone && match.winner_entrant_id === match.entrant2_id} />

            {isPlayable && canScore && (
                <Button asChild size="sm" variant={match.status === 'in_progress' ? 'default' : 'outline'} className="mt-2 h-7 w-full text-xs">
                    <Link href={scoreRoute}>
                        <Radio className="size-3.5" />
                        {match.status === 'in_progress' ? 'En vivo' : 'Iniciar partido'}
                    </Link>
                </Button>
            )}

            {canAssignReferee && (
                <Select value={match.referee_id ? String(match.referee_id) : 'none'} onValueChange={assignReferee}>
                    <SelectTrigger className="mt-2 h-7 text-xs">
                        <SelectValue placeholder="Sin árbitro" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">Sin árbitro</SelectItem>
                        {referees!.map((referee) => (
                            <SelectItem key={referee.id} value={String(referee.id)}>
                                {referee.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {isDone && scorecardRoute && (
                <a
                    href={scorecardRoute}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mt-2 flex items-center justify-center gap-1 text-xs text-muted-foreground hover:text-foreground hover:underline"
                >
                    <FileDown className="size-3" />
                    Planilla (PDF)
                </a>
            )}
        </div>
    );
}

function EntrantRow({ name, isWinner }: { name: string; isWinner: boolean }) {
    return (
        <div className="flex items-center justify-between gap-2 py-0.5">
            <span className={cn('truncate text-sm', isWinner && 'font-semibold', name === 'Por definir' && 'italic text-muted-foreground')}>
                {isWinner && <Trophy className="mr-1 inline size-3.5 text-amber-500" />}
                {name}
            </span>
        </div>
    );
}
