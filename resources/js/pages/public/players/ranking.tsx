import { Pagination } from '@/components/pagination';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useInitials } from '@/hooks/use-initials';
import SmartLayout from '@/layouts/smart-layout';
import { type BreadcrumbItem, type PaginatedData, type Player } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
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

    return (
        <SmartLayout breadcrumbs={breadcrumbs}>
            <Head title="Ranking" />
            <div className="mb-6 space-y-2">
                <h1 className="text-3xl font-bold tracking-tight">Ranking de jugadores</h1>
                <p className="text-muted-foreground">Clasificación por rating ELO.</p>
            </div>

            <form onSubmit={submit} className="mb-4 max-w-sm">
                <Input placeholder="Buscar jugador..." value={search} onChange={(e) => setSearch(e.target.value)} />
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
                        {players.data.map((player, index) => (
                            <TableRow key={player.id}>
                                <TableCell className="text-muted-foreground">{startRank + index + 1}</TableCell>
                                <TableCell>
                                    <Link href={route('public.players.show', player.id)} className="flex items-center gap-2 hover:underline">
                                        <Avatar className="size-8">
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
                                <TableCell className="text-right tabular-nums">{player.matches_played > 0 ? `${((player.matches_won / player.matches_played) * 100).toFixed(0)}%` : '—'}</TableCell>
                            </TableRow>
                        ))}
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
