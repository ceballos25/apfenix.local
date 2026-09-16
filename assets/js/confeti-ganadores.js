/**
 * Confeti de ganadores: cañones laterales + lluvia dorada.
 */
(function () {
    const section = document.getElementById('ultimosBendecidos');
    const canvas = document.getElementById('confetiGanadores');
    if (!section || !canvas || typeof confetti !== 'function') return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const fire = confetti.create(canvas, { resize: true, useWorker: false });
    const colors = ['#FFD700', '#FFC107', '#FFFFFF', '#d500f9', '#2D0434', '#FB7185'];
    let timer = null;

    function burst() {
        fire({
            particleCount: 70,
            spread: 62,
            startVelocity: 48,
            gravity: 0.9,
            ticks: 220,
            origin: { x: 0.08, y: 0.22 },
            angle: 60,
            colors,
        });
        fire({
            particleCount: 70,
            spread: 62,
            startVelocity: 48,
            gravity: 0.9,
            ticks: 220,
            origin: { x: 0.92, y: 0.22 },
            angle: 120,
            colors,
        });
        fire({
            particleCount: 45,
            spread: 100,
            startVelocity: 28,
            gravity: 0.75,
            ticks: 260,
            origin: { x: 0.5, y: 0 },
            colors: ['#FFD700', '#FFC107', '#FFF3BF'],
        });
    }

    function start() {
        if (timer) return;
        burst();
        timer = setInterval(burst, 4200);
    }

    function stop() {
        if (!timer) return;
        clearInterval(timer);
        timer = null;
    }

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting && entry.intersectionRatio > 0.28) {
                start();
            } else {
                stop();
            }
        });
    }, { threshold: [0.28, 0.5] });

    io.observe(section);
})();
