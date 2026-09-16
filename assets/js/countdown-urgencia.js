/**
 * Cuenta regresiva de urgencia (preventa o sorteo).
 */
(function () {
    const cfg = window.DINAMICA || {};
    const iso = cfg.expira;
    if (!iso) return;

    const target = new Date(iso).getTime();
    if (!target || Number.isNaN(target)) return;

    const pad = (n) => String(n).padStart(2, '0');

    function parts(ms) {
        const total = Math.max(0, Math.floor(ms / 1000));
        return {
            d: String(Math.floor(total / 86400)).padStart(2, '0'),
            h: pad(Math.floor((total % 86400) / 3600)),
            m: pad(Math.floor((total % 3600) / 60)),
            s: pad(total % 60),
            done: total <= 0,
        };
    }

    function setText(selector, value) {
        document.querySelectorAll(selector).forEach((el) => {
            el.textContent = value;
        });
    }

    function tick() {
        const p = parts(target - Date.now());
        setText('.js-cd-d', p.d);
        setText('.js-cd-h', p.h);
        setText('.js-cd-m', p.m);
        setText('.js-cd-s', p.s);

        const compact = p.d === '00'
            ? `${p.h}:${p.m}:${p.s}`
            : `${parseInt(p.d, 10)}d ${p.h}:${p.m}:${p.s}`;
        document.querySelectorAll('.promo2x1-countdown').forEach((el) => {
            el.textContent = compact;
        });

        const box = document.getElementById('urgenciaTimer');
        if (box) {
            box.classList.toggle('is-critical', parseInt(p.d, 10) < 2);
        }

        if (p.done) {
            clearInterval(timer);
        }
    }

    tick();
    const timer = setInterval(tick, 1000);
})();
