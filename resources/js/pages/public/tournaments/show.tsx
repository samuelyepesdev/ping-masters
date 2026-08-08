import { TournamentStatusBadge } from '@/components/tournaments/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import SmartLayout from '@/layouts/smart-layout';
import { type BreadcrumbItem, type SharedData, type Tournament, type TournamentRegistration } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarDays, MapPin } from 'lucide-react';

const FORMAT_LABELS: Record<string, string> = {
    single_elimination: 'Eliminación directa',
    double_elimination: 'Doble eliminación',
    round_robin: 'Todos contra todos',
    swiss: 'Sistema suizo',
    group_knockout: 'Grupos + eliminación',
};

export default function PublicTournamentShow({
    tournament,
    userRegistration,
    isRegistrationOpen,
}: {
    tournament: Tournament;
    userRegistration: TournamentRegistration | null;
    isRegistrationOpen: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Explorar torneos', href: '/torneos' },
        { title: tournament.name, href: '#' },
    ];

    return (
        <SmartLayout breadcrumbs={breadcrumbs}>
            <Head title={tournament.name} />

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-2">
                    <div className="flex items-center gap-3">
                        <h1 className="text-3xl font-bold tracking-tight">{tournament.name}</h1>
                        <TournamentStatusBadge status={tournament.status} />
                    </div>
                    <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                        <span className="flex items-center gap-1.5">
                            <CalendarDays className="size-4" />
                            {tournament.start_date} — {tournament.end_date}
                        </span>
                        {tournament.venue && (
                            <span className="flex items-center gap-1.5">
                                <MapPin className="size-4" />
                                {tournament.venue}
                                {tournament.city ? `, ${tournament.city}` : ''}
                            </span>
                        )}
                    </div>
                </div>

                <div>
                    {userRegistration ? (
                        <Badge variant="secondary" className="px-3 py-1.5 text-sm">
                            Ya estás inscrito ({userRegistration.status})
                        </Badge>
                    ) : isRegistrationOpen ? (
                        auth.user ? (
                            <Button size="lg" asChild>
                                <Link href={route('public.tournaments.register', tournament.slug)}>Inscribirme</Link>
                            </Button>
                        ) : (
                            <Button size="lg" asChild>
                                <Link href={route('login')}>Inicia sesión para inscribirte</Link>
                            </Button>
                        )
                    ) : (
                        <Badge variant="outline" className="px-3 py-1.5 text-sm">
                            Inscripciones cerradas
                        </Badge>
                    )}
                </div>
            </div>

            {tournament.description && <p className="mb-8 max-w-3xl text-muted-foreground">{tournament.description}</p>}

            <h2 className="mb-3 text-xl font-semibold">Categorías</h2>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {tournament.divisions?.map((division) => (
                    <Card key={division.id}>
                        <CardHeader>
                            <CardTitle className="text-base">{division.name}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm text-muted-foreground">
                            <p>{FORMAT_LABELS[division.format]}</p>
                            <p>
                                Mejor de {division.best_of} · a {division.points_to_win} puntos
                            </p>
                            {(division.min_age || division.max_age) && (
                                <p>
                                    Edad: {division.min_age ?? '–'} a {division.max_age ?? '–'}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </SmartLayout>
    );
}
