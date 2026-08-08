import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

export function formatDate(dateString?: string | null, pattern = 'd MMM yyyy'): string {
    if (!dateString) return '';

    return format(parseISO(dateString), pattern, { locale: es });
}

export function formatDateRange(start?: string | null, end?: string | null): string {
    if (!start && !end) return 'Fechas por definir';
    if (!end || start === end) return formatDate(start);

    return `${formatDate(start)} — ${formatDate(end)}`;
}
