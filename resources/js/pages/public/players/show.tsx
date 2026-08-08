import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useInitials } from '@/hooks/use-initials';
import SmartLayout from '@/layouts/smart-layout';
import { cn } from '@/lib/utils';
import { type Achievement, type BreadcrumbItem, type Player, type TournamentRegistration } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BadgeCheck, Sparkles, Trophy } from 'lucide-react';
import { CartesianGrid, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface Props {
    player: Player;
    ratingHistory: { rating: number; date: string }[];
    registrations: TournamentRegistration[];
    levelName: string | null;
    currentLevelXp: number;
    nextLevelXp: number | null;
}

const ALL_ACHIEVEMENT_ICONS: Record<string, string> = {
    first_win: '🏓',
    ten_wins: '🥉',
    veteran: '🎽',
    win_streak_5: '🔥',
    champion: '🏆',
};

export default function PlayerShow({ player, ratingHistory, registrations, levelName, currentLevelXp, nextLevelXp }: Props) {
    const getInitials = useInitials();

    const xpIntoLevel = player.xp_total - currentLevelXp;
    const xpForLevel = nextLevelXp ? nextLevelXp - currentLevelXp : null;
    const progressPct = xpForLevel ? Math.min(100, Math.round((xpIntoLevel / xpForLevel) * 100)) : 100;

    const unlockedCodes = new Set((player.achievements ?? []).map((a) => a.code));

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ranking', href: '/ranking' },
        { title: player.user?.name ?? 'Jugador', href: '#' },
    ];

    return (
        <SmartLayout breadcrumbs={breadcrumbs}>
            <Head title={player.user?.name ?? 'Jugador'} />

            <div className="space-y-8">
                <div className="flex flex-wrap items-center gap-4">
                    <Avatar className="size-16">
                        <AvatarFallback className="text-xl">{getInitials(player.user?.name ?? '?')}</AvatarFallback>
                    </Avatar>
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold tracking-tight">{player.user?.name}</h1>
                            {player.is_elite && (
                                <Badge className="border-transparent bg-amber-500/15 text-amber-700 dark:text-amber-400">
                                    <Sparkles className="mr-1 size-3.5" />
                                    Élite
                                </Badge>
                            )}
                            {player.user?.email_verified_at && (
                                <Badge className="border-transparent bg-blue-500/15 text-blue-700 dark:text-blue-400">
                                    <BadgeCheck className="mr-1 size-3.5" />
                                    Verificado
                                </Badge>
                            )}
                        </div>
                        <p className="text-muted-foreground">{player.club?.name ?? 'Sin club'}</p>
                    </div>
                </div>

                <Card>
                    <CardContent className="space-y-2 pt-6">
                        <div className="flex items-center justify-between text-sm">
                            <span className="font-medium">
                                Nivel {player.level} · {levelName}
                            </span>
                            <span className="text-muted-foreground">
                                {player.xp_total} XP{nextLevelXp ? ` · ${nextLevelXp - player.xp_total} para el siguiente nivel` : ' · nivel máximo'}
                            </span>
                        </div>
                        <div className="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                            <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${progressPct}%` }} />
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-4">
                    <StatCard label="Rating" value={player.rating_current} />
                    <StatCard label="Partidos jugados" value={player.matches_played} />
                    <StatCard label="Partidos ganados" value={player.matches_won} />
                    <StatCard label="% Victorias" value={player.matches_played > 0 ? `${((player.matches_won / player.matches_played) * 100).toFixed(0)}%` : '—'} />
                </div>

                {ratingHistory.length > 1 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Evolución del rating</CardTitle>
                        </CardHeader>
                        <CardContent className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={ratingHistory}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                                    <XAxis dataKey="date" tick={{ fontSize: 12 }} />
                                    <YAxis domain={['auto', 'auto']} tick={{ fontSize: 12 }} width={40} />
                                    <Tooltip />
                                    <Line type="monotone" dataKey="rating" stroke="currentColor" className="text-primary" strokeWidth={2} dot={false} />
                                </LineChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Logros</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                        {Object.entries(ALL_ACHIEVEMENT_ICONS).map(([code, icon]) => {
                            const achievement = (player.achievements ?? []).find((a: Achievement) => a.code === code);
                            const unlocked = unlockedCodes.has(code);

                            return (
                                <div
                                    key={code}
                                    className={cn(
                                        'flex flex-col items-center gap-1 rounded-lg border p-3 text-center',
                                        unlocked ? 'bg-card' : 'opacity-40 grayscale',
                                    )}
                                >
                                    <span className="text-2xl">{icon}</span>
                                    <span className="text-xs font-medium">{achievement?.name ?? code}</span>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Torneos</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {registrations.length === 0 && <p className="text-sm text-muted-foreground">Sin participaciones todavía.</p>}
                        {registrations.map((registration) => (
                            <Link
                                key={registration.id}
                                href={route('public.tournaments.show', registration.tournament?.slug)}
                                className="flex items-center justify-between rounded-md border px-3 py-2 text-sm hover:bg-accent"
                            >
                                <span className="flex items-center gap-2">
                                    <Trophy className="size-4 text-muted-foreground" />
                                    {registration.tournament?.name}
                                </span>
                                <Badge variant="secondary">{registration.status}</Badge>
                            </Link>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </SmartLayout>
    );
}

function StatCard({ label, value }: { label: string; value: string | number }) {
    return (
        <Card>
            <CardContent className="pt-6 text-center">
                <p className="text-2xl font-bold tabular-nums">{value}</p>
                <p className="text-xs text-muted-foreground">{label}</p>
            </CardContent>
        </Card>
    );
}
