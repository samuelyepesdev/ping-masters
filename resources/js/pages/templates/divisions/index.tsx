import { ConfirmDialog } from '@/components/confirm-dialog';
import { CATEGORY_LABELS, FORMAT_LABELS } from '@/components/tournaments/division-editor';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type DivisionTemplate } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { LayoutTemplate, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Plantillas de categorías', href: '/plantillas/categorias' }];

export default function DivisionTemplatesIndex({ templates }: { templates: DivisionTemplate[] }) {
    const [pendingDelete, setPendingDelete] = useState<DivisionTemplate | null>(null);

    function destroy() {
        if (!pendingDelete) return;
        router.delete(route('templates.divisions.destroy', pendingDelete.id));
        setPendingDelete(null);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Plantillas de categorías" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Plantillas de categorías</h1>
                        <p className="text-muted-foreground">Reutilízalas al crear cualquier torneo, sin volver a configurarlas desde cero.</p>
                    </div>
                    <Button asChild>
                        <Link href={route('templates.divisions.create')}>
                            <Plus className="size-4" />
                            Nueva plantilla
                        </Link>
                    </Button>
                </div>

                {templates.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-16 text-center">
                        <LayoutTemplate className="size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">Aún no tienes plantillas de categorías.</p>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Modalidad</TableHead>
                                    <TableHead>Formato</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {templates.map((template) => (
                                    <TableRow key={template.id}>
                                        <TableCell className="font-medium">{template.name}</TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">{CATEGORY_LABELS[template.category_type]}</Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{FORMAT_LABELS[template.format]}</TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={route('templates.divisions.edit', template.id)}>Editar</Link>
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => setPendingDelete(template)}>
                                                <Trash2 className="size-4 text-muted-foreground hover:text-destructive" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <ConfirmDialog
                    open={pendingDelete !== null}
                    onOpenChange={(open) => !open && setPendingDelete(null)}
                    title="Eliminar plantilla"
                    description={`¿Eliminar la plantilla «${pendingDelete?.name}»? Esta acción no se puede deshacer.`}
                    confirmLabel="Eliminar"
                    destructive
                    onConfirm={destroy}
                />
            </div>
        </AppLayout>
    );
}
