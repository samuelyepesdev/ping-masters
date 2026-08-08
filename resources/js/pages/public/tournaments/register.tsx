import InputError from '@/components/input-error';
import { DynamicFieldRenderer } from '@/components/tournaments/dynamic-field-renderer';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SmartLayout from '@/layouts/smart-layout';
import { type BreadcrumbItem, type SharedData, type Tournament } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface DivisionSelection {
    division_id: number;
    selected: boolean;
    partner_name: string;
    partner_club: string;
    [key: string]: string | number | boolean;
}

interface RegisterFormData {
    name: string;
    email: string;
    phone: string;
    divisionSelections: DivisionSelection[];
    responses: Record<string, string | boolean | string[]>;
    [key: string]: FormDataConvertible;
}

export default function PublicTournamentRegister({ tournament }: { tournament: Tournament }) {
    const { auth } = usePage<SharedData>().props;
    const isGuest = !auth.user;

    const { data, setData, post, transform, processing, errors } = useForm<RegisterFormData>({
        name: '',
        email: '',
        phone: '',
        divisionSelections: (tournament.divisions ?? []).map((d) => ({
            division_id: d.id,
            selected: false,
            partner_name: '',
            partner_club: '',
        })),
        responses: {},
    });

    function toggleDivision(divisionId: number, selected: boolean) {
        setData(
            'divisionSelections',
            data.divisionSelections.map((d) => (d.division_id === divisionId ? { ...d, selected } : d)),
        );
    }

    function updateDivision(divisionId: number, patch: Partial<Pick<DivisionSelection, 'partner_name' | 'partner_club'>>) {
        setData(
            'divisionSelections',
            data.divisionSelections.map((d) => (d.division_id === divisionId ? { ...d, ...patch } : d)),
        );
    }

    function updateResponse(fieldId: number, value: string | boolean | string[] | undefined) {
        setData('responses', { ...data.responses, [fieldId]: value ?? '' });
    }

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        transform(() => ({
            ...(isGuest ? { name: data.name, email: data.email, phone: data.phone || null } : {}),
            divisions: data.divisionSelections
                .filter((d) => d.selected)
                .map((d) => ({
                    division_id: d.division_id,
                    partner_name: d.partner_name || null,
                    partner_club: d.partner_club || null,
                })),
            responses: data.responses,
        }));

        post(route('public.tournaments.store', tournament.slug));
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Explorar torneos', href: '/torneos' },
        { title: tournament.name, href: route('public.tournaments.show', tournament.slug) },
        { title: 'Inscripción', href: '#' },
    ];

    return (
        <SmartLayout breadcrumbs={breadcrumbs}>
            <Head title={`Inscripción — ${tournament.name}`} />

            <div className="mx-auto max-w-2xl space-y-8">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Inscripción a {tournament.name}</h1>
                    <p className="text-muted-foreground">Selecciona la(s) categoría(s) en las que quieres participar.</p>
                </div>

                <form onSubmit={submit} className="space-y-8">
                    {isGuest && (
                        <div className="space-y-3">
                            <h2 className="text-lg font-semibold">Tus datos</h2>
                            <p className="text-sm text-muted-foreground">
                                No tienes una cuenta todavía — la crearemos con estos datos y te enviaremos por correo un enlace para definir tu
                                contraseña.
                            </p>
                            <Card>
                                <CardContent className="grid gap-3 p-4 sm:grid-cols-2">
                                    <div className="grid gap-1.5">
                                        <Label>Nombre completo</Label>
                                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>Correo electrónico</Label>
                                        <Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                                        <InputError message={errors.email} />
                                    </div>
                                    <div className="grid gap-1.5 sm:col-span-2">
                                        <Label>Teléfono (opcional)</Label>
                                        <Input value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    <div className="space-y-3">
                        <h2 className="text-lg font-semibold">Categorías</h2>
                        {data.divisionSelections.map((selection) => {
                            const division = tournament.divisions?.find((d) => d.id === selection.division_id);
                            if (!division) return null;

                            const isFull = division.is_full === true;

                            return (
                                <Card key={division.id} className={isFull ? 'opacity-60' : undefined}>
                                    <CardContent className="space-y-3 p-4">
                                        <label className="flex items-center gap-3">
                                            <Checkbox
                                                checked={selection.selected}
                                                disabled={isFull}
                                                onCheckedChange={(checked) => toggleDivision(division.id, checked === true)}
                                            />
                                            <span className="flex-1 font-medium">{division.name}</span>
                                            {isFull && <Badge variant="destructive">Inscripciones agotadas</Badge>}
                                        </label>

                                        {selection.selected && division.category_type === 'doubles' && (
                                            <div className="grid gap-3 pl-8 sm:grid-cols-2">
                                                <div className="grid gap-1.5">
                                                    <Label>Nombre de tu pareja</Label>
                                                    <Input
                                                        value={selection.partner_name}
                                                        onChange={(e) => updateDivision(division.id, { partner_name: e.target.value })}
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label>Club de tu pareja (opcional)</Label>
                                                    <Input
                                                        value={selection.partner_club}
                                                        onChange={(e) => updateDivision(division.id, { partner_club: e.target.value })}
                                                    />
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            );
                        })}
                        <InputError message={errors.divisions as string | undefined} />
                    </div>

                    {tournament.registration_fields && tournament.registration_fields.length > 0 && (
                        <div className="space-y-4">
                            <h2 className="text-lg font-semibold">Información adicional</h2>
                            {tournament.registration_fields.map((field) => (
                                <DynamicFieldRenderer
                                    key={field.id}
                                    field={field}
                                    value={data.responses[field.id]}
                                    onChange={(value) => updateResponse(field.id, value)}
                                    error={(errors as Record<string, string>)[`responses.${field.id}`]}
                                />
                            ))}
                        </div>
                    )}

                    <Button type="submit" size="lg" disabled={processing}>
                        Confirmar inscripción
                    </Button>
                </form>
            </div>
        </SmartLayout>
    );
}
