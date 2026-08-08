import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type TournamentRegistrationField } from '@/types';

type FieldValue = string | boolean | string[] | undefined;

export function DynamicFieldRenderer({
    field,
    value,
    onChange,
    error,
}: {
    field: TournamentRegistrationField;
    value: FieldValue;
    onChange: (value: FieldValue) => void;
    error?: string;
}) {
    const options = field.options ?? [];

    return (
        <div className="grid gap-1.5">
            {field.field_type !== 'checkbox' && (
                <Label>
                    {field.label}
                    {field.is_required && <span className="text-destructive"> *</span>}
                </Label>
            )}

            {field.field_type === 'text' && (
                <Input value={(value as string) ?? ''} placeholder={field.placeholder ?? ''} onChange={(e) => onChange(e.target.value)} />
            )}

            {field.field_type === 'textarea' && (
                <Textarea value={(value as string) ?? ''} placeholder={field.placeholder ?? ''} onChange={(e) => onChange(e.target.value)} />
            )}

            {field.field_type === 'number' && (
                <Input
                    type="number"
                    value={(value as string) ?? ''}
                    placeholder={field.placeholder ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            )}

            {field.field_type === 'email' && (
                <Input
                    type="email"
                    value={(value as string) ?? ''}
                    placeholder={field.placeholder ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            )}

            {field.field_type === 'phone' && (
                <Input
                    type="tel"
                    value={(value as string) ?? ''}
                    placeholder={field.placeholder ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                />
            )}

            {field.field_type === 'date' && <Input type="date" value={(value as string) ?? ''} onChange={(e) => onChange(e.target.value)} />}

            {field.field_type === 'select' && (
                <Select value={(value as string) ?? ''} onValueChange={onChange}>
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona una opción" />
                    </SelectTrigger>
                    <SelectContent>
                        {options.map((option) => (
                            <SelectItem key={option} value={option}>
                                {option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {field.field_type === 'radio' && (
                <RadioGroup value={(value as string) ?? ''} onValueChange={onChange}>
                    {options.map((option) => (
                        <label key={option} className="flex items-center gap-2 text-sm">
                            <RadioGroupItem value={option} />
                            {option}
                        </label>
                    ))}
                </RadioGroup>
            )}

            {field.field_type === 'checkbox_group' && (
                <div className="space-y-2">
                    {options.map((option) => {
                        const selected = Array.isArray(value) ? value : [];
                        return (
                            <label key={option} className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={selected.includes(option)}
                                    onCheckedChange={(checked) =>
                                        onChange(checked ? [...selected, option] : selected.filter((v) => v !== option))
                                    }
                                />
                                {option}
                            </label>
                        );
                    })}
                </div>
            )}

            {field.field_type === 'checkbox' && (
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox checked={value === true} onCheckedChange={(checked) => onChange(checked === true)} />
                    {field.label}
                    {field.is_required && <span className="text-destructive"> *</span>}
                </label>
            )}

            {field.help_text && <p className="text-xs text-muted-foreground">{field.help_text}</p>}
            {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
        </div>
    );
}
