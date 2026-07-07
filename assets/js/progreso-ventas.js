/**
 * Barra de progreso de ventas — carga al entrar con animación de 0% al valor real.
 */
(function () {
    const DURACION_ANIM_MS = 1400;

    const labels = () => document.querySelectorAll('.progreso-venta-pct');
    const bars = () => document.querySelectorAll('.progreso-venta-bar');

    let animFrame = null;

    function formatearPct(valor) {
        const n = Math.min(100, Math.max(0, Number(valor) || 0));
        return n.toFixed(2).replace(/\.?0+$/, '');
    }

    function pintarUI(pct) {
        const texto = formatearPct(pct) + '%';
        labels().forEach((el) => { el.textContent = texto; });
        bars().forEach((bar) => { bar.style.width = pct + '%'; });
    }

    function animarDesdeCero(destino) {
        const meta = Math.min(100, Math.max(0, Number(destino) || 0));

        if (animFrame) {
            cancelAnimationFrame(animFrame);
        }

        pintarUI(0);
        const t0 = performance.now();

        function frame(ahora) {
            const t = Math.min(1, (ahora - t0) / DURACION_ANIM_MS);
            const ease = 1 - Math.pow(1 - t, 3);
            const actual = meta * ease;

            pintarUI(actual);

            if (t < 1) {
                animFrame = requestAnimationFrame(frame);
            } else {
                pintarUI(meta);
                animFrame = null;
            }
        }

        animFrame = requestAnimationFrame(frame);
    }

    async function cargarAvance() {
        try {
            const fd = new FormData();
            fd.append('action', 'avance_rifa');

            const res = await fetch('front/ajax/web.ajax.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
            });

            const data = await res.json();

            if (data.success && data.data) {
                animarDesdeCero(data.data.porcentaje);
            }
        } catch (e) {
            console.error('Error cargando avance de ventas', e);
        }
    }

    document.addEventListener('DOMContentLoaded', cargarAvance);
})();
