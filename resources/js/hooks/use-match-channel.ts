import { type MatchScoreState } from '@/types';
import { useEffect } from 'react';

export function useMatchChannel(matchId: number, onUpdate: (state: MatchScoreState) => void) {
    useEffect(() => {
        let cancelled = false;

        import('@/echo').then(({ default: echo }) => {
            if (cancelled) return;

            echo.channel(`match.${matchId}`).listen('.score.updated', onUpdate);
        });

        return () => {
            cancelled = true;
            import('@/echo').then(({ default: echo }) => echo.leaveChannel(`match.${matchId}`));
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [matchId]);
}
