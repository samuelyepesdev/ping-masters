import { TournamentStatusBadge } from '@/components/tournaments/status-badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedData, type Tournament } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Plus, Trophy, Users } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Torneos', href: '/tournaments' }];

export default function TournamentsIndex({ tournaments }: { tournaments: PaginatedData<Tournament> }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Torneos" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Torneos</h1>
                        <p className="text-muted-foreground">Administra tus eventos de tenis de mesa.</p>
                    </div>
                    <Button asChild>
                        <Link href={route('tournaments.create')}>
                            <Plus className="size-4" />
                            Nuevo torneo
                        </Link>
                    </Button>
                </div>

                {tournaments.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-16 text-center">
                        <Trophy className="size-10 text-muted-foreground" />
                        <div>
                            <p className="font-medium">Todavía no has creado ningún torneo</p>
                            <p className="text-sm text-muted-foreground">Crea el primero para empezar a recibir inscripciones.</p>
                        </div>
                        <Button asChild>
                            <Link href={route('tournaments.create')}>Crear torneo</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Torneo</TableHead>
                                    <TableHead>Fechas</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Categorías</TableHead>
                                    <TableHead>Inscritos</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tournaments.data.map((tournament) => (
                                    <TableRow key={tournament.id}>
                                        <TableCell className="font-medium">
                                            <Link href={route('tournaments.show', tournament.id)} className="hover:underline">
                                                {tournament.name}
                                            </Link>
                                            {tournament.city && <p className="text-xs text-muted-foreground">{tournament.city}</p>}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {tournament.start_date} — {tournament.end_date}
                                        </TableCell>
                                        <TableCell>
                                            <TournamentStatusBadge status={tournament.status} />
                                        </TableCell>
                                        <TableCell>{tournament.divisions_count}</TableCell>
                                        <TableCell>
                                            <span className="inline-flex items-center gap-1 text-sm">
                                                <Users className="size-3.5" />
                                                {tournament.registrations_count}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={route('tournaments.edit', tournament.id)}>Editar</Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
