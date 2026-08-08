import confetti from 'canvas-confetti';

export function celebrateVictory() {
    const duration = 1500;
    const end = Date.now() + duration;

    const colors = ['#f59e0b', '#2563eb', '#16a34a'];

    (function frame() {
        confetti({
            particleCount: 4,
            angle: 60,
            spread: 60,
            origin: { x: 0, y: 0.7 },
            colors,
        });
        confetti({
            particleCount: 4,
            angle: 120,
            spread: 60,
            origin: { x: 1, y: 0.7 },
            colors,
        });

        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    })();

    confetti({
        particleCount: 100,
        spread: 90,
        origin: { y: 0.6 },
        colors,
    });
}
