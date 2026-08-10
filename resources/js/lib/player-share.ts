import { type Player } from '@/types';

export function buildPlayerShareText(player: Player, levelName: string | null): string {
    const winRate = player.matches_played > 0 ? Math.round((player.matches_won / player.matches_played) * 100) : null;
    const link = route('public.players.show', player.id);

    const lines = [
        `🏓 ${player.user?.name} en Ping Masters`,
        `Nivel ${player.level}${levelName ? ` · ${levelName}` : ''} · Rating ${player.rating_current}`,
        `${player.matches_played} partidos jugados${winRate !== null ? ` · ${winRate}% victorias` : ''}`,
        `\nVer perfil: ${link}`,
    ];

    return lines.filter(Boolean).join('\n');
}
