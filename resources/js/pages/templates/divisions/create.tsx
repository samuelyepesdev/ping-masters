import { DivisionTemplateForm } from '@/components/templates/division-template-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Plantillas de categorías', href: '/plantillas/categorias' },
    { title: 'Nueva plantilla', href: '/plantillas/categorias/create' },
];

export default function CreateDivisionTemplate({ redirectTo }: { redirectTo?: string | null }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva plantilla de categoría" />
            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Nueva plantilla de categoría</h1>
                    <p className="text-muted-foreground">Configúrala una vez y reutilízala en cualquier torneo.</p>
                    {redirectTo && (
                        <Link href={redirectTo} className="mt-1 inline-block text-sm text-muted-foreground hover:underline">
                            ← Volver sin crear
                        </Link>
                    )}
                </div>
                <DivisionTemplateForm redirectTo={redirectTo} />
            </div>
        </AppLayout>
    );
}
