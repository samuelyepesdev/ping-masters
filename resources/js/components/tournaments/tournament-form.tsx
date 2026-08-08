import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { type Tournament, type TournamentStatus } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { DivisionEditor, newDivision, type DraftDivision } from '@/components/tournaments/division-editor';
import { RegistrationFormBuilder, type DraftRegistrationField } from '@/components/tournaments/registration-form-builder';

const STATUS_LABELS: Record<TournamentStatus, string> = {
    draft: 'Borrador (no visible)',
    registration_open: 'Inscripciones abiertas',
    registration_closed: 'Inscripciones cerradas',
    in_progress: 'En curso',
    completed: 'Finalizado',
    cancelled: 'Cancelado',
};

interface TournamentFormData {
    name: string;
    description: string;
    venue: string;
    city: string;
    status: TournamentStatus;
    start_date: string;
    end_date: string;
    registration_opens_at: string;
    registration_closes_at: string;
    max_participants: string;
    divisions: DraftDivision[];
    registration_fields: DraftRegistrationField[];
    [key: string]: FormDataConvertible;
}

function draftDivisionFromModel(d: NonNullable<Tournament['divisions']>[number]): DraftDivision {
    return {
        key: crypto.randomUUID(),
        id: d.id,
        name: d.name,
        category_type: d.category_type,
        gender_category: d.gender_category,
        min_age: d.min_age?.toString() ?? '',
        max_age: d.max_age?.toString() ?? '',
        format: d.format,
        best_of: (d.best_of === 7 ? '7' : '5') as '5' | '7',
        points_to_win: d.points_to_win.toString(),
        group_size: d.group_size?.toString() ?? '4',
        advance_per_group: d.advance_per_group?.toString() ?? '2',
        swiss_rounds: d.swiss_rounds?.toString() ?? '5',
        max_participants: d.max_participants?.toString() ?? '',
        seed_by_rating: d.seed_by_rating,
    };
}

