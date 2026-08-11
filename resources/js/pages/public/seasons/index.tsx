import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import SmartLayout from '@/layouts/smart-layout';
import { formatDate } from '@/lib/format-date';
import { type BreadcrumbItem, type Season } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarRange, ChevronRight } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ranking', href: '/ranking' },
    { title: 'Temporadas', href: '/temporadas' },
];

export default function SeasonsIndex({ seasons }: { seasons: Season[] }) {
    return (
        <SmartLayout breadcrumbs={breadcrumbs}>
            <Head title="Temporadas" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Temporadas</h1>
                    <p className="text-muted-foreground">El ranking se reinicia por temporadas — aquí queda el histórico de cada una.</p>
                </div>

                <div className="space-y-3">
                    {seasons.map((season) => (
                        <Link key={season.id} href={route('public.seasons.show', season.id)}>
                            <Card className="transition-colors hover:bg-accent">
                                <CardContent className="flex items-center justify-between gap-3 p-4">
                                    <div className="flex items-center gap-3">
                                        <CalendarRange className="size-5 text-muted-foreground" />
                                        <div>
                                            <p className="flex items-center gap-2 font-medium">
                                                {season.name}
                                                {!season.ended_at && <Badge>Actual</Badge>}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {formatDate(season.started_at)}
                                                {season.ended_at ? ` — ${formatDate(season.ended_at)}` : ' — en curso'}
                                                {' · '}
                                                {season.standings_count ?? 0} jugadores
                                            </p>
                                        </div>
                                    </div>
                                    <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </SmartLayout>
    );
}
