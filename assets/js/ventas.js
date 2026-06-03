/**
 * ventas.js - Gestión Blindada y Completa
 */
let ventasCache = [], paginaActual = 1;
const registrosPorPagina = 10;
const isAdmin = window.APP_USER?.isAdmin ?? true;

document.addEventListener('DOMContentLoaded', function() {

    // 1. Cargar Rifas
    cargarRifasSelect();
    
    // 2. Cargar Ventas Iniciales
    cargarVentas();

    // 3. Cargar admins (solo administrador)
    if (isAdmin) {
        cargarAdminsSelect();
    }

    // Método de pago
    document.getElementById('filterMetodoPago')?.addEventListener('change', () => {
        paginaActual = 1;
        cargarVentas();
    });

    // Admin
    const selAdmin = document.getElementById('filterAdmin');
    if (selAdmin) {
        selAdmin.addEventListener('change', () => {
            paginaActual = 1;
            cargarVentas();
        });
    }

    // 🔎 Buscador
    const inputSearch = document.getElementById('searchVentas');
    if (inputSearch) {
        inputSearch.addEventListener('input', debounce(() => {
            paginaActual = 1;
            cargarVentas();
        }, 600));
    }

    // 📅 Periodo
    const selPeriodo = document.getElementById('filterPeriodo');
    if (selPeriodo) {
        selPeriodo.addEventListener('change', function() {
            if (this.value !== "") {
                document.getElementById('fecha_inicio').value = '';
                document.getElementById('fecha_fin').value = '';
            }
            paginaActual = 1;
            cargarVentas();
        });
    }

    // 📅 Fechas manuales
    ['fecha_inicio', 'fecha_fin'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function() {
                if (this.value !== "") {
                    document.getElementById('filterPeriodo').value = '';
                }

                if (
                    document.getElementById('fecha_inicio').value &&
                    document.getElementById('fecha_fin').value
                ) {
                    paginaActual = 1;
                    cargarVentas();
                }
            });
        }
    });

    // 🎯 Rifa
    const selRifa = document.getElementById('filterRifa');
    if (selRifa) {
        selRifa.addEventListener('change', function() {
            paginaActual = 1;
            cargarVentas();
        });
    }

});

// ===============================
// FUNCIONES
// ===============================

async function parseJsonResponse(response) {
    const text = await response.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        console.error('Respuesta no JSON:', text.slice(0, 400));
        if (text.includes('vendor/autoload.php')) {
            throw new Error('Falta la carpeta vendor/ en el servidor. Suba deploy/vendor.zip y extráigalo en public_html/vendor/');
        }
        throw new Error('El servidor respondió con error. Revise logs de PHP en cPanel.');
    }
}

// Obtener Rifas
async function cargarRifasSelect() {
    try {
        const fd = new FormData();
        fd.append('action', 'obtener_rifas');

        const res = await fetch('ajax/ventas.ajax.php', { method: 'POST', body: fd });
        const data = await parseJsonResponse(res);

        if (data.success && data.data) {
            const select = document.getElementById('filterRifa');
            select.innerHTML = '<option value="">Todas las rifas</option>';

            const lista = Array.isArray(data.data) ? data.data : [data.data];

            lista.forEach(r => {
                select.innerHTML += `<option value="${r.id_raffle}">${r.title_raffle}</option>`;
            });
        }
    } catch (e) {
        console.error("Error rifas", e);
    }
}

// Obtener Admins
async function cargarAdminsSelect() {
    try {
        const fd = new FormData();
        fd.append('action', 'obtener_admins');

        const res = await fetch('ajax/ventas.ajax.php', { method: 'POST', body: fd });
        const data = await parseJsonResponse(res);

        if (data.success && data.data) {
            const select = document.getElementById('filterAdmin');
            select.innerHTML = '<option value="">Todos</option>';

            data.data.forEach(a => {
                select.innerHTML += `<option value="${a.id_admin}">${a.email_admin}</option>`;
            });
        }
    } catch (e) {
        console.error("Error cargando admins", e);
    }
}

// Cargar Ventas
async function cargarVentas() {
    if (typeof showPreloader === 'function') showPreloader();

    try {
        const fd = new FormData();
        fd.append('action', 'obtener');
        fd.append('search', document.getElementById('searchVentas')?.value || '');
        fd.append('periodo', document.getElementById('filterPeriodo')?.value || '');
        fd.append('id_raffle', document.getElementById('filterRifa')?.value || '');
        fd.append('fecha_inicio', document.getElementById('fecha_inicio')?.value || '');
        fd.append('fecha_fin', document.getElementById('fecha_fin')?.value || '');
        fd.append('payment_method', document.getElementById('filterMetodoPago')?.value || '');
        fd.append('id_admin', document.getElementById('filterAdmin')?.value || '');

        const res = await fetch('ajax/ventas.ajax.php', { method: 'POST', body: fd });
        const data = await parseJsonResponse(res);

        ventasCache = data.success
            ? (Array.isArray(data.data) ? data.data : (data.data ? [data.data] : []))
            : [];

        renderizarTodo();

    } catch (e) {
        console.error(e);
    } finally {
        if (typeof hidePreloader === 'function') hidePreloader();
    }
}

