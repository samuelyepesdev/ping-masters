import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type FormTemplate } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { LayoutTemplate, Plus, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Plantillas de formularios', href: '/plantillas/formularios' }];

export default function FormTemplatesIndex({ templates }: { templates: FormTemplate[] }) {
    function destroy(template: FormTemplate) {
        if (!confirm(`¿Eliminar la plantilla «${template.name}»?`)) return;
        router.delete(route('templates.forms.destroy', template.id));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Plantillas de formularios" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Plantillas de formularios</h1>
                        <p className="text-muted-foreground">Formularios de inscripción reutilizables para tus torneos.</p>
                    </div>
                    <Button asChild>
                        <Link href={route('templates.forms.create')}>
                            <Plus className="size-4" />
                            Nueva plantilla
                        </Link>
                    </Button>
                </div>

                {templates.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-16 text-center">
                        <LayoutTemplate className="size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">Aún no tienes plantillas de formularios.</p>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead>Campos</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {templates.map((template) => (
                                    <TableRow key={template.id}>
                                        <TableCell className="font-medium">{template.name}</TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{template.description ?? '—'}</TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{template.fields_count ?? 0}</TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={route('templates.forms.edit', template.id)}>Editar</Link>
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => destroy(template)}>
                                                <Trash2 className="size-4 text-muted-foreground hover:text-destructive" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
