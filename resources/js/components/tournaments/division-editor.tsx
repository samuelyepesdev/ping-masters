import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type DivisionCategoryType, type DivisionFormat, type DivisionGenderCategory } from '@/types';
import { Plus, Trash2 } from 'lucide-react';

export interface DraftDivision {
    key: string;
    id?: number;
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

const CATEGORY_LABELS: Record<DivisionCategoryType, string> = {
    singles: 'Individual',
    doubles: 'Dobles',
    team: 'Equipos',
};

const GENDER_LABELS: Record<DivisionGenderCategory, string> = {
    open: 'Abierto',
    male: 'Masculino',
    female: 'Femenino',
    mixed: 'Mixto',
};

const FORMAT_LABELS: Record<DivisionFormat, string> = {
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

export function DivisionEditor({ divisions, onChange }: { divisions: DraftDivision[]; onChange: (divisions: DraftDivision[]) => void }) {
    function update(key: string, patch: Partial<DraftDivision>) {
        onChange(divisions.map((d) => (d.key === key ? { ...d, ...patch } : d)));
    }

    function remove(key: string) {
        onChange(divisions.filter((d) => d.key !== key));
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-lg font-semibold">Categorías / divisiones</h3>
                    <p className="text-sm text-muted-foreground">Cada categoría tiene su propio formato de juego y reglas.</p>
                </div>
                <Button type="button" onClick={() => onChange([...divisions, newDivision()])}>
                    <Plus className="size-4" />
                    Agregar categoría
                </Button>
            </div>

            <div className="space-y-4">
                {divisions.map((division) => (
                    <Card key={division.key}>
                        <CardContent className="space-y-4 p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div className="grid flex-1 gap-1.5">
                                    <Label>Nombre de la categoría</Label>
                                    <Input
                                        value={division.name}
                                        onChange={(e) => update(division.key, { name: e.target.value })}
                                        placeholder="Ej: Individual Masculino Sub-18"
                                    />
                                </div>
                                {divisions.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => remove(division.key)}
                                        className="mt-6 text-muted-foreground hover:text-destructive"
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-1.5">
                                    <Label>Modalidad</Label>
                                    <Select
                                        value={division.category_type}
                                        onValueChange={(value) => update(division.key, { category_type: value as DivisionCategoryType })}
                                    >
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
                                        onValueChange={(value) => update(division.key, { gender_category: value as DivisionGenderCategory })}
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
                                    <Select value={division.format} onValueChange={(value) => update(division.key, { format: value as DivisionFormat })}>
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
                                    <Select value={division.best_of} onValueChange={(value) => update(division.key, { best_of: value as '5' | '7' })}>
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
                                    <Input
                                        type="number"
                                        min={1}
                                        value={division.points_to_win}
                                        onChange={(e) => update(division.key, { points_to_win: e.target.value })}
                                    />
                                </div>

                                <div className="grid gap-1.5">
                                    <Label>Edad mínima</Label>
                                    <Input
                                        type="number"
                                        min={0}
                                        value={division.min_age}
                                        onChange={(e) => update(division.key, { min_age: e.target.value })}
                                        placeholder="Sin límite"
                                    />
                                </div>

                                <div className="grid gap-1.5">
                                    <Label>Edad máxima</Label>
                                    <Input
                                        type="number"
                                        min={0}
                                        value={division.max_age}
                                        onChange={(e) => update(division.key, { max_age: e.target.value })}
                                        placeholder="Sin límite"
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-4">
                                {division.format === 'group_knockout' && (
                                    <>
                                        <div className="grid gap-1.5">
                                            <Label>Jugadores por grupo</Label>
                                            <Input
                                                type="number"
                                                min={3}
                                                value={division.group_size}
                                                onChange={(e) => update(division.key, { group_size: e.target.value })}
                                            />
                                        </div>
                                        <div className="grid gap-1.5">
                                            <Label>Clasifican por grupo</Label>
                                            <Input
                                                type="number"
                                                min={1}
                                                value={division.advance_per_group}
                                                onChange={(e) => update(division.key, { advance_per_group: e.target.value })}
                                            />
                                        </div>
                                    </>
                                )}

                                {division.format === 'swiss' && (
                                    <div className="grid gap-1.5">
                                        <Label>Número de rondas</Label>
                                        <Input
                                            type="number"
                                            min={1}
                                            value={division.swiss_rounds}
                                            onChange={(e) => update(division.key, { swiss_rounds: e.target.value })}
                                        />
                                    </div>
                                )}

                                <div className="grid gap-1.5">
                                    <Label>Cupo máximo</Label>
                                    <Input
                                        type="number"
                                        min={2}
                                        value={division.max_participants}
                                        onChange={(e) => update(division.key, { max_participants: e.target.value })}
                                        placeholder="Sin límite"
                                    />
                                </div>

                                <label className="flex items-center gap-2 self-end pb-2 text-sm">
                                    <Checkbox
                                        checked={division.seed_by_rating}
                                        onCheckedChange={(checked) => update(division.key, { seed_by_rating: checked === true })}
                                    />
                                    Sembrar por ranking
                                </label>
                            </div>
                        </CardContent>
                    </Card>
                ))}
                {divisions.length === 0 && <p className="text-sm text-muted-foreground">Agrega al menos una categoría.</p>}
            </div>
        </div>
    );
}
