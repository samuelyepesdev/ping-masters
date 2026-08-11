import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format-date';
import { type BreadcrumbItem, type Season } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { CalendarRange, RotateCcw, Trophy, Users } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Temporadas', href: '/admin/seasons' }];

interface Props {
    current: Season;
    activePlayers: number;
    pastSeasons: Season[];
}

export default function AdminSeasonsIndex({ current, activePlayers, pastSeasons }: Props) {
    const [resetting, setResetting] = useState(false);

    const form = useForm({ name: '' });

    const submitReset: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('admin.seasons.reset'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setResetting(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Temporadas" />
            <div className="space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Temporadas</h1>
                    <p className="text-muted-foreground">Reinicia el ranking por temporadas y consulta el histórico de cierres anteriores.</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <CalendarRange className="size-4 text-muted-foreground" />
                            Temporada actual
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="text-xl font-semibold">{current.name}</span>
                            <Badge variant="secondary">Desde {formatDate(current.started_at)}</Badge>
                        </div>
                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <Users className="size-4" />
                            {activePlayers} {activePlayers === 1 ? 'jugador ha' : 'jugadores han'} jugado partidos clasificatorios esta temporada.
                        </p>
                        <Button variant="outline" onClick={() => setResetting(true)}>
                            <RotateCcw className="size-4" />
                            Reiniciar temporada
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Histórico de temporadas cerradas</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {pastSeasons.length === 0 ? (
                            <div className="flex flex-col items-center gap-3 py-16 text-center">
                                <Trophy className="size-10 text-muted-foreground" />
                                <p className="text-muted-foreground">Todavía no se ha cerrado ninguna temporada.</p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Temporada</TableHead>
                                        <TableHead>Periodo</TableHead>
                                        <TableHead className="text-right">Jugadores</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {pastSeasons.map((season) => (
                                        <TableRow key={season.id}>
                                            <TableCell className="font-medium">{season.name}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {formatDate(season.started_at)} — {formatDate(season.ended_at)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">{season.standings_count ?? 0}</TableCell>
                                            <TableCell className="text-right">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={route('public.seasons.show', season.id)}>Ver ranking final</Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog open={resetting} onOpenChange={setResetting}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reiniciar temporada</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3 text-sm">
                        <p>
                            Esto va a <span className="font-semibold text-foreground">guardar el ranking actual de "{current.name}"</span> en el
                            histórico y <span className="font-semibold text-foreground">reiniciar el rating de todos los jugadores a 1000</span>.
                        </p>
                        <p className="text-muted-foreground">
                            El XP, nivel, logros y el historial de partidos jugados de cada jugador no se ven afectados — solo el rating usado
                            para el ranking.
                        </p>
                    </div>
                    <form onSubmit={submitReset} className="space-y-3">
                        <div className="grid gap-1.5">
                            <Label htmlFor="season-name">Nombre de la nueva temporada (opcional)</Label>
                            <Input
                                id="season-name"
                                placeholder={`Temporada ${pastSeasons.length + 2}`}
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setResetting(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" variant="destructive" disabled={form.processing}>
                                Sí, reiniciar temporada
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
