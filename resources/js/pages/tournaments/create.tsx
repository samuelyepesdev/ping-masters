import { TournamentForm } from '@/components/tournaments/tournament-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Torneos', href: '/tournaments' },
    { title: 'Nuevo torneo', href: '/tournaments/create' },
];

export default function CreateTournament() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo torneo" />
            <div className="mx-auto w-full max-w-4xl space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Crear torneo</h1>
                    <p className="text-muted-foreground">Configura los datos generales, las categorías y el formulario de inscripción.</p>
                </div>
                <TournamentForm />
            </div>
        </AppLayout>
    );
}
