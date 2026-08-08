import { DivisionTemplateForm } from '@/components/templates/division-template-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type DivisionTemplate } from '@/types';
import { Head } from '@inertiajs/react';

export default function EditDivisionTemplate({ template }: { template: DivisionTemplate }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Plantillas de categorías', href: '/plantillas/categorias' },
        { title: template.name, href: `/plantillas/categorias/${template.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${template.name}`} />
            <div className="mx-auto w-full max-w-2xl space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Editar plantilla</h1>
                    <p className="text-muted-foreground">{template.name}</p>
                </div>
                <DivisionTemplateForm template={template} />
            </div>
        </AppLayout>
    );
}
