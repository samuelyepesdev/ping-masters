import { ConfirmDialog } from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { type FormTemplate, type RegistrationFieldType } from '@/types';
import { DndContext, type DragEndEvent, closestCenter, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { SortableContext, arrayMove, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Link } from '@inertiajs/react';
import { GripVertical, LayoutTemplate, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

export interface DraftRegistrationField {
    key: string;
    id?: number;
    label: string;
    field_type: RegistrationFieldType;
    options: string[];
    placeholder: string;
    help_text: string;
    is_required: boolean;
    [key: string]: string | number | boolean | string[] | undefined;
}

const FIELD_TYPE_LABELS: Record<RegistrationFieldType, string> = {
    text: 'Texto corto',
    textarea: 'Texto largo',
    number: 'Número',
    email: 'Correo electrónico',
    phone: 'Teléfono',
    date: 'Fecha',
    select: 'Selección única (lista)',
    radio: 'Selección única (botones)',
    checkbox: 'Casilla (sí/no)',
    checkbox_group: 'Selección múltiple',
};

const CHOICE_TYPES: RegistrationFieldType[] = ['select', 'radio', 'checkbox_group'];

export function newField(): DraftRegistrationField {
    return {
        key: crypto.randomUUID(),
        label: '',
        field_type: 'text',
        options: [],
        placeholder: '',
        help_text: '',
        is_required: true,
    };
}

function draftFieldFromTemplateField(field: NonNullable<FormTemplate['fields']>[number]): DraftRegistrationField {
    return {
        key: crypto.randomUUID(),
        label: field.label,
        field_type: field.field_type,
        options: field.options ?? [],
        placeholder: field.placeholder ?? '',
        help_text: field.help_text ?? '',
        is_required: field.is_required,
    };
}

export function RegistrationFormBuilder({
    fields,
    onChange,
    templates = [],
    allowManualAdd = true,
}: {
    fields: DraftRegistrationField[];
    onChange: (fields: DraftRegistrationField[]) => void;
    templates?: FormTemplate[];
    allowManualAdd?: boolean;
}) {
    const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 4 } }));
    const [pendingTemplate, setPendingTemplate] = useState<FormTemplate | null>(null);

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;
        if (!over || active.id === over.id) return;

        const oldIndex = fields.findIndex((f) => f.key === active.id);
        const newIndex = fields.findIndex((f) => f.key === over.id);
        onChange(arrayMove(fields, oldIndex, newIndex));
    }

    function updateField(key: string, patch: Partial<DraftRegistrationField>) {
        onChange(fields.map((f) => (f.key === key ? { ...f, ...patch } : f)));
    }

    function removeField(key: string) {
        onChange(fields.filter((f) => f.key !== key));
    }

    function loadTemplate(templateId: string) {
        const template = templates.find((t) => t.id === Number(templateId));
        if (!template) return;

        if (fields.length > 0) {
            setPendingTemplate(template);
            return;
        }

        onChange((template.fields ?? []).map(draftFieldFromTemplateField));
    }

    function confirmLoadTemplate() {
        if (!pendingTemplate) return;
        onChange((pendingTemplate.fields ?? []).map(draftFieldFromTemplateField));
        setPendingTemplate(null);
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 className="text-lg font-semibold">Formulario de inscripción</h3>
                    <p className="text-sm text-muted-foreground">
                        Agrega los campos que los jugadores deberán responder al inscribirse en este torneo.
                    </p>
                </div>
                <div className="flex gap-2">
                    {templates.length > 0 && (
                        <Select value="" onValueChange={loadTemplate}>
                            <SelectTrigger className="w-56">
                                <SelectValue placeholder="Cargar plantilla..." />
                            </SelectTrigger>
                            <SelectContent>
                                {templates.map((template) => (
                                    <SelectItem key={template.id} value={String(template.id)}>
                                        {template.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                    {allowManualAdd && (
                        <Button type="button" onClick={() => onChange([...fields, newField()])}>
                            <Plus className="size-4" />
                            Agregar campo
                        </Button>
                    )}
                </div>
            </div>

            {!allowManualAdd && templates.length === 0 && (
                <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-10 text-center">
                    <LayoutTemplate className="size-8 text-muted-foreground" />
                    <p className="text-sm text-muted-foreground">Aún no tienes plantillas de formulario.</p>
                    <Button type="button" size="sm" asChild>
                        <Link href={route('templates.forms.create')}>Crear plantilla de formulario</Link>
                    </Button>
                </div>
            )}

            {fields.length === 0 && !(!allowManualAdd && templates.length === 0) && (
                <Card className="border-dashed">
                    <CardContent className="py-10 text-center text-sm text-muted-foreground">
                        Aún no hay campos personalizados. Los jugadores solo elegirán su(s) categoría(s) al inscribirse.
                    </CardContent>
                </Card>
            )}

            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                <SortableContext items={fields.map((f) => f.key)} strategy={verticalListSortingStrategy}>
                    <div className="space-y-3">
                        {fields.map((field) => (
                            <SortableFieldCard
                                key={field.key}
                                field={field}
                                onUpdate={(patch) => updateField(field.key, patch)}
                                onRemove={() => removeField(field.key)}
                            />
                        ))}
                    </div>
                </SortableContext>
            </DndContext>

            <ConfirmDialog
                open={pendingTemplate !== null}
                onOpenChange={(open) => !open && setPendingTemplate(null)}
                title="Reemplazar campos del formulario"
                description="Esto reemplazará los campos actuales del formulario por los de la plantilla seleccionada. ¿Continuar?"
                confirmLabel="Sí, reemplazar"
                cancelLabel="No, mantener los actuales"
                destructive
                onConfirm={confirmLoadTemplate}
            />
        </div>
    );
}

function SortableFieldCard({
    field,
    onUpdate,
    onRemove,
}: {
    field: DraftRegistrationField;
    onUpdate: (patch: Partial<DraftRegistrationField>) => void;
    onRemove: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: field.key });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    const isChoiceType = CHOICE_TYPES.includes(field.field_type);

    return (
        <Card ref={setNodeRef} style={style} className={cn(isDragging && 'opacity-60')}>
            <CardContent className="space-y-3 p-4">
                <div className="flex items-start gap-3">
                    <button
                        type="button"
                        className="mt-2 cursor-grab touch-none text-muted-foreground hover:text-foreground active:cursor-grabbing"
                        {...attributes}
                        {...listeners}
                    >
                        <GripVertical className="size-5" />
                    </button>

                    <div className="grid flex-1 gap-3 sm:grid-cols-[1fr_220px]">
                        <div className="grid gap-1.5">
                            <Label>Pregunta / etiqueta</Label>
                            <Input
                                value={field.label}
                                onChange={(e) => onUpdate({ label: e.target.value })}
                                placeholder="Ej: Número de camiseta"
                            />
                        </div>

                        <div className="grid gap-1.5">
                            <Label>Tipo de campo</Label>
                            <Select value={field.field_type} onValueChange={(value) => onUpdate({ field_type: value as RegistrationFieldType })}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(FIELD_TYPE_LABELS).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <Button type="button" variant="ghost" size="icon" onClick={onRemove} className="text-muted-foreground hover:text-destructive">
                        <Trash2 className="size-4" />
                    </Button>
                </div>

                {isChoiceType && (
                    <div className="grid gap-1.5 pl-8">
                        <Label>Opciones (una por línea)</Label>
                        <Textarea
                            rows={3}
                            value={field.options.join('\n')}
                            onChange={(e) => onUpdate({ options: e.target.value.split('\n') })}
                            placeholder={'Opción A\nOpción B\nOpción C'}
                        />
                    </div>
                )}

                <div className="grid gap-3 pl-8 sm:grid-cols-2">
                    <div className="grid gap-1.5">
                        <Label>Texto de ayuda (opcional)</Label>
                        <Input value={field.help_text} onChange={(e) => onUpdate({ help_text: e.target.value })} />
                    </div>
                    <label className="flex items-center gap-2 self-end pb-2 text-sm">
                        <Checkbox checked={field.is_required} onCheckedChange={(checked) => onUpdate({ is_required: checked === true })} />
                        Campo obligatorio
                    </label>
                </div>
            </CardContent>
        </Card>
    );
}
