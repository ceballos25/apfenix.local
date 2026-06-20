let agentes = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarAgentes();

    const inputSearch = document.getElementById('searchAgent');
    if (inputSearch) {
        inputSearch.addEventListener('input', debounce(() => {
            renderTabla();
        }, 400));
    }

    ['fecha_inicio', 'fecha_fin'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => renderTabla());
        }
    });
});

function cargarAgentes() {
    fetch('ajax/ventas-chat.ajax.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'obtener' }),
    })
        .then(res => res.json())
        .then(data => {
            agentes = data.success && Array.isArray(data.data)
                ? data.data
                : (data.data ? [data.data] : []);
            renderTabla();
        })
        .catch(err => {
            console.error('Error cargando ventas chatbot:', err);
            agentes = [];
            renderTabla();
        });
}

function renderTabla() {
    const tbody = document.getElementById('bodyTabla');
    const search = (document.getElementById('searchAgent')?.value || '').toLowerCase();
    const fechaInicio = document.getElementById('fecha_inicio')?.value || '';
    const fechaFin = document.getElementById('fecha_fin')?.value || '';

    const filtradas = agentes.filter(a => {
        const texto = `
            ${a.name_customer || ''}
            ${a.lastname_customer || ''}
            ${a.phone_customer || ''}
            ${a.email_customer || ''}
            ${a.code_agent || ''}
        `.toLowerCase();

        const coincideBusqueda = texto.includes(search);

        let coincideFecha = true;
        if (fechaInicio && fechaFin && a.date_created_agent) {
            const fecha = a.date_created_agent.split(' ')[0];
            coincideFecha = fecha >= fechaInicio && fecha <= fechaFin;
        }

        return coincideBusqueda && coincideFecha;
    });

    if (!filtradas.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Sin ventas pendientes</td></tr>';
        return;
    }

    tbody.innerHTML = filtradas.map(a => {
        const estadoBadge = {
            0: '<span class="badge bg-warning">Pendiente</span>',
            1: '<span class="badge bg-success">Validado</span>',
            2: '<span class="badge bg-danger">Rechazado</span>',
            3: '<span class="badge bg-dark">Error</span>',
        }[a.status_agent] || '';

        const urlComprobante = a.url_agent || '';
        const esImagen = /\.(jpg|jpeg|png|gif|webp)(\?|$)/i.test(urlComprobante);

        return `
        <tr class="align-middle border-bottom">
            <td class="py-3 ps-3">
                <div class="d-flex">
                    <div class="rounded-circle bg-light border d-flex justify-content-center align-items-center text-secondary fw-bold me-3"
                        style="width: 42px; height: 42px;">
                        ${a.name_customer ? a.name_customer.charAt(0).toUpperCase() : 'C'}
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-dark text-capitalize">
                            ${a.name_customer || ''} ${a.lastname_customer || ''}
                        </span>
                        <div class="text-muted small mt-1">
                            <span class="me-2">
                                <i class="text-secondary ti ti-phone"></i> ${a.phone_customer || '--'}
                            </span>
                            <span>
                                <i class="text-secondary ti ti-map-pin"></i> ${a.city_customer || 'N/A'}
                            </span>
                        </div>
                        <small class="text-muted">${a.email_customer || ''}</small>
                    </div>
                </div>
            </td>
            <td class="text-secondary">${a.code_agent}</td>
            <td>${a.quantity_agent}</td>
            <td>$${Number(a.amount_agent).toLocaleString('es-CO')}</td>
            <td>
                ${
                    urlComprobante
                        ? `<button class="btn btn-sm btn-outline-primary"
                            onclick="verComprobante('${escapeAttr(urlComprobante)}', ${esImagen})">
                            Ver
                           </button>`
                        : '—'
                }
            </td>
            <td>${estadoBadge}</td>
            <td>${a.date_created_agent || ''}</td>
            <td>
                ${
                    a.status_agent == 0
                        ? `
                    <button class="btn btn-success btn-sm" onclick="aprobar(${a.id_agent})">✔</button>
                    <button class="btn btn-danger btn-sm" onclick="rechazar(${a.id_agent})">✖</button>
                    `
                        : '—'
                }
            </td>
        </tr>`;
    }).join('');
}

function escapeAttr(str) {
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function limpiarFiltrosAgent() {
    document.getElementById('searchAgent').value = '';
    document.getElementById('fecha_inicio').value = '';
    document.getElementById('fecha_fin').value = '';
    renderTabla();
}

function verComprobante(url, esImagen) {
    const cuerpo = document.getElementById('cuerpoComprobante');
    if (esImagen) {
        cuerpo.innerHTML = `<img src="${url}" class="img-fluid rounded" alt="Comprobante">`;
    } else {
        cuerpo.innerHTML = `<a href="${url}" target="_blank" rel="noopener" class="btn btn-primary">Abrir enlace</a>`;
    }
    new bootstrap.Modal(document.getElementById('modalComprobante')).show();
}

function aprobar(id) {
    const a = agentes.find(x => x.id_agent == id);
    if (!a) return;

    Swal.fire({
        title: '¿Validar venta del Agente IA?',
        text: `Código: ${a.code_agent}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, validar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#198754',
    }).then(result => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Procesando...',
            text: 'Creando venta y notificando al Agente IA',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        const params = new URLSearchParams({ action: 'aprobar' });
        Object.entries(a).forEach(([key, val]) => {
            if (val !== null && val !== undefined) {
                params.append(key, val);
            }
        });

        fetch('ajax/ventas-chat.ajax.php', {
            method: 'POST',
            body: params,
        })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.id_sale) {
                    let texto = 'Venta validada correctamente.';
                    if (res.webhook_ok === false) {
                        const detalle = res.webhook?.message || 'Error desconocido';
                        texto += '\n\nAgente IA: ' + detalle;
                        texto += '\n\nRevise logs/meteor-webhook.log en el servidor.';
                    }

                    Swal.fire({
                        icon: res.webhook_ok === false ? 'warning' : 'success',
                        title: 'Venta validada',
                        text: texto,
                        timer: res.webhook_ok === false ? undefined : 1200,
                        showConfirmButton: res.webhook_ok === false,
                    });

                    if (res.webhook_ok !== false) {
                        setTimeout(() => verRecibo(res.id_sale), 1200);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'No se pudo validar',
                    });
                }
                cargarAgentes();
            })
            .catch(() => Swal.fire('Error', 'Fallo en la solicitud', 'error'));
    });
}

function rechazar(id) {
    const a = agentes.find(x => x.id_agent == id);
    if (!a) return;

    Swal.fire({
        title: '¿Rechazar venta del Agente IA?',
        text: `Código: ${a.code_agent}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, rechazar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Procesando...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        const params = new URLSearchParams({ action: 'rechazar' });
        Object.entries(a).forEach(([key, val]) => {
            if (val !== null && val !== undefined) {
                params.append(key, val);
            }
        });

        fetch('ajax/ventas-chat.ajax.php', {
            method: 'POST',
            body: params,
        })
            .then(res => res.json())
            .then(res => {
                if (res.success !== false) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rechazado',
                        text: 'La venta fue rechazada',
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'No se pudo rechazar',
                    });
                }
                cargarAgentes();
            })
            .catch(() => Swal.fire('Error', 'Fallo en la solicitud', 'error'));
    });
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

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
                Swal.fire('Error', 'No se pudo cargar el recibo', 'error');
            }
        });
}
