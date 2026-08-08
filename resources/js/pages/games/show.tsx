import { ConfirmDialog } from '@/components/confirm-dialog';
import { MatchScoringConsole } from '@/components/matches/match-scoring-console';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type MatchScoreState } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Check, Copy, MessageCircle, X } from 'lucide-react';
import { useState } from 'react';

const MATCH_TYPE_LABELS: Record<string, string> = {
    ranked: 'Clasificatorio',
    friendly: 'Amistoso',
};

export default function GameShow({ match: initialMatch }: { match: MatchScoreState }) {
    const [match, setMatch] = useState(initialMatch);
    const [copied, setCopied] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Retos', href: '/retos' },
        { title: `Reto ${match.code}`, href: '#' },
    ];

    const shareUrl = typeof window !== 'undefined' ? window.location.href : '';
    const whatsappMessage = `¡Te reto a un partido de tenis de mesa en Ping Masters! Únete con el código ${match.code}: ${shareUrl}`;

    function copyCode() {
        navigator.clipboard.writeText(match.code ?? '');
        setCopied(true);
        setTimeout(() => setCopied(false), 1500);
    }

    function cancelMatch() {
        router.post(route('games.cancel', match.code));
        setCancelOpen(false);
    }

    const canCancel = ['waiting', 'ready', 'in_progress'].includes(match.status);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Reto ${match.code}`} />
            <div className="mx-auto max-w-3xl space-y-6 p-4">
                <div className="flex items-center justify-center gap-2">
                    <Badge variant={match.match_type === 'ranked' ? 'default' : 'secondary'}>
                        {MATCH_TYPE_LABELS[match.match_type ?? 'friendly']}
                    </Badge>
                    {canCancel && (
                        <Button variant="ghost" size="sm" className="text-muted-foreground hover:text-destructive" onClick={() => setCancelOpen(true)}>
                            <X className="size-4" />
                            Cancelar reto
                        </Button>
                    )}
                </div>

                <ConfirmDialog
                    open={cancelOpen}
                    onOpenChange={setCancelOpen}
                    title="Cancelar reto"
                    description="¿Seguro que quieres cancelar este reto? Esta acción no se puede deshacer."
                    confirmLabel="Sí, cancelar"
                    cancelLabel="No, volver"
                    destructive
                    onConfirm={cancelMatch}
                />

                {match.status === 'waiting' && (
                    <Card>
                        <CardContent className="space-y-4 p-6 text-center">
                            <p className="text-muted-foreground">Comparte este código con tu rival para que se una al reto.</p>
                            <p className="text-4xl font-bold tracking-[0.3em]">{match.code}</p>
                            <div className="flex flex-wrap items-center justify-center gap-2">
                                <Button variant="outline" onClick={copyCode}>
                                    {copied ? <Check className="size-4" /> : <Copy className="size-4" />}
                                    {copied ? 'Copiado' : 'Copiar enlace'}
                                </Button>
                                <Button asChild>
                                    <a
                                        href={`https://wa.me/?text=${encodeURIComponent(whatsappMessage)}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <MessageCircle className="size-4" />
                                        Invitar por WhatsApp
                                    </a>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <MatchScoringConsole
                    match={match}
                    channel="casual-match"
                    forfeitLabel="Abandonar"
                    onMatchChange={setMatch}
                    routes={{
                        start: route('games.start', match.code),
                        point: route('games.point', match.code),
                        undo: route('games.undo', match.code),
                        forfeit: route('games.forfeit', match.code),
                    }}
                />
            </div>
        </AppLayout>
    );
}
