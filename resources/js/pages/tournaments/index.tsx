import { ConfirmDialog } from '@/components/confirm-dialog';
import { Pagination } from '@/components/pagination';
import { TournamentStatusBadge } from '@/components/tournaments/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDateRange } from '@/lib/format-date';
import { type BreadcrumbItem, type PaginatedData, type Tournament } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, Eye, MapPin, MoreHorizontal, Pencil, Plus, Trash2, Trophy, Users } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Torneos', href: '/tournaments' }];

export default function TournamentsIndex({ tournaments }: { tournaments: PaginatedData<Tournament> }) {
    const [viewing, setViewing] = useState<Tournament | null>(null);
    const [pendingDelete, setPendingDelete] = useState<Tournament | null>(null);

    function destroy() {
        if (!pendingDelete) return;
        router.delete(route('tournaments.destroy', pendingDelete.id));
        setPendingDelete(null);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Torneos" />
            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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
                                            {formatDateRange(tournament.start_date, tournament.end_date)}
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
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon" className="size-8">
                                                        <MoreHorizontal className="size-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem onClick={() => setViewing(tournament)}>
                                                        <Eye className="size-4" />
                                                        Ver
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('tournaments.edit', tournament.id)}>
                                                            <Pencil className="size-4" />
                                                            Editar
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        className="text-destructive focus:text-destructive"
                                                        onClick={() => setPendingDelete(tournament)}
                                                    >
                                                        <Trash2 className="size-4" />
                                                        Eliminar
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <Pagination data={tournaments} />
            </div>

            <Dialog open={viewing !== null} onOpenChange={(open) => !open && setViewing(null)}>
                <DialogContent>
                    {viewing && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    {viewing.name}
                                    <TournamentStatusBadge status={viewing.status} />
                                </DialogTitle>
                            </DialogHeader>

                            <div className="space-y-3 text-sm">
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <CalendarDays className="size-4" />
                                    {formatDateRange(viewing.start_date, viewing.end_date)}
                                </div>
                                {(viewing.venue || viewing.city) && (
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <MapPin className="size-4" />
                                        {[viewing.venue, viewing.city].filter(Boolean).join(', ')}
                                    </div>
                                )}
                                {(viewing.registration_opens_at || viewing.registration_closes_at) && (
                                    <p className="text-muted-foreground">
                                        Inscripciones: {formatDate(viewing.registration_opens_at) || '—'} a{' '}
                                        {formatDate(viewing.registration_closes_at) || '—'}
                                    </p>
                                )}
                                <div className="flex flex-wrap gap-2 pt-1">
                                    <Badge variant="secondary">{viewing.divisions_count ?? 0} categorías</Badge>
                                    <Badge variant="secondary">{viewing.registrations_count ?? 0} inscritos</Badge>
                                    {!viewing.is_active && <Badge variant="outline">Inactivo</Badge>}
                                </div>
                                {viewing.description && <p className="text-muted-foreground">{viewing.description}</p>}
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button variant="outline" asChild>
                                    <Link href={route('tournaments.edit', viewing.id)}>Editar</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={route('tournaments.show', viewing.id)}>Ver detalle completo</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={pendingDelete !== null}
                onOpenChange={(open) => !open && setPendingDelete(null)}
                title="Eliminar torneo"
                description={`¿Eliminar «${pendingDelete?.name}»? Se eliminarán también sus categorías, inscripciones y partidos. Esta acción no se puede deshacer.`}
                confirmLabel="Eliminar"
                destructive
                onConfirm={destroy}
            />
        </AppLayout>
    );
}
