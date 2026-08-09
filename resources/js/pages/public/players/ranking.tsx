import { Pagination } from '@/components/pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useInitials } from '@/hooks/use-initials';
import SmartLayout from '@/layouts/smart-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type PaginatedData, type Player } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Crown, Search, Sparkles } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export default function PlayerRanking({ players, filters }: { players: PaginatedData<Player>; filters: { search?: string } }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const getInitials = useInitials();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('public.players.ranking'), { search }, { preserveState: true, replace: true });
    };

    const startRank = (players.current_page - 1) * players.per_page;
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ranking', href: '/ranking' }];
    const showPodium = !filters.search && players.current_page === 1 && players.data.length > 0;

    return (
        <SmartLayout breadcrumbs={breadcrumbs}>
            <Head title="Ranking" />
            <div className="mb-6 space-y-2">
                <h1 className="text-3xl font-bold tracking-tight">Ranking de jugadores</h1>
                <p className="text-muted-foreground">Clasificación por rating ELO.</p>
            </div>

            {showPodium && <Podium players={players.data.slice(0, 3)} getInitials={getInitials} />}

            <form onSubmit={submit} className="relative mb-4 max-w-sm">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input placeholder="Buscar jugador..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9" />
            </form>

            <div className="rounded-xl border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-12">#</TableHead>
                            <TableHead>Jugador</TableHead>
                            <TableHead>Club</TableHead>
                            <TableHead className="text-right">Nivel</TableHead>
                            <TableHead className="text-right">Rating</TableHead>
                            <TableHead className="text-right">PJ</TableHead>
                            <TableHead className="text-right">% Victorias</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {players.data.map((player, index) => {
                            const rank = startRank + index + 1;

                            return (
                                <TableRow key={player.id}>
                                    <TableCell
                                        className={cn(
                                            'font-semibold tabular-nums',
                                            rank === 1 && 'text-amber-500',
                                            rank === 2 && 'text-slate-400',
                                            rank === 3 && 'text-orange-500',
                                            rank > 3 && 'text-muted-foreground',
                                        )}
                                    >
                                        {rank}
                                    </TableCell>
                                    <TableCell>
                                        <Link href={route('public.players.show', player.id)} className="flex items-center gap-2 hover:underline">
                                            <Avatar className="size-8">
                                                <AvatarImage src={player.user?.avatar ?? undefined} alt={player.user?.name ?? ''} />
                                                <AvatarFallback className="text-xs">{getInitials(player.user?.name ?? '?')}</AvatarFallback>
                                            </Avatar>
                                            <span className="font-medium">{player.user?.name}</span>
                                            {player.is_elite && <Sparkles className="size-3.5 text-amber-500" />}
                                        </Link>
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">{player.club?.name ?? '—'}</TableCell>
                                    <TableCell className="text-right">
                                        <Badge variant="secondary">Nv. {player.level}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right font-semibold tabular-nums">{player.rating_current}</TableCell>
                                    <TableCell className="text-right tabular-nums">{player.matches_played}</TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {player.matches_played > 0 ? `${((player.matches_won / player.matches_played) * 100).toFixed(0)}%` : '—'}
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                        {players.data.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={7} className="text-center text-sm text-muted-foreground">
                                    No se encontraron jugadores.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <Pagination data={players} />
            </div>
        </SmartLayout>
    );
}

function Podium({ players, getInitials }: { players: Player[]; getInitials: (name: string) => string }) {
    const [first, second, third] = players;

    return (
        <div className="mb-8 grid grid-cols-3 items-end gap-2 sm:gap-4">
            {second ? <PodiumCard player={second} rank={2} getInitials={getInitials} /> : <div />}
            <PodiumCard player={first} rank={1} getInitials={getInitials} />
            {third ? <PodiumCard player={third} rank={3} getInitials={getInitials} /> : <div />}
        </div>
    );
}

const PODIUM_STYLES: Record<1 | 2 | 3, string> = {
    1: 'border-amber-400/60 bg-gradient-to-b from-amber-400/20 via-amber-400/5 to-transparent',
    2: 'border-slate-400/50 bg-gradient-to-b from-slate-400/15 via-slate-400/5 to-transparent',
    3: 'border-orange-400/50 bg-gradient-to-b from-orange-400/15 via-orange-400/5 to-transparent',
};

function PodiumCard({ player, rank, getInitials }: { player: Player; rank: 1 | 2 | 3; getInitials: (name: string) => string }) {
    const isFirst = rank === 1;

    return (
        <Link
            href={route('public.players.show', player.id)}
            className={cn(
                'flex min-w-0 flex-col items-center gap-1.5 rounded-2xl border-2 px-1.5 pb-3 text-center transition-transform hover:-translate-y-0.5 sm:gap-2 sm:px-3 sm:pb-5',
                PODIUM_STYLES[rank],
                isFirst ? 'pt-5 sm:pt-8' : 'pt-4 sm:pt-6',
            )}
        >
            {isFirst && <Crown className="size-4 text-amber-500 sm:size-5" />}
            <Avatar className={isFirst ? 'size-12 sm:size-20' : 'size-9 sm:size-14'}>
                <AvatarImage src={player.user?.avatar ?? undefined} alt={player.user?.name ?? ''} />
                <AvatarFallback className={isFirst ? 'text-sm sm:text-lg' : 'text-xs sm:text-sm'}>
                    {getInitials(player.user?.name ?? '?')}
                </AvatarFallback>
            </Avatar>
            <div className="min-w-0 w-full">
                <p className={cn('truncate font-semibold', isFirst ? 'text-sm sm:text-lg' : 'text-xs sm:text-sm')}>{player.user?.name}</p>
                <p className="truncate text-[11px] text-muted-foreground sm:text-xs">
                    #{rank} · {player.rating_current}
                </p>
            </div>
        </Link>
    );
}
