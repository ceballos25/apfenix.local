/**
 * dashboard-vendedor.js — Dashboard personalizado del vendedor
 */
document.addEventListener('DOMContentLoaded', () => {
    cargarDashboardVendedor();
});

async function cargarDashboardVendedor() {
    try {
        const fd = new FormData();
        fd.append('action', 'obtener_dashboard');

        const res = await fetch('ajax/vendedor.ajax.php', { method: 'POST', body: fd });
        const json = await res.json();

        if (!json.success) {
            console.error(json.message);
            return;
        }

        const d = json.data;
        const pct = d.porcentaje ?? 0;

        document.getElementById('nombreVendedor').textContent = d.nombre || 'Vendedor';
        document.getElementById('fechaHoy').textContent = formatearFecha(d.fecha);
        document.getElementById('badgePorcentaje').textContent = pct + '%';
        document.getElementById('textoMeta').textContent =
            `Meta: ${d.goal_value} ${d.goal_label} — Llevas: ${d.progreso_actual}`;

        const barra = document.getElementById('barraProgreso');
        barra.style.width = pct + '%';
        barra.setAttribute('aria-valuenow', pct);
        document.getElementById('textoBarra').textContent = pct + '%';

        if (pct >= 100) {
            barra.classList.remove('bg-success');
            barra.classList.add('bg-primary');
        }

        document.getElementById('kpiVentasHoy').textContent = d.total_ventas ?? 0;
        document.getElementById('kpiNumerosHoy').textContent = d.total_numeros ?? 0;
        document.getElementById('kpiDineroHoy').textContent = fmtMoney(d.total_dinero ?? 0);

        renderVentasHoy(d.ultimas_ventas || []);
    } catch (e) {
        console.error('Error dashboard vendedor', e);
    }
}

function renderVentasHoy(ventas) {
    const tbody = document.getElementById('bodyVentasHoy');

    if (!ventas.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Aún no tienes ventas hoy</td></tr>';
        return;
    }

    tbody.innerHTML = ventas.map(v => {
        const hora = (v.date_created_sale || '').split(' ')[1]?.substring(0, 5) || '-';
        return `<tr>
            <td class="ps-3"><span class="font-monospace small">${v.code_sale}</span></td>
            <td class="text-capitalize">${v.name_customer} ${v.lastname_customer}</td>
            <td class="small text-muted">${v.title_raffle || '-'}</td>
            <td>${v.quantity_sale}</td>
            <td class="fw-bold text-success">${fmtMoney(v.total_sale)}</td>
            <td class="text-muted small">${hora}</td>
        </tr>`;
    }).join('');
}

function fmtMoney(n) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(n);
}

function formatearFecha(fecha) {
    if (!fecha) return '';
    const [y, m, d] = fecha.split('-');
    const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${parseInt(d)} ${meses[parseInt(m) - 1]} ${y}`;
}
