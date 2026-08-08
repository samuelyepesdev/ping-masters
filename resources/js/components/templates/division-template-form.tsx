import { DivisionFieldsCard, draftDivisionFromTemplate, newDivision, type DraftDivision } from '@/components/tournaments/division-editor';
import { Button } from '@/components/ui/button';
import { type DivisionTemplate } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export function DivisionTemplateForm({ template, redirectTo }: { template?: DivisionTemplate; redirectTo?: string | null }) {
    const isEdit = !!template;

    const { data, setData, post, put, transform, processing, errors } = useForm<DraftDivision & { [key: string]: FormDataConvertible }>(
        template ? draftDivisionFromTemplate(template) : newDivision(),
    );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        transform((current) => ({
            name: current.name,
            category_type: current.category_type,
            gender_category: current.gender_category,
            min_age: current.min_age === '' ? null : Number(current.min_age),
            max_age: current.max_age === '' ? null : Number(current.max_age),
            format: current.format,
            best_of: Number(current.best_of),
            points_to_win: Number(current.points_to_win || 11),
            group_size: current.format === 'group_knockout' ? Number(current.group_size || 4) : null,
            advance_per_group: current.format === 'group_knockout' ? Number(current.advance_per_group || 2) : null,
            swiss_rounds: current.format === 'swiss' ? Number(current.swiss_rounds || 5) : null,
            max_participants: current.max_participants === '' ? null : Number(current.max_participants),
            seed_by_rating: current.seed_by_rating,
            ...(redirectTo ? { redirect_to: redirectTo } : {}),
        }));

        if (isEdit && template) {
            put(route('templates.divisions.update', template.id));
        } else {
            post(route('templates.divisions.store'));
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <DivisionFieldsCard division={data} onChange={(patch) => setData((prev) => ({ ...prev, ...patch }))} />
            {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}

            <Button type="submit" disabled={processing}>
                {isEdit ? 'Guardar cambios' : 'Crear plantilla'}
            </Button>
        </form>
    );
}
