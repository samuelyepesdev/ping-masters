import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarClock, Radio, Swords } from 'lucide-react';

interface RefereeMatch {
    id: number;
    tournament_id: number;
    tournament_name: string;
    division_id: number;
    division_name: string;
    round_name: string | null;
    entrant1_name: string;
    entrant2_name: string;
    status: string;
    table_number: number | null;
    scheduled_at: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Arbitraje', href: '/scoring' }];

export default function RefereeIndex({ upcoming, completed }: { upcoming: RefereeMatch[]; completed: RefereeMatch[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Arbitraje" />
            <div className="space-y-8 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Mis partidos por arbitrar</h1>
                    <p className="text-muted-foreground">Partidos que un organizador te ha asignado.</p>
                </div>

                <div>
                    <h2 className="mb-3 text-lg font-semibold">Por jugar</h2>
                    {upcoming.length === 0 ? (
                        <Card className="border-dashed">
                            <CardContent className="py-10 text-center text-sm text-muted-foreground">
                                No tienes partidos asignados por ahora.
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="space-y-3">
                            {upcoming.map((match) => (
                                <MatchRow key={match.id} match={match} />
                            ))}
                        </div>
                    )}
                </div>

                {completed.length > 0 && (
                    <div>
                        <h2 className="mb-3 text-lg font-semibold">Arbitrados</h2>
                        <div className="space-y-3">
                            {completed.map((match) => (
                                <MatchRow key={match.id} match={match} />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function MatchRow({ match }: { match: RefereeMatch }) {
    const isDone = match.status === 'completed' || match.status === 'walkover';

    return (
        <Card>
            <CardContent className="flex flex-wrap items-center justify-between gap-3 py-4">
                <div>
                    <p className="font-medium">
                        {match.entrant1_name} <span className="text-muted-foreground">vs</span> {match.entrant2_name}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {match.tournament_name} · {match.division_name}
                        {match.round_name ? ` · ${match.round_name}` : ''}
                    </p>
                    <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                        {match.table_number && (
                            <span className="flex items-center gap-1">
                                <Swords className="size-3" />
                                Mesa {match.table_number}
                            </span>
                        )}
                        {match.scheduled_at && (
                            <span className="flex items-center gap-1">
                                <CalendarClock className="size-3" />
                                {match.scheduled_at}
                            </span>
                        )}
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Badge variant={isDone ? 'secondary' : match.status === 'in_progress' ? 'default' : 'outline'}>{match.status}</Badge>
                    {!isDone && (
                        <Button asChild size="sm">
                            <Link href={route('tournaments.divisions.matches.score', [match.tournament_id, match.division_id, match.id])}>
                                <Radio className="size-3.5" />
                                {match.status === 'in_progress' ? 'Continuar' : 'Iniciar'}
                            </Link>
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
