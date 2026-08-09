import { TournamentStatusBadge } from '@/components/tournaments/status-badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import PublicLayout from '@/layouts/public-layout';
import { formatDateRange } from '@/lib/format-date';
import { type Player, type SharedData, type Tournament } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { CalendarDays, ClipboardList, MapPin, Swords, Trophy, TrendingUp, Users, Zap } from 'lucide-react';

interface Props {
    tournaments: Tournament[];
    topPlayers: Player[];
    stats: { tournaments: number; players: number; matches_played: number };
}

export default function Home({ tournaments, topPlayers, stats }: Props) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();

    return (
        <PublicLayout>
            <Head title="Ping Masters" />

            <section
                className="relative overflow-hidden rounded-2xl border bg-cover bg-top px-6 pt-56 pb-10 text-center sm:px-12 sm:pt-64"
                style={{ backgroundImage: "url('/banner.png')" }}
            >
                <div className="absolute inset-0 bg-gradient-to-t from-background via-background/85 to-transparent" />
                <motion.div
                    initial={{ opacity: 0, y: 16 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.5, ease: 'easeOut' }}
                    className="relative mx-auto max-w-2xl space-y-6"
                >
                    <p className="text-lg text-muted-foreground">
                        La plataforma para organizar torneos de tenis de mesa: inscripciones, sorteo automático, marcador en vivo, ranking ELO y
                        logros — todo en un solo lugar.
                    </p>
                    <div className="flex flex-wrap items-center justify-center gap-3">
                        <Button size="lg" asChild>
                            <Link href={route('public.tournaments.index')}>
                                <Trophy className="size-4" />
                                Ver torneos
                            </Link>
                        </Button>
                        <Button size="lg" variant="outline" asChild>
                            <Link href={route('public.players.ranking')}>
                                <TrendingUp className="size-4" />
                                Ver ranking
                            </Link>
                        </Button>
                        {!auth.user && (
                            <Button size="lg" variant="ghost" asChild>
                                <Link href={route('register')}>Crear cuenta</Link>
                            </Button>
                        )}
                    </div>
                </motion.div>
            </section>

            <section className="mt-10 grid gap-4 sm:grid-cols-3">
                <StatCard icon={Trophy} label="Torneos" value={stats.tournaments} />
                <StatCard icon={Users} label="Jugadores" value={stats.players} />
                <StatCard icon={Swords} label="Partidos jugados" value={stats.matches_played} />
            </section>

            <section className="mt-14">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-xl font-semibold">Torneos activos</h2>
                    <Link href={route('public.tournaments.index')} className="text-sm text-muted-foreground hover:underline">
                        Ver todos →
                    </Link>
                </div>

                {tournaments.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-14 text-center">
                        <Trophy className="size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">Por ahora no hay torneos activos. ¡Vuelve pronto!</p>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {tournaments.map((tournament) => (
                            <Link key={tournament.id} href={route('public.tournaments.show', tournament.slug)}>
                                <Card className="h-full transition-shadow hover:shadow-md">
                                    <CardHeader>
                                        <div className="flex items-start justify-between gap-2">
                                            <CardTitle className="text-lg">{tournament.name}</CardTitle>
                                            <TournamentStatusBadge status={tournament.status} />
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-2 text-sm text-muted-foreground">
                                        <p className="flex items-center gap-2">
                                            <CalendarDays className="size-4" />
                                            {formatDateRange(tournament.start_date, tournament.end_date)}
                                        </p>
                                        {tournament.city && (
                                            <p className="flex items-center gap-2">
                                                <MapPin className="size-4" />
                                                {tournament.city}
                                            </p>
                                        )}
                                        <p className="flex items-center gap-2">
                                            <Users className="size-4" />
                                            {tournament.registrations_count ?? 0} inscritos · {tournament.divisions_count ?? 0} categorías
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </section>

            <section className="mt-14 grid gap-8 lg:grid-cols-[1fr_360px]">
                <div>
                    <h2 className="mb-4 text-xl font-semibold">Cómo funciona</h2>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <HowItWorksStep
                            icon={ClipboardList}
                            title="Inscríbete"
                            description="Elige tu categoría y completa el formulario de inscripción del torneo."
                        />
                        <HowItWorksStep
                            icon={Swords}
                            title="Juega"
                            description="Sigue tu llave, mira el marcador en vivo y juega según las reglas oficiales ITTF."
                        />
                        <HowItWorksStep
                            icon={Zap}
                            title="Sube de nivel"
                            description="Gana rating ELO, XP y logros con cada partido que juegas."
                        />
                    </div>
                </div>

                <div>
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-xl font-semibold">Top del ranking</h2>
                        <Link href={route('public.players.ranking')} className="text-sm text-muted-foreground hover:underline">
                            Ver todo →
                        </Link>
                    </div>
                    <Card>
                        <CardContent className="divide-y p-0">
                            {topPlayers.length === 0 && <p className="p-4 text-sm text-muted-foreground">Aún no hay jugadores clasificados.</p>}
                            {topPlayers.map((player, index) => (
                                <Link
                                    key={player.id}
                                    href={route('public.players.show', player.id)}
                                    className="flex items-center gap-3 p-3 hover:bg-accent"
                                >
                                    <span className="w-4 text-sm text-muted-foreground">{index + 1}</span>
                                    <Avatar className="size-8">
                                        <AvatarImage src={player.user?.avatar ?? undefined} alt={player.user?.name ?? ''} />
                                        <AvatarFallback className="text-xs">{getInitials(player.user?.name ?? '?')}</AvatarFallback>
                                    </Avatar>
                                    <span className="flex-1 truncate text-sm font-medium">{player.user?.name}</span>
                                    <span className="text-sm font-semibold tabular-nums">{player.rating_current}</span>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </section>
        </PublicLayout>
    );
}

function StatCard({ icon: Icon, label, value }: { icon: typeof Trophy; label: string; value: number }) {
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

function HowItWorksStep({ icon: Icon, title, description }: { icon: typeof Trophy; title: string; description: string }) {
    return (
        <Card>
            <CardContent className="space-y-2 pt-6">
                <div className="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Icon className="size-4.5" />
                </div>
                <p className="font-medium">{title}</p>
                <p className="text-sm text-muted-foreground">{description}</p>
            </CardContent>
        </Card>
    );
}
