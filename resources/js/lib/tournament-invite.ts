import { formatDateRange } from '@/lib/format-date';
import { type Tournament } from '@/types';

export function buildTournamentInviteText(tournament: Tournament): string {
    const location = [tournament.venue, tournament.city].filter(Boolean).join(', ');
    const dateRange = formatDateRange(tournament.start_date, tournament.end_date);
    const categories = (tournament.divisions ?? []).map((division) => `• ${division.name}`).join('\n');
    const link = route('public.tournaments.show', tournament.slug);

    const lines = [
        `🏓 ¡Te invito a "${tournament.name}"!`,
        [dateRange, location].filter(Boolean).join(' · '),
        categories ? `\nCategorías:\n${categories}` : null,
        `\nInscríbete aquí: ${link}`,
    ];

    return lines.filter(Boolean).join('\n');
}
