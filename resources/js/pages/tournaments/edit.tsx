import { TournamentForm } from '@/components/tournaments/tournament-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type DivisionTemplate, type FormTemplate, type Tournament } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    tournament: Tournament;
    divisionTemplates: DivisionTemplate[];
    formTemplates: FormTemplate[];
}

export default function EditTournament({ tournament, divisionTemplates, formTemplates }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Torneos', href: '/tournaments' },
        { title: tournament.name, href: `/tournaments/${tournament.id}` },
        { title: 'Editar', href: `/tournaments/${tournament.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${tournament.name}`} />
            <div className="mx-auto w-full max-w-4xl space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Editar torneo</h1>
                    <p className="text-muted-foreground">{tournament.name}</p>
                </div>
                <TournamentForm tournament={tournament} divisionTemplates={divisionTemplates} formTemplates={formTemplates} />
            </div>
        </AppLayout>
    );
}
