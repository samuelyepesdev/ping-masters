import { TournamentStatusBadge } from '@/components/tournaments/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDateRange } from '@/lib/format-date';
import { type BreadcrumbItem, type SharedData, type Tournament, type TournamentRegistration } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarClock, ClipboardList, Plus, Sparkles, Swords, Trophy, Users, Zap } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Panel', href: '/dashboard' }];

interface AdminStats {
    tournaments_total: number;
    tournaments_active: number;
    users_total: number;
    matches_played: number;
    recent_tournaments: Tournament[];
}

interface OrganizerStats {
    tournaments_total: number;
    tournaments_open: number;
    pending_registrations: number;
    upcoming_tournaments: Tournament[];
}

interface RefereeMatch {
    id: number;
    tournament_id: number;
    division_id: number;
    tournament_name: string;
    division_name: string;
    entrant1_name: string;
    entrant2_name: string;
}

interface RefereeStats {
    upcoming_count: number;
    upcoming_matches: RefereeMatch[];
}

interface PlayerStats {
    rating_current: number;
    level: number;
    xp_total: number;
    matches_played: number;
    matches_won: number;
    is_elite: boolean;
    player_id: number;
    upcoming_registrations: TournamentRegistration[];
}

interface Props {
    admin?: AdminStats;
    organizer?: OrganizerStats;
    referee?: RefereeStats;
    player?: PlayerStats;
}

