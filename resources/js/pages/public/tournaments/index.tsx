import { TournamentStatusBadge } from '@/components/tournaments/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/public-layout';
import { type PaginatedData, type Tournament } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays, MapPin, Trophy, Users } from 'lucide-react';

export default function PublicTournamentsIndex({ tournaments }: { tournaments: PaginatedData<Tournament> }) {
    return (
        <PublicLayout>
            <Head title="Torneos" />
            <div className="mb-8 space-y-2">
                <h1 className="text-3xl font-bold tracking-tight">Torneos de tenis de mesa</h1>
                <p className="text-muted-foreground">Explora los eventos disponibles e inscríbete en la categoría que te corresponda.</p>
            </div>

            {tournaments.data.length === 0 ? (
                <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-16 text-center">
                    <Trophy className="size-10 text-muted-foreground" />
                    <p className="text-muted-foreground">Por ahora no hay torneos publicados. ¡Vuelve pronto!</p>
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {tournaments.data.map((tournament) => (
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
                                        {tournament.start_date} — {tournament.end_date}
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
        </PublicLayout>
    );
}
