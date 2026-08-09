import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo?: Echo<'reverb'>;
    }
}

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

// Reverb is optional — deployments that skip it (broadcasting disabled) must not crash
// just because a page tried to subscribe to a live channel. Callers get `null` back and
// simply don't receive live updates instead of throwing.
let echo: Echo<'reverb'> | null = null;

if (reverbKey) {
    window.Pusher = Pusher;

    echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    window.Echo = echo;
}

export default echo;