// Render general
function renderizarTodo() {
    if (typeof PaginationHelper !== 'undefined') {
        const segmento = PaginationHelper.getSegment(ventasCache, paginaActual, registrosPorPagina);
        renderTabla(segmento);

        PaginationHelper.render({
            totalItems: ventasCache.length,
            currentPage: paginaActual,
            limit: registrosPorPagina,
            containerId: 'contenedorPaginacion',
            infoId: 'infoPaginacion',
            callbackName: 'cambiarPagina'
        });
    } else {
        renderTabla(ventasCache);
    }
}

function cambiarPagina(p) {
    paginaActual = p;
    renderizarTodo();
}

function renderTabla(ventas) {
    const tbody = document.getElementById('bodyTabla');
    if (!tbody) return;

    if (!ventas || ventas.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">No se encontraron registros</td></tr>`;
        return;
    }

    tbody.innerHTML = ventas.map(v => {
        const f = new Date(v.date_created_sale);
        const fecha = f.toLocaleDateString('es-CO');
        const hora = f.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: true });

        const inicial = v.name_customer ? v.name_customer.charAt(0).toUpperCase() : 'C';

        const badgeClass = v.payment_method_sale === 'Efectivo' 
            ? 'bg-success-subtle text-success border-success-subtle' 
            : 'bg-primary-subtle text-primary border-primary-subtle';

        return `
        <tr class="align-middle border-bottom hover-shadow">
            <td class="d-none">${v.id_sale}</td>

            <!-- CLIENTE -->
            <td class="py-3 ps-3">
                <div class="d-flex">
                    <div class="rounded-circle bg-light border d-flex justify-content-center align-items-center text-secondary fw-bold me-3 flex-shrink-0" 
                         style="width: 42px; height: 42px; font-size: 1.1rem;">
                        ${inicial}
                    </div>

                    <div class="d-flex flex-column" style="line-height: 1.3;">
                        <span class="fw-bold text-dark text-capitalize">
                            ${v.name_customer} ${v.lastname_customer}
                        </span>

                        <div class="text-muted small mt-1">
                            <span class="me-2">
                                <i class="ti ti-phone text-secondary"></i> ${v.phone_customer || '--'}
                            </span>
                            <span>
                                <i class="ti ti-map-pin text-secondary"></i> ${v.city_customer || 'N/A'}
                            </span>
                        </div>

                        <small class="text-muted fst-italic" style="font-size: 0.75rem;">
                            <i class="ti ti-mail text-secondary"></i> ${v.email_customer || ''}
                        </small>

                        <small class="text-muted fst-italic" style="font-size: 0.75rem;">
                            <i class="ti ti-user text-secondary"></i> 
                            ${v.email_admin || ('Admin #' + (v.id_admin_sale ?? '0'))}
                        </small>
                    </div>
                </div>
            </td>

            <!-- CODIGO -->
            <td class="py-3">
                <span class="font-monospace bg-light text-primary px-2 py-1 rounded border" style="font-size: 0.85rem;">
                    ${v.code_sale}
                </span>
            </td>

            <!-- CANTIDAD -->
            <td class="py-3">
                <span class="fw-medium text-dark d-block">
                    ${v.quantity_sale} Núms
                </span>
                <small class="text-muted text-truncate d-block">
                    ${v.title_raffle}
                </small>
            </td>

            <!-- TOTAL -->
            <td class="py-3">
                <span class="fw-bold text-dark">
                    $${Number(v.total_sale).toLocaleString('es-CO')}
                </span>
            </td>

            <!-- METODO -->
            <td class="py-3">
                <span class="badge ${badgeClass} border px-3 py-2 rounded-pill">
                    ${v.payment_method_sale}
                </span>
            </td>

            <!-- FECHA -->
            <td class="py-3">
                <div class="d-flex flex-column text-muted">
                    <span class="text-dark fw-medium">${fecha}</span>
                    <span style="font-size: 0.85rem;">${hora}</span>
                </div>
            </td>

            <!-- ACCIONES -->
            <td class="py-3 text-end pe-3">
                <button class="btn btn-icon btn-sm btn-outline-primary border-0 rounded-circle shadow-sm" 
                        onclick="verRecibo(${v.id_sale})" 
                        title="Ver Detalle"
                        style="width: 32px; height: 32px;">
                    <i class="ti ti-eye fs-7"></i>
                </button>
                ${isAdmin ? `
                <button class="btn btn-icon btn-sm btn-outline-danger border-0 rounded-circle shadow-sm ms-1"
                        onclick="anularVenta(${v.id_sale})"
                        title="Anular venta"
                        style="width: 32px; height: 32px;">
                    <i class="ti ti-trash fs-7"></i>
                </button>` : ''}
            </td>
        </tr>`;
    }).join('');
}

// Recibo
function verRecibo(id) {
    const fd = new FormData();
    fd.append('action', 'detalle_venta');
    fd.append('id_sale', id);

    fetch('ajax/ventas.ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('cuerpoRecibo').innerHTML = res.html_recibo;
                new bootstrap.Modal(document.getElementById('modalRecibo')).show();
            } else {
                Swal.fire('Error', res.message || 'No se pudo cargar el comprobante', 'error');
            }
        });
}

// Anular
function anularVenta(id) {

    Swal.fire({
        title: '¿Anular venta?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, anular'
    }).then(result => {

        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append('action', 'anular');
        fd.append('id_sale', id);

        fetch('ajax/ventas.ajax.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire('OK', 'Venta anulada', 'success');
                    cargarVentas();
                }
            });
    });
}

// Util
function debounce(f, w) {
    let t;
    return (...a) => {
        clearTimeout(t);
        t = setTimeout(() => f(...a), w);
    };
}