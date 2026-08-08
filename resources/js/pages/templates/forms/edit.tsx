import { FormTemplateForm } from '@/components/templates/form-template-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type FormTemplate } from '@/types';
import { Head } from '@inertiajs/react';

export default function EditFormTemplate({ template }: { template: FormTemplate }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Plantillas de formularios', href: '/plantillas/formularios' },
        { title: template.name, href: `/plantillas/formularios/${template.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${template.name}`} />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Editar plantilla</h1>
                    <p className="text-muted-foreground">{template.name}</p>
                </div>
                <FormTemplateForm template={template} />
            </div>
        </AppLayout>
    );
}
