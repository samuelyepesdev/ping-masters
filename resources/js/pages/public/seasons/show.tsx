import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useInitials } from '@/hooks/use-initials';
import SmartLayout from '@/layouts/smart-layout';
import { formatDate } from '@/lib/format-date';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Season, type SeasonStandingRow } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Trophy } from 'lucide-react';

export default function SeasonShow({ season, standings }: { season: Season; standings: SeasonStandingRow[] }) {
    const getInitials = useInitials();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ranking', href: '/ranking' },
        { title: 'Temporadas', href: route('public.seasons.index') },
        { title: season.name, href: '#' },
    ];

    return (
        <SmartLayout breadcrumbs={breadcrumbs}>
            <Head title={season.name} />
            <div className="mb-6 space-y-2">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-bold tracking-tight">{season.name}</h1>
                    {!season.ended_at && <Badge>En curso</Badge>}
                </div>
                <p className="text-muted-foreground">
                    {formatDate(season.started_at)}
                    {season.ended_at ? ` — ${formatDate(season.ended_at)}` : ' — ranking en vivo'}
                </p>
            </div>

            {standings.length === 0 ? (
                <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-16 text-center">
                    <Trophy className="size-10 text-muted-foreground" />
                    <p className="text-muted-foreground">Todavía no hay jugadores clasificados en esta temporada.</p>
                </div>
            ) : (
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">#</TableHead>
                                <TableHead>Jugador</TableHead>
                                <TableHead>Club</TableHead>
                                <TableHead className="text-right">Rating</TableHead>
                                <TableHead className="text-right">PJ</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {standings.map((row) => (
                                <TableRow key={row.player_id}>
                                    <TableCell
                                        className={cn(
                                            'font-semibold tabular-nums',
                                            row.rank === 1 && 'text-amber-500',
                                            row.rank === 2 && 'text-slate-400',
                                            row.rank === 3 && 'text-orange-500',
                                            row.rank > 3 && 'text-muted-foreground',
                                        )}
                                    >
                                        {row.rank}
                                    </TableCell>
                                    <TableCell>
                                        <Link href={route('public.players.show', row.player_id)} className="flex items-center gap-2 hover:underline">
                                            <Avatar className="size-8">
                                                <AvatarImage src={row.avatar ?? undefined} alt={row.name ?? ''} />
                                                <AvatarFallback className="text-xs">{getInitials(row.name ?? '?')}</AvatarFallback>
                                            </Avatar>
                                            <span className="font-medium">{row.name}</span>
                                        </Link>
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">{row.club ?? '—'}</TableCell>
                                    <TableCell className="text-right font-semibold tabular-nums">{row.rating}</TableCell>
                                    <TableCell className="text-right tabular-nums">{row.matches_played}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}
        </SmartLayout>
    );
}
