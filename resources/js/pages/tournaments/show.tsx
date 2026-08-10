import { Pagination } from '@/components/pagination';
import { TournamentStatusBadge } from '@/components/tournaments/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatDateRange } from '@/lib/format-date';
import { buildTournamentInviteText } from '@/lib/tournament-invite';
import { type BreadcrumbItem, type PaginatedData, type RegistrationStatus, type Tournament, type TournamentRegistration } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Check, Copy, FileDown, Pencil, Share2 } from 'lucide-react';
import { useState } from 'react';

const REGISTRATION_STATUS_LABELS: Record<RegistrationStatus, string> = {
    pending: 'Pendiente',
    approved: 'Aprobada',
    rejected: 'Rechazada',
    waitlisted: 'Lista de espera',
    cancelled: 'Cancelada',
};

export default function TournamentShow({
    tournament,
    registrations,
}: {
    tournament: Tournament;
    registrations: PaginatedData<TournamentRegistration>;
}) {
    const [viewing, setViewing] = useState<TournamentRegistration | null>(null);
    const [sharing, setSharing] = useState(false);
    const [copied, setCopied] = useState(false);

    const inviteText = buildTournamentInviteText(tournament);

    async function copyInviteText() {
        await navigator.clipboard.writeText(inviteText);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    async function shareInviteText() {
        if (navigator.share) {
            try {
                await navigator.share({ title: tournament.name, text: inviteText });
            } catch {
                // The user cancelled the native share sheet — nothing to do.
            }
        } else {
            await copyInviteText();
        }
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Torneos', href: '/tournaments' },
        { title: tournament.name, href: `/tournaments/${tournament.id}` },
    ];

    function updateStatus(registration: TournamentRegistration, status: RegistrationStatus) {
        router.patch(
            route('tournaments.registrations.update', [tournament.id, registration.id]),
            { status },
            { preserveScroll: true },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={tournament.name} />
            <div className="space-y-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight">{tournament.name}</h1>
                            <TournamentStatusBadge status={tournament.status} />
                        </div>
                        <p className="text-muted-foreground">
                            {tournament.venue ? `${tournament.venue}, ` : ''}
                            {tournament.city} · {formatDateRange(tournament.start_date, tournament.end_date)}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" onClick={() => setSharing(true)}>
                            <Share2 className="size-4" />
                            Compartir
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={route('tournaments.pdf.schedule', tournament.id)} target="_blank" rel="noopener noreferrer">
                                <FileDown className="size-4" />
                                Cronograma (PDF)
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={route('tournaments.edit', tournament.id)}>
                                <Pencil className="size-4" />
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Categorías</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {tournament.divisions?.map((division) => (
                                <Link
                                    key={division.id}
                                    href={route('tournaments.divisions.show', [tournament.id, division.id])}
                                    className="flex items-center justify-between rounded-md border px-3 py-2 text-sm hover:bg-accent"
                                >
                                    <span>{division.name}</span>
                                    <Badge variant="secondary">{division.format.replace(/_/g, ' ')}</Badge>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Formulario de inscripción</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {tournament.registration_fields?.length ? (
                                tournament.registration_fields.map((field) => (
                                    <div key={field.id} className="flex items-center justify-between rounded-md border px-3 py-2 text-sm">
                                        <span>{field.label}</span>
                                        {field.is_required && (
                                            <Badge variant="outline" className="text-xs">
                                                obligatorio
                                            </Badge>
                                        )}
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-muted-foreground">Sin campos personalizados.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Inscripciones ({registrations.total})</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Jugador</TableHead>
                                    <TableHead>Categorías</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {registrations.data.map((registration) => (
                                    <TableRow key={registration.id}>
                                        <TableCell>
                                            <p className="font-medium">{registration.player?.user?.name}</p>
                                            <p className="text-xs text-muted-foreground">{registration.player?.user?.email}</p>
                                        </TableCell>
                                        <TableCell className="text-sm">
                                            {registration.divisions?.map((d) => d.division?.name).join(', ')}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {new Date(registration.submitted_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>
                                            <Select
                                                value={registration.status}
                                                onValueChange={(value) => updateStatus(registration, value as RegistrationStatus)}
                                            >
                                                <SelectTrigger className="h-8 w-[160px]">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {Object.entries(REGISTRATION_STATUS_LABELS).map(([value, label]) => (
                                                        <SelectItem key={value} value={value}>
                                                            {label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="sm" onClick={() => setViewing(registration)}>
                                                Ver respuestas
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {registrations.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-sm text-muted-foreground">
                                            Aún no hay inscripciones.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Pagination data={registrations} />
            </div>

            <Dialog open={!!viewing} onOpenChange={(open) => !open && setViewing(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{viewing?.player?.user?.name}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        {viewing?.responses?.length ? (
                            viewing.responses.map((response) => (
                                <div key={response.id}>
                                    <p className="text-sm font-medium">{response.field?.label}</p>
                                    <p className="text-sm text-muted-foreground">{response.value}</p>
                                </div>
                            ))
                        ) : (
                            <p className="text-sm text-muted-foreground">Sin respuestas adicionales.</p>
                        )}
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog open={sharing} onOpenChange={setSharing}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Invitar al torneo</DialogTitle>
                    </DialogHeader>
                    <Textarea value={inviteText} readOnly rows={8} className="resize-none text-sm" />
                    <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={copyInviteText}>
                            {copied ? <Check className="size-4" /> : <Copy className="size-4" />}
                            {copied ? 'Copiado' : 'Copiar texto'}
                        </Button>
                        <Button onClick={shareInviteText}>
                            <Share2 className="size-4" />
                            Compartir
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
