import { type MatchScoreState } from '@/types';
import { useEffect } from 'react';

export function useMatchChannel(matchId: number, onUpdate: (state: MatchScoreState) => void, channel: string = 'match') {
    useEffect(() => {
        let cancelled = false;
        const channelName = `${channel}.${matchId}`;

        import('@/echo').then(({ default: echo }) => {
            if (cancelled) return;

            echo.channel(channelName).listen('.score.updated', onUpdate);
        });

        return () => {
            cancelled = true;
            import('@/echo').then(({ default: echo }) => echo.leaveChannel(channelName));
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [matchId, channel]);
}
