import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { type TournamentStatus } from '@/types';

const STATUS_CONFIG: Record<TournamentStatus, { label: string; className: string }> = {
    draft: { label: 'Borrador', className: 'bg-muted text-muted-foreground border-transparent' },
    registration_open: { label: 'Inscripciones abiertas', className: 'bg-green-500/15 text-green-700 dark:text-green-400 border-transparent' },
    registration_closed: { label: 'Inscripciones cerradas', className: 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border-transparent' },
    in_progress: { label: 'En curso', className: 'bg-blue-500/15 text-blue-700 dark:text-blue-400 border-transparent' },
    completed: { label: 'Finalizado', className: 'bg-slate-500/15 text-slate-700 dark:text-slate-300 border-transparent' },
    cancelled: { label: 'Cancelado', className: 'bg-red-500/15 text-red-700 dark:text-red-400 border-transparent' },
};

export function TournamentStatusBadge({ status, className }: { status: TournamentStatus; className?: string }) {
    const config = STATUS_CONFIG[status];

    return <Badge className={cn(config.className, className)}>{config.label}</Badge>;
}