export default function Dashboard({ admin, organizer, referee, player }: Props) {
    const { auth } = usePage<SharedData>().props;
    const hasAnySection = admin || organizer || referee || player;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel" />
            <div className="space-y-8 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Hola, {auth.user.name.split(' ')[0]}</h1>
                    <p className="text-muted-foreground">Este es tu panel de Ping Masters.</p>
                </div>

                {admin && (
                    <section className="space-y-4">
                        <h2 className="text-lg font-semibold">Plataforma</h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <StatCard icon={Trophy} label="Torneos totales" value={admin.tournaments_total} />
                            <StatCard icon={CalendarClock} label="Torneos activos" value={admin.tournaments_active} />
                            <StatCard icon={Users} label="Usuarios" value={admin.users_total} />
                            <StatCard icon={Swords} label="Partidos jugados" value={admin.matches_played} />
                        </div>
                        {admin.recent_tournaments.length > 0 && (
                            <Card>
                                <CardContent className="divide-y p-0">
                                    {admin.recent_tournaments.map((tournament) => (
                                        <TournamentRow key={tournament.id} tournament={tournament} />
                                    ))}
                                </CardContent>
                            </Card>
                        )}
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('admin.users.index')}>Administrar usuarios y roles</Link>
                        </Button>
                    </section>
                )}

                {organizer && (
                    <section className="space-y-4">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h2 className="text-lg font-semibold">Tus torneos</h2>
                            <Button size="sm" asChild>
                                <Link href={route('tournaments.create')}>
                                    <Plus className="size-4" />
                                    Nuevo torneo
                                </Link>
                            </Button>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <StatCard icon={Trophy} label="Torneos totales" value={organizer.tournaments_total} />
                            <StatCard icon={CalendarClock} label="Inscripciones abiertas" value={organizer.tournaments_open} />
                            <StatCard icon={ClipboardList} label="Inscripciones por revisar" value={organizer.pending_registrations} />
                        </div>
                        {organizer.upcoming_tournaments.length > 0 ? (
                            <Card>
                                <CardContent className="divide-y p-0">
                                    {organizer.upcoming_tournaments.map((tournament) => (
                                        <TournamentRow key={tournament.id} tournament={tournament} />
                                    ))}
                                </CardContent>
                            </Card>
                        ) : (
                            organizer.tournaments_total === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    Todavía no has creado ningún torneo.{' '}
                                    <Link href={route('tournaments.create')} className="underline">
                                        Crea el primero
                                    </Link>
                                    .
                                </p>
                            )
                        )}
                    </section>
                )}

                {referee && (
                    <section className="space-y-4">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h2 className="text-lg font-semibold">Arbitraje</h2>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('referee.index')}>Ver todos mis partidos</Link>
                            </Button>
                        </div>
                        <StatCard icon={Swords} label="Partidos por arbitrar" value={referee.upcoming_count} />
                        {referee.upcoming_matches.length > 0 && (
                            <Card>
                                <CardContent className="divide-y p-0">
                                    {referee.upcoming_matches.map((match) => (
                                        <Link
                                            key={match.id}
                                            href={route('tournaments.divisions.matches.score', [
                                                match.tournament_id,
                                                match.division_id,
                                                match.id,
                                            ])}
                                            className="flex items-center justify-between gap-3 p-3 text-sm hover:bg-accent"
                                        >
                                            <span>
                                                {match.entrant1_name} vs {match.entrant2_name}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {match.tournament_name} · {match.division_name}
                                            </span>
                                        </Link>
                                    ))}
                                </CardContent>
                            </Card>
                        )}
                    </section>
                )}

                {player && (
                    <section className="space-y-4">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h2 className="text-lg font-semibold">Tu progreso</h2>
                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('games.index')}>
                                        <Swords className="size-4" />
                                        Retos
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('public.players.show', player.player_id)}>Ver mi perfil</Link>
                                </Button>
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-4">
                            <StatCard icon={Trophy} label="Rating" value={player.rating_current} />
                            <StatCard icon={Zap} label={`Nivel ${player.level}`} value={`${player.xp_total} XP`} />
                            <StatCard icon={Swords} label="Partidos jugados" value={player.matches_played} />
                            <StatCard
                                icon={Sparkles}
                                label="% Victorias"
                                value={player.matches_played > 0 ? `${((player.matches_won / player.matches_played) * 100).toFixed(0)}%` : '—'}
                            />
                        </div>
                        {player.is_elite && (
                            <Badge className="border-transparent bg-amber-500/15 text-amber-700 dark:text-amber-400">
                                <Sparkles className="mr-1 size-3.5" />
                                Jugador élite
                            </Badge>
                        )}
                        {player.upcoming_registrations.length > 0 && (
                            <div>
                                <p className="mb-2 text-sm font-medium">Tus torneos</p>
                                <Card>
                                    <CardContent className="divide-y p-0">
                                        {player.upcoming_registrations.map((registration) => (
                                            <Link
                                                key={registration.id}
                                                href={route('public.tournaments.show', registration.tournament?.slug)}
                                                className="flex items-center justify-between gap-3 p-3 text-sm hover:bg-accent"
                                            >
                                                <span>{registration.tournament?.name}</span>
                                                <Badge variant="secondary">{registration.status}</Badge>
                                            </Link>
                                        ))}
                                    </CardContent>
                                </Card>
                            </div>
                        )}
                    </section>
                )}

                {!hasAnySection && (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-16 text-center">
                        <Trophy className="size-10 text-muted-foreground" />
                        <div>
                            <p className="font-medium">Todavía no tienes actividad</p>
                            <p className="text-sm text-muted-foreground">Explora los torneos disponibles o revisa el ranking para empezar.</p>
                        </div>
                        <div className="flex gap-2">
                            <Button asChild>
                                <Link href={route('public.tournaments.index')}>Ver torneos</Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route('public.players.ranking')}>Ver ranking</Link>
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function StatCard({ icon: Icon, label, value }: { icon: typeof Trophy; label: string; value: number | string }) {
    return (
        <Card>
            <CardContent className="flex items-center gap-3 pt-6">
                <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Icon className="size-5" />
                </div>
                <div>
                    <p className="text-2xl font-bold tabular-nums">{value}</p>
                    <p className="text-xs text-muted-foreground">{label}</p>
                </div>
            </CardContent>
        </Card>
    );
}

function TournamentRow({ tournament }: { tournament: Tournament }) {
    return (
        <Link href={route('tournaments.show', tournament.id)} className="flex items-center justify-between gap-3 p-3 text-sm hover:bg-accent">
            <div>
                <p className="font-medium">{tournament.name}</p>
                <p className="text-xs text-muted-foreground">{formatDateRange(tournament.start_date, tournament.end_date)}</p>
            </div>
            <TournamentStatusBadge status={tournament.status} />
        </Link>
    );
}
