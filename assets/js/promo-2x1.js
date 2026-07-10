/**
 * Promoción 2×1 — helpers compartidos (landing + venta manual).
 */
(function (global) {
    const cfg = () => global.PROMO_2X1 || { activo: false, minimo: 50 };

    function aplica(cantidad) {
        const c = cfg();
        return !!(c.activo && cantidad >= (c.minimo || 50));
    }

    function entregados(cantidad) {
        const pagados = parseInt(cantidad, 10) || 0;
        return aplica(pagados) ? pagados * 2 : pagados;
    }

    function formatearCuentaRegresiva(ms) {
        const totalSeg = Math.max(0, Math.floor(ms / 1000));
        const dias = Math.floor(totalSeg / 86400);
        const horas = Math.floor((totalSeg % 86400) / 3600);
        const minutos = Math.floor((totalSeg % 3600) / 60);
        const segundos = totalSeg % 60;
        const pad = (n) => String(n).padStart(2, '0');

        if (dias > 0) {
            return `${dias}d ${pad(horas)}:${pad(minutos)}:${pad(segundos)}`;
        }

        return `${pad(horas)}:${pad(minutos)}:${pad(segundos)}`;
    }

    function textoCantidad(cantidad) {
        const pagados = parseInt(cantidad, 10) || 0;
        if (!pagados) return '0';
        if (!aplica(pagados)) return String(pagados);
        return `${pagados} → ${entregados(pagados)}`;
    }

    function initCountdown(selectors, onExpire) {
        const c = cfg();
        if (!c.activo || !c.expira) return null;

        const expira = new Date(c.expira).getTime();
        const nodes = typeof selectors === 'string'
            ? document.querySelectorAll(selectors)
            : selectors;

        const tick = () => {
            const restante = expira - Date.now();
            const texto = restante > 0 ? formatearCuentaRegresiva(restante) : '00:00:00';

            nodes.forEach((el) => { el.textContent = texto; });

            if (restante <= 0) {
                clearInterval(timer);
                c.activo = false;
                document.querySelectorAll('.promo-2x1-wrap').forEach((el) => el.classList.add('d-none'));
                if (typeof onExpire === 'function') onExpire();
            }
        };

        tick();
        const timer = setInterval(tick, 1000);
        return timer;
    }

    global.Promo2x1 = {
        aplica,
        entregados,
        textoCantidad,
        initCountdown,
        formatearCuentaRegresiva,
    };
})(window);
