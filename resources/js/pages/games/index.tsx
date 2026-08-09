import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Swords } from 'lucide-react';
import { FormEventHandler } from 'react';

const MATCH_TYPE_LABELS: Record<string, string> = {
    ranked: 'Clasificatorio',
    friendly: 'Amistoso',
};

const STATUS_LABELS: Record<string, string> = {
    waiting: 'Esperando rival',
    ready: 'Listo para empezar',
    in_progress: 'En curso',
    completed: 'Finalizado',
    cancelled: 'Cancelado',
};

interface CasualMatchSummary {
    id: number;
    code: string;
    match_type: 'ranked' | 'friendly';
    status: string;
    creator_name: string;
    opponent_name: string | null;
    score_summary: string | null;
    is_mine_to_join: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Retos', href: '/retos' }];

export default function GamesIndex({ matches }: { matches: CasualMatchSummary[] }) {
    const createForm = useForm({
        match_type: 'friendly',
        best_of: '5',
        points_to_win: '11',
    });

    const joinForm = useForm({ code: '' });

    const submitCreate: FormEventHandler = (e) => {
        e.preventDefault();
        createForm.post(route('games.store'));
    };

    const submitJoin: FormEventHandler = (e) => {
        e.preventDefault();
        joinForm.post(route('games.join'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Retos" />
            <div className="mx-auto max-w-3xl space-y-8 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Retos</h1>
                    <p className="text-muted-foreground">Desafía a otro jugador a un partido individual, clasificatorio o amistoso.</p>
                </div>

                <div className="grid gap-6 sm:grid-cols-2">
                    <Card>
                        <CardContent className="space-y-4 p-4">
                            <h2 className="font-semibold">Crear un reto</h2>
                            <form onSubmit={submitCreate} className="space-y-3">
                                <div className="grid gap-1.5">
                                    <Label>Tipo</Label>
                                    <Select
                                        value={createForm.data.match_type}
                                        onValueChange={(value) => createForm.setData('match_type', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="friendly">Amistoso (no afecta tu ranking)</SelectItem>
                                            <SelectItem value="ranked">Clasificatorio (afecta tu ranking)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="grid gap-1.5">
                                        <Label>Mejor de</Label>
                                        <Select value={createForm.data.best_of} onValueChange={(value) => createForm.setData('best_of', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="5">5 sets</SelectItem>
                                                <SelectItem value="7">7 sets</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>Puntos por set</Label>
                                        <Input
                                            type="number"
                                            min={5}
                                            max={21}
                                            value={createForm.data.points_to_win}
                                            onChange={(e) => createForm.setData('points_to_win', e.target.value)}
                                        />
                                    </div>
                                </div>
                                <Button type="submit" disabled={createForm.processing} className="w-full">
                                    <Swords className="size-4" />
                                    Crear reto
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="space-y-4 p-4">
                            <h2 className="font-semibold">Unirme con un código</h2>
                            <form onSubmit={submitJoin} className="space-y-3">
                                <div className="grid gap-1.5">
                                    <Label>Código del reto</Label>
                                    <Input
                                        value={joinForm.data.code}
                                        onChange={(e) => joinForm.setData('code', e.target.value.toUpperCase())}
                                        placeholder="Ej: A1B2C3"
                                        className="uppercase tracking-widest"
                                    />
                                    <InputError message={joinForm.errors.code} />
                                </div>
                                <Button type="submit" disabled={joinForm.processing} variant="outline" className="w-full">
                                    Unirme al reto
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-3">
                    <h2 className="text-lg font-semibold">Tus retos</h2>
                    {matches.length === 0 && <p className="text-sm text-muted-foreground">Aún no tienes retos. ¡Crea uno arriba!</p>}
                    {matches.map((match) => (
                        <Link key={match.id} href={route('games.show', match.code)}>
                            <Card className="transition-colors hover:bg-accent">
                                <CardContent className="flex flex-wrap items-center justify-between gap-3 p-4">
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {match.creator_name} {match.opponent_name ? `vs ${match.opponent_name}` : ''}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {MATCH_TYPE_LABELS[match.match_type]} · Código {match.code}
                                            {match.score_summary ? ` · ${match.score_summary}` : ''}
                                        </p>
                                    </div>
                                    <Badge
                                        className="shrink-0"
                                        variant={match.status === 'completed' || match.status === 'cancelled' ? 'secondary' : 'default'}
                                    >
                                        {STATUS_LABELS[match.status] ?? match.status}
                                    </Badge>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
