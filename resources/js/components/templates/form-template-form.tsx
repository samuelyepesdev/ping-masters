import { newField, RegistrationFormBuilder, type DraftRegistrationField } from '@/components/tournaments/registration-form-builder';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type FormTemplate } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface FormTemplateFormData {
    name: string;
    description: string;
    fields: DraftRegistrationField[];
    [key: string]: FormDataConvertible;
}

function draftFieldFromModel(f: NonNullable<FormTemplate['fields']>[number]): DraftRegistrationField {
    return {
        key: crypto.randomUUID(),
        id: f.id,
        label: f.label,
        field_type: f.field_type,
        options: f.options ?? [],
        placeholder: f.placeholder ?? '',
        help_text: f.help_text ?? '',
        is_required: f.is_required,
    };
}

export function FormTemplateForm({ template, redirectTo }: { template?: FormTemplate; redirectTo?: string | null }) {
    const isEdit = !!template;

    const { data, setData, post, put, transform, processing, errors } = useForm<FormTemplateFormData>({
        name: template?.name ?? '',
        description: template?.description ?? '',
        fields: template?.fields?.map(draftFieldFromModel) ?? [newField()],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        transform((current) => ({
            name: current.name,
            description: current.description || null,
            fields: current.fields
                .filter((f) => f.label.trim() !== '')
                .map((f) => ({
                    id: f.id,
                    label: f.label,
                    field_type: f.field_type,
                    options: ['select', 'radio', 'checkbox_group'].includes(f.field_type)
                        ? f.options.map((o) => o.trim()).filter(Boolean)
                        : null,
                    placeholder: f.placeholder || null,
                    help_text: f.help_text || null,
                    is_required: f.is_required,
                })),
            ...(redirectTo ? { redirect_to: redirectTo } : {}),
        }));

        if (isEdit && template) {
            put(route('templates.forms.update', template.id));
        } else {
            post(route('templates.forms.store'));
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-1.5">
                <Label>Nombre de la plantilla</Label>
                <Input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Ej: Formulario estándar" />
                {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
            </div>

            <div className="grid gap-1.5">
                <Label>Descripción (opcional)</Label>
                <Textarea rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} />
            </div>

            <RegistrationFormBuilder fields={data.fields} onChange={(fields) => setData('fields', fields)} />

            <Button type="submit" disabled={processing}>
                {isEdit ? 'Guardar cambios' : 'Crear plantilla'}
            </Button>
        </form>
    );
}
