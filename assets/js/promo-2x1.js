/**
 * Preventa: ~30% extra (no es 2×1).
 * 3→4, 5→7, 8→10, 10→13, 20→26, 25→33, 50→65
 */
(function (global) {
    function cfg() {
        const d = global.DINAMICA || {};
        const p = global.PROMO_2X1 || {};
        return {
            activo: !!(d.preventaActiva || p.activo),
            minimo: d.minimo || p.minimo || 3,
            bonusRate: typeof d.bonusRate === 'number' ? d.bonusRate : 0.30,
            expira: d.expira || p.expira || null,
        };
    }

    function precioUnitario(cantidad) {
        const d = global.DINAMICA || {};
        const promo = d.precioPromo || 8000;
        const full = d.precio || 9000;
        const desde = d.desdePromo || 25;
        const qty = parseInt(cantidad, 10) || 0;
        if (d.preventaActiva) return full;
        if (qty >= desde) return promo;
        return full;
    }

    function aplica(cantidad) {
        const c = cfg();
        const pagados = parseInt(cantidad, 10) || 0;
        return !!(c.activo && pagados >= c.minimo);
    }

    function bonus(cantidad) {
        const pagados = parseInt(cantidad, 10) || 0;
        if (!aplica(pagados)) return 0;
        return Math.max(1, Math.round(pagados * cfg().bonusRate));
    }

    function entregados(cantidad) {
        const pagados = parseInt(cantidad, 10) || 0;
        return pagados + bonus(pagados);
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

    function apagarPreventaCliente() {
        if (global.DINAMICA) {
            global.DINAMICA.preventaActiva = false;
            global.DINAMICA.preventaFase = 'ended';
        }
        if (global.PROMO_2X1) {
            global.PROMO_2X1.activo = false;
        }
    }

    function initCountdown(selectors, onExpire) {
        const c = cfg();
        if (!c.expira) return null;

        const empezóActiva = !!(global.DINAMICA && global.DINAMICA.preventaActiva);
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
                if (empezóActiva) {
                    apagarPreventaCliente();
                    if (sessionStorage.getItem('preventaEndedReload') !== '1') {
                        sessionStorage.setItem('preventaEndedReload', '1');
                        location.reload();
                        return;
                    }
                    if (typeof onExpire === 'function') onExpire();
                }
            }
        };

        tick();
        const timer = setInterval(tick, 1000);
        return timer;
    }

    global.Promo2x1 = {
        aplica,
        bonus,
        entregados,
        precioUnitario,
        textoCantidad,
        initCountdown,
        formatearCuentaRegresiva,
    };
})(window);
