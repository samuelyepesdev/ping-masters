import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link } from '@inertiajs/react';
import { type DivisionCategoryType, type DivisionFormat, type DivisionGenderCategory, type DivisionTemplate } from '@/types';
import { LayoutTemplate, Trash2 } from 'lucide-react';

export interface DraftDivision {
    key: string;
    id?: number;
    source_template_id?: number;
    name: string;
    category_type: DivisionCategoryType;
    gender_category: DivisionGenderCategory;
    min_age: string;
    max_age: string;
    format: DivisionFormat;
    best_of: '5' | '7';
    points_to_win: string;
    group_size: string;
    advance_per_group: string;
    swiss_rounds: string;
    max_participants: string;
    seed_by_rating: boolean;
    [key: string]: string | number | boolean | undefined;
}

export const CATEGORY_LABELS: Record<DivisionCategoryType, string> = {
    singles: 'Individual',
    doubles: 'Dobles',
    team: 'Equipos',
};

export const GENDER_LABELS: Record<DivisionGenderCategory, string> = {
    open: 'Abierto',
    male: 'Masculino',
    female: 'Femenino',
    mixed: 'Mixto',
};

export const FORMAT_LABELS: Record<DivisionFormat, string> = {
    single_elimination: 'Eliminación directa',
    double_elimination: 'Doble eliminación',
    round_robin: 'Todos contra todos',
    swiss: 'Sistema suizo',
    group_knockout: 'Grupos + eliminación',
};

export function newDivision(): DraftDivision {
    return {
        key: crypto.randomUUID(),
        name: '',
        category_type: 'singles',
        gender_category: 'open',
        min_age: '',
        max_age: '',
        format: 'single_elimination',
        best_of: '5',
        points_to_win: '11',
        group_size: '4',
        advance_per_group: '2',
        swiss_rounds: '5',
        max_participants: '',
        seed_by_rating: true,
    };
}

export function draftDivisionFromTemplate(template: DivisionTemplate): DraftDivision {
    return {
        key: crypto.randomUUID(),
        source_template_id: template.id,
        name: template.name,
        category_type: template.category_type,
        gender_category: template.gender_category,
        min_age: template.min_age?.toString() ?? '',
        max_age: template.max_age?.toString() ?? '',
        format: template.format,
        best_of: (template.best_of === 7 ? '7' : '5') as '5' | '7',
        points_to_win: template.points_to_win.toString(),
        group_size: template.group_size?.toString() ?? '4',
        advance_per_group: template.advance_per_group?.toString() ?? '2',
        swiss_rounds: template.swiss_rounds?.toString() ?? '5',
        max_participants: template.max_participants?.toString() ?? '',
        seed_by_rating: template.seed_by_rating,
    };
}

/**
 * The reusable field set for configuring one division (name, format, rules). Shared by the
 * tournament wizard's multi-division editor and the standalone division-template form.
 */
