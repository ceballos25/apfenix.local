/**
 * vendedores.js — CRUD de vendedores (admin)
 */
let vendedoresCache = [];
let modalVendedor = null;

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('modalVendedor');
    if (el) modalVendedor = bootstrap.Modal.getOrCreateInstance(el);
    cargarVendedores();
});

async function cargarVendedores() {
    try {
        const fd = new FormData();
        fd.append('action', 'obtener');
        const res = await fetch('ajax/vendedores.ajax.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.success) {
            document.getElementById('bodyTablaVendedores').innerHTML =
                `<tr><td colspan="5" class="text-center text-danger py-4">${data.message || 'Error'}</td></tr>`;
            return;
        }

        vendedoresCache = data.data || [];
        renderTabla();
    } catch (e) {
        console.error(e);
    }
}

function renderTabla() {
    const tbody = document.getElementById('bodyTablaVendedores');
    if (!vendedoresCache.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay vendedores registrados</td></tr>';
        return;
    }

    tbody.innerHTML = vendedoresCache.map(v => {
        const activo = parseInt(v.status_admin) === 1;
        const metaLabel = v.goal_type_admin === 'numeros'
            ? `${v.goal_value_admin} números/día`
            : `${v.goal_value_admin} ventas/día`;

        return `<tr>
            <td class="ps-3 fw-bold">${esc(v.name_admin || '-')}</td>
            <td>${esc(v.email_admin)}</td>
            <td><span class="badge bg-info-subtle text-info">${metaLabel}</span></td>
            <td>${activo
                ? '<span class="badge bg-success">Activo</span>'
                : '<span class="badge bg-secondary">Inactivo</span>'}</td>
            <td>
                <button class="btn btn-sm btn-light border" onclick="editarVendedor(${v.id_admin})" title="Editar">
                    <i class="ti ti-edit"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

function abrirModalVendedor() {
    document.getElementById('formVendedor').reset();
    document.getElementById('vendedorId').value = '';
    document.getElementById('modalVendedorTitle').textContent = 'Nuevo Vendedor';
    document.getElementById('password_admin').required = true;
    document.getElementById('passHint').textContent = '(obligatoria)';
    modalVendedor.show();
}

async function editarVendedor(id) {
    try {
        const fd = new FormData();
        fd.append('action', 'obtener_uno');
        fd.append('id_admin', id);
        const res = await fetch('ajax/vendedores.ajax.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (!data.success) {
            Swal.fire('Error', data.message, 'error');
            return;
        }

        const v = data.data;
        document.getElementById('vendedorId').value = v.id_admin;
        document.getElementById('name_admin').value = v.name_admin || '';
        document.getElementById('email_admin').value = v.email_admin || '';
        document.getElementById('password_admin').value = '';
        document.getElementById('password_admin').required = false;
        document.getElementById('passHint').textContent = '(dejar vacío para no cambiar)';
        document.getElementById('status_admin').value = v.status_admin ?? 1;
        document.getElementById('goal_type_admin').value = v.goal_type_admin || 'ventas';
        document.getElementById('goal_value_admin').value = v.goal_value_admin || 0;
        document.getElementById('modalVendedorTitle').textContent = 'Editar Vendedor';
        modalVendedor.show();
    } catch (e) {
        Swal.fire('Error', 'No se pudo cargar el vendedor', 'error');
    }
}

async function guardarVendedor() {
    const form = document.getElementById('formVendedor');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const id = document.getElementById('vendedorId').value;
    const fd = new FormData(form);
    fd.append('action', id ? 'actualizar' : 'crear');

    try {
        const res = await fetch('ajax/vendedores.ajax.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
            modalVendedor.hide();
            cargarVendedores();
        } else {
            Swal.fire('Error', data.message || 'No se pudo guardar', 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Error de conexión', 'error');
    }
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}
