import { type MatchScoreState } from '@/types';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';

const TERMINAL_STATUSES = ['completed', 'walkover', 'cancelled'];
const POLL_INTERVAL_MS = 4000;

/**
 * Subscribes to live score updates for a match, via Reverb when it's deployed (echo is
 * non-null). Otherwise falls back to polling — reloading just the `match` prop every few
 * seconds — so the other player/spectators still see updates without a manual page reload.
 */
export function useMatchChannel(matchId: number, status: string, onUpdate: (state: MatchScoreState) => void, channel: string = 'match') {
    useEffect(() => {
        let cancelled = false;
        let pollTimer: ReturnType<typeof setInterval> | null = null;
        const channelName = `${channel}.${matchId}`;

        import('@/echo').then(({ default: echo }) => {
            if (cancelled) return;

            if (echo) {
                echo.channel(channelName).listen('.score.updated', onUpdate);
                return;
            }

            if (TERMINAL_STATUSES.includes(status)) return;

            pollTimer = setInterval(() => {
                router.reload({ only: ['match'], showProgress: false });
            }, POLL_INTERVAL_MS);
        });

        return () => {
            cancelled = true;
            if (pollTimer) clearInterval(pollTimer);
            import('@/echo').then(({ default: echo }) => echo?.leaveChannel(channelName));
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [matchId, channel, status]);
}