export function DivisionFieldsCard({
    division,
    onChange,
    onRemove,
}: {
    division: DraftDivision;
    onChange: (patch: Partial<DraftDivision>) => void;
    onRemove?: () => void;
}) {
    return (
        <Card>
            <CardContent className="space-y-4 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="grid flex-1 gap-1.5">
                        <Label>Nombre de la categoría</Label>
                        <Input value={division.name} onChange={(e) => onChange({ name: e.target.value })} placeholder="Ej: Individual Masculino Sub-18" />
                    </div>
                    {onRemove && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={onRemove}
                            className="mt-6 text-muted-foreground hover:text-destructive"
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="grid gap-1.5">
                        <Label>Modalidad</Label>
                        <Select value={division.category_type} onValueChange={(value) => onChange({ category_type: value as DivisionCategoryType })}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(CATEGORY_LABELS).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Género</Label>
                        <Select
                            value={division.gender_category}
                            onValueChange={(value) => onChange({ gender_category: value as DivisionGenderCategory })}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(GENDER_LABELS).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Formato de llave</Label>
                        <Select value={division.format} onValueChange={(value) => onChange({ format: value as DivisionFormat })}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(FORMAT_LABELS).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-4">
                    <div className="grid gap-1.5">
                        <Label>Mejor de</Label>
                        <Select value={division.best_of} onValueChange={(value) => onChange({ best_of: value as '5' | '7' })}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="5">5 sets</SelectItem>
                                <SelectItem value="7">7 sets</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Puntos por set</Label>
                        <Input type="number" min={1} value={division.points_to_win} onChange={(e) => onChange({ points_to_win: e.target.value })} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Edad mínima</Label>
                        <Input
                            type="number"
                            min={0}
                            value={division.min_age}
                            onChange={(e) => onChange({ min_age: e.target.value })}
                            placeholder="Sin límite"
                        />
                    </div>

                    <div className="grid gap-1.5">
                        <Label>Edad máxima</Label>
                        <Input
                            type="number"
                            min={0}
                            value={division.max_age}
                            onChange={(e) => onChange({ max_age: e.target.value })}
                            placeholder="Sin límite"
                        />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-4">
                    {division.format === 'group_knockout' && (
                        <>
                            <div className="grid gap-1.5">
                                <Label>Jugadores por grupo</Label>
                                <Input type="number" min={3} value={division.group_size} onChange={(e) => onChange({ group_size: e.target.value })} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Clasifican por grupo</Label>
                                <Input
                                    type="number"
                                    min={1}
                                    value={division.advance_per_group}
                                    onChange={(e) => onChange({ advance_per_group: e.target.value })}
                                />
                            </div>
                        </>
                    )}

                    {division.format === 'swiss' && (
                        <div className="grid gap-1.5">
                            <Label>Número de rondas</Label>
                            <Input type="number" min={1} value={division.swiss_rounds} onChange={(e) => onChange({ swiss_rounds: e.target.value })} />
                        </div>
                    )}

                    <div className="grid gap-1.5">
                        <Label>Cupo máximo</Label>
                        <Input
                            type="number"
                            min={2}
                            value={division.max_participants}
                            onChange={(e) => onChange({ max_participants: e.target.value })}
                            placeholder="Sin límite"
                        />
                    </div>

                    <label className="flex items-center gap-2 self-end pb-2 text-sm">
                        <Checkbox
                            checked={division.seed_by_rating}
                            onCheckedChange={(checked) => onChange({ seed_by_rating: checked === true })}
                        />
                        Sembrar por ranking
                    </label>
                </div>
            </CardContent>
        </Card>
    );
}

export function DivisionEditor({
    divisions,
    onChange,
    templates = [],
}: {
    divisions: DraftDivision[];
    onChange: (divisions: DraftDivision[]) => void;
    templates?: DivisionTemplate[];
}) {
    function update(key: string, patch: Partial<DraftDivision>) {
        onChange(divisions.map((d) => (d.key === key ? { ...d, ...patch } : d)));
    }

    function remove(key: string) {
        onChange(divisions.filter((d) => d.key !== key));
    }

    function toggleTemplate(template: DivisionTemplate, checked: boolean) {
        if (checked) {
            onChange([...divisions, draftDivisionFromTemplate(template)]);
        } else {
            onChange(divisions.filter((d) => d.source_template_id !== template.id));
        }
    }

    const selectedTemplateIds = new Set(divisions.map((d) => d.source_template_id).filter((id): id is number => id !== undefined));

    return (
        <div className="space-y-4">
            <div>
                <h3 className="text-lg font-semibold">Categorías / divisiones</h3>
                <p className="text-sm text-muted-foreground">
                    Selecciona las plantillas de categoría que quieres incluir en este torneo.
                </p>
            </div>

            {templates.length === 0 ? (
                <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-10 text-center">
                    <LayoutTemplate className="size-8 text-muted-foreground" />
                    <p className="text-sm text-muted-foreground">Aún no tienes plantillas de categoría.</p>
                    <Button type="button" size="sm" asChild>
                        <Link href={route('templates.divisions.create')}>Crear plantilla de categoría</Link>
                    </Button>
                </div>
            ) : (
                <div className="grid gap-2 sm:grid-cols-2">
                    {templates.map((template) => (
                        <label
                            key={template.id}
                            className="flex cursor-pointer items-start gap-2 rounded-lg border p-3 text-sm hover:bg-accent"
                        >
                            <Checkbox
                                checked={selectedTemplateIds.has(template.id)}
                                onCheckedChange={(checked) => toggleTemplate(template, checked === true)}
                            />
                            <div>
                                <p className="font-medium">{template.name}</p>
                                <p className="text-xs text-muted-foreground">
                                    {CATEGORY_LABELS[template.category_type]} · {FORMAT_LABELS[template.format]}
                                </p>
                            </div>
                        </label>
                    ))}
                </div>
            )}

            <div className="space-y-4">
                {divisions.map((division) => (
                    <DivisionFieldsCard key={division.key} division={division} onChange={(patch) => update(division.key, patch)} onRemove={() => remove(division.key)} />
                ))}
                {divisions.length === 0 && <p className="text-sm text-muted-foreground">Selecciona al menos una plantilla arriba.</p>}
            </div>
        </div>
    );
}
