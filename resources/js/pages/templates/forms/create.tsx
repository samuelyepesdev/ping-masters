import { FormTemplateForm } from '@/components/templates/form-template-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Plantillas de formularios', href: '/plantillas/formularios' },
    { title: 'Nueva plantilla', href: '/plantillas/formularios/create' },
];

export default function CreateFormTemplate({ redirectTo }: { redirectTo?: string | null }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva plantilla de formulario" />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Nueva plantilla de formulario</h1>
                    <p className="text-muted-foreground">Constrúyela una vez y cárgala en cualquier torneo.</p>
                    {redirectTo && (
                        <Link href={redirectTo} className="mt-1 inline-block text-sm text-muted-foreground hover:underline">
                            ← Volver sin crear
                        </Link>
                    )}
                </div>
                <FormTemplateForm redirectTo={redirectTo} />
            </div>
        </AppLayout>
    );
}