function draftFieldFromModel(f: NonNullable<Tournament['registration_fields']>[number]): DraftRegistrationField {
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

export function TournamentForm({ tournament }: { tournament?: Tournament }) {
    const isEdit = !!tournament;
    const [step, setStep] = useState(1);

    const { data, setData, post, put, transform, processing, errors } = useForm<TournamentFormData>({
        name: tournament?.name ?? '',
        description: tournament?.description ?? '',
        venue: tournament?.venue ?? '',
        city: tournament?.city ?? '',
        status: tournament?.status ?? 'draft',
        start_date: tournament?.start_date?.substring(0, 10) ?? '',
        end_date: tournament?.end_date?.substring(0, 10) ?? '',
        registration_opens_at: tournament?.registration_opens_at?.substring(0, 10) ?? '',
        registration_closes_at: tournament?.registration_closes_at?.substring(0, 10) ?? '',
        max_participants: tournament?.max_participants?.toString() ?? '',
        divisions: tournament?.divisions?.length ? tournament.divisions.map(draftDivisionFromModel) : [newDivision()],
        registration_fields: tournament?.registration_fields?.map(draftFieldFromModel) ?? [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const payload = {
            ...data,
            max_participants: data.max_participants || null,
            registration_opens_at: data.registration_opens_at || null,
            registration_closes_at: data.registration_closes_at || null,
            divisions: data.divisions.map((d) => ({
                id: d.id,
                name: d.name,
                category_type: d.category_type,
                gender_category: d.gender_category,
                min_age: d.min_age === '' ? null : Number(d.min_age),
                max_age: d.max_age === '' ? null : Number(d.max_age),
                format: d.format,
                best_of: Number(d.best_of),
                points_to_win: Number(d.points_to_win || 11),
                group_size: d.format === 'group_knockout' ? Number(d.group_size || 4) : null,
                advance_per_group: d.format === 'group_knockout' ? Number(d.advance_per_group || 2) : null,
                swiss_rounds: d.format === 'swiss' ? Number(d.swiss_rounds || 5) : null,
                max_participants: d.max_participants === '' ? null : Number(d.max_participants),
                seed_by_rating: d.seed_by_rating,
            })),
            registration_fields: data.registration_fields
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
        };

        transform(() => payload);

        if (isEdit && tournament) {
            put(route('tournaments.update', tournament.id));
        } else {
            post(route('tournaments.store'));
        }
    };

    const steps = [
        { id: 1, label: 'Datos generales' },
        { id: 2, label: 'Categorías' },
        { id: 3, label: 'Formulario de inscripción' },
    ];

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="flex items-center gap-2">
                {steps.map((s, i) => (
                    <div key={s.id} className="flex flex-1 items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setStep(s.id)}
                            className={cn(
                                'flex size-8 shrink-0 items-center justify-center rounded-full border text-sm font-medium transition-colors',
                                step === s.id
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : step > s.id
                                      ? 'border-primary/50 bg-primary/10 text-primary'
                                      : 'border-input text-muted-foreground',
                            )}
                        >
                            {step > s.id ? <Check className="size-4" /> : s.id}
                        </button>
                        <span className={cn('hidden text-sm sm:inline', step === s.id ? 'font-medium' : 'text-muted-foreground')}>{s.label}</span>
                        {i < steps.length - 1 && <div className="h-px flex-1 bg-border" />}
                    </div>
                ))}
            </div>

            {step === 1 && (
                <div className="space-y-4">
                    <div className="grid gap-1.5">
                        <Label>Nombre del torneo</Label>
                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Copa Ping Masters 2026" />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Descripción</Label>
                        <Textarea rows={4} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label>Sede</Label>
                            <Input value={data.venue} onChange={(e) => setData('venue', e.target.value)} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label>Ciudad</Label>
                            <Input value={data.city} onChange={(e) => setData('city', e.target.value)} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label>Fecha de inicio</Label>
                            <Input type="date" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} />
                            <InputError message={errors.start_date} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label>Fecha de fin</Label>
                            <Input type="date" value={data.end_date} onChange={(e) => setData('end_date', e.target.value)} />
                            <InputError message={errors.end_date} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label>Apertura de inscripciones</Label>
                            <Input
                                type="date"
                                value={data.registration_opens_at}
                                onChange={(e) => setData('registration_opens_at', e.target.value)}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label>Cierre de inscripciones</Label>
                            <Input
                                type="date"
                                value={data.registration_closes_at}
                                onChange={(e) => setData('registration_closes_at', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label>Cupo máximo de participantes</Label>
                            <Input
                                type="number"
                                min={1}
                                value={data.max_participants}
                                onChange={(e) => setData('max_participants', e.target.value)}
                                placeholder="Sin límite"
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label>Estado</Label>
                            <Select value={data.status} onValueChange={(value) => setData('status', value as TournamentStatus)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(STATUS_LABELS).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>
            )}

            {step === 2 && (
                <div>
                    <DivisionEditor divisions={data.divisions} onChange={(divisions) => setData('divisions', divisions)} />
                    <InputError message={errors.divisions as string | undefined} className="mt-2" />
                </div>
            )}

            {step === 3 && (
                <RegistrationFormBuilder fields={data.registration_fields} onChange={(fields) => setData('registration_fields', fields)} />
            )}

            <div className="flex items-center justify-between border-t pt-4">
                <Button type="button" variant="outline" onClick={() => setStep((s) => Math.max(1, s - 1))} disabled={step === 1}>
                    Anterior
                </Button>

                {step < 3 ? (
                    <Button type="button" onClick={() => setStep((s) => Math.min(3, s + 1))}>
                        Siguiente
                    </Button>
                ) : (
                    <Button type="submit" disabled={processing}>
                        {isEdit ? 'Guardar cambios' : 'Crear torneo'}
                    </Button>
                )}
            </div>
        </form>
    );
}
