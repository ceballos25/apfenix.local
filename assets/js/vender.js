/**
 * vender.js - Gestión de Ventas Caballos Revelo
 * Refactorización Pro: Búsqueda Inteligente, Limpieza de Datos y Soporte Multi-dispositivo.
 * NUEVA LÓGICA: selección por cantidad de números.
 */

// --- 1. ESTADO GLOBAL ---
const estado = {
    rifa: null,
    cantidadSeleccionada: 0,
    paginaActual: 1,
    itemsPorPagina: 40,
    cupon: {
        aplicado: false,
        codigo: ''
    },
    config: {
        rutas: {
            clientes: 'ajax/clientes.ajax.php',
            rifas: 'ajax/rifas.ajax.php',
            ventas: 'ajax/ventas.ajax.php'
        }
    }
};

const cuponConfig = window.CUPON_AP_FENIX || { activo: false };
let cuponCountdownTimer = null;

// --- 2. INICIALIZACIÓN ---
document.addEventListener('DOMContentLoaded', () => {
    inyectarEstilosMarca();
    initComponentes();
    initCuponPromo();
    initPromo2x1();
    cargarRifasActivas();
    asignarEventos();
});

function inyectarEstilosMarca() {

    const css = `
    <style>
        .btn-ticket-revelo {
            background-color:#ffffff !important;
            border:2px solid #1a1a1a !important;
            color:#1a1a1a !important;
            border-radius:10px;
            font-weight:700 !important;
            width:55px;
            height:55px;
        }

        .btn-resumen-tickets{
            background:#fff !important;
            border:1px solid #1a1a1a !important;
            border-radius:50px !important;
        }

        .pulse-gold{
            animation:pulse-animation .5s ease-in-out;
        }

        @keyframes pulse-animation{
            0%{transform:scale(1);}
            50%{transform:scale(1.15);box-shadow:0 0 10px #d4af37;}
            100%{transform:scale(1);}
        }
    </style>`;

    document.head.insertAdjacentHTML('beforeend', css);
}

// --- 3. EVENTOS ---
function asignarEventos() {

    $('#selectRifa').on('change', cambiarRifa);

    $('#cantidadNumeros').on('input', function () {

        estado.cantidadSeleccionada = parseInt(this.value) || 0;

        actualizarCarritoUI();
    });

    $('#departamento').on('change', function () {
        cargarCiudadesVenta(this.value);
    });

    $('#btnLimpiarCliente').on('click', resetClienteForm);

    $('#celularCliente').on('input paste', function () {

        let val = $(this).val().replace(/\D/g, '');

        if (val.startsWith('57') && val.length > 10) val = val.substring(2);

        $(this).val(val);

        if (val.length === 10) buscarClientePorCelular(val);
        actualizarCarritoUI();
    });

    $('#nombreCliente, #apellidoCliente').on('input', actualizarCarritoUI);

    $('input[name="metodoPago"], input[name="metodoPagoMobile"]').on('change', function () {
        sincronizarMetodoPago(this.value);
    });

    const btnCobrar = document.getElementById('btnSiCobrar');
    if (btnCobrar) {
        btnCobrar.addEventListener('click', ejecutarVenta);
    }
}

function sincronizarMetodoPago(valor) {
    $('input[name="metodoPago"][value="' + valor + '"]').prop('checked', true);
    $('input[name="metodoPagoMobile"][value="' + valor + '"]').prop('checked', true);
}

function obtenerMetodoPago() {
    return $('input[name="metodoPago"]:checked').val()
        || $('input[name="metodoPagoMobile"]:checked').val()
        || '';
}

// --- CLIENTES ---
async function buscarClientePorCelular(numero) {

    const fd = new FormData();

    fd.append('action', 'obtener');
    fd.append('search', numero);
    fd.append('status', 1);

    try {

        const res = await fetch(estado.config.rutas.clientes, {
            method: 'POST',
            body: fd
        });

        const json = await res.json();

        if (json.success && json.data && json.data.length > 0) {

            const clienteEncontrado = json.data[0];

            if (clienteEncontrado.phone_customer === numero) {

                llenarFormulario(clienteEncontrado);
            }
        }

    } catch (e) {

        console.error("Error buscando cliente:", e);
    }
}

// --- COMPONENTES ---
function initComponentes() {

    $('#buscadorCliente').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar cliente...',
        allowClear: true,
        minimumInputLength: 3,
        ajax: {
            url: estado.config.rutas.clientes,
            type: 'POST',
            dataType: 'json',
            delay: 300,
            data: params => {

                let term = params.term ? params.term.trim() : "";

                if (/^[0-9\s+]+$/.test(term)) {

                    let digits = term.replace(/\D/g, '');

                    if (digits.startsWith('57') && digits.length > 10)
                        term = digits.substring(2);
                    else
                        term = digits;
                }

                return {
                    action: 'obtener',
                    search: term,
                    status: 1
                };
            },
            processResults: res => ({
                results: (res.success && res.data)
                    ? res.data.map(c => ({
                        id: c.id_customer,
                        text: `${c.name_customer} ${c.lastname_customer} (${c.phone_customer})`,
                        cliente: c
                    }))
                    : []
            })
        }
    })
        .on('select2:select', e => llenarFormulario(e.params.data.cliente))
        .on('select2:unselecting', resetClienteForm);

    $('.select2-ubicacion').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    if (typeof datosColombia !== 'undefined') {

        const $depto = $('#departamento');

        $depto.empty().append('<option value="">Seleccione...</option>');

        Object.keys(datosColombia)
            .sort()
            .forEach(d => $depto.append(new Option(d, d)));
    }
}

// --- RIFAS ---
async function cargarRifasActivas() {

    const fd = new FormData();
    fd.append('action', 'obtener_activas');

    try {

        const res = await fetch(estado.config.rutas.rifas, {
            method: 'POST',
            body: fd
        });

        const json = await res.json();

        if (json.success && json.data.length > 0) {

            const select = document.getElementById('selectRifa');

            // limpiar opciones por seguridad
            select.innerHTML = '';

            json.data.forEach((r, index) => {

                const opt = new Option(r.title_raffle, r.id_raffle);
                opt.dataset.precio = r.price_raffle;

                select.add(opt);

                // seleccionar la primera automáticamente
                if (index === 0) {

                    estado.rifa = {
                        id: r.id_raffle,
                        precio: parseFloat(r.price_raffle || 0)
                    };

                }

            });

            actualizarCarritoUI();

        }

    } catch (e) {

        console.error("Error rifas:", e);

    }

}

function cambiarRifa() {

    const idRifa = $('#selectRifa').val();

    if (!idRifa) return;

    const opt = document.getElementById('selectRifa').selectedOptions[0];

    estado.rifa = {
        id: idRifa,
        precio: parseFloat(opt.dataset.precio || 0)
    };

    actualizarCarritoUI();
}

function obtenerPrecioUnitario(cantidad) {
    if (window.Promo2x1 && typeof window.Promo2x1.precioUnitario === 'function') {
        return window.Promo2x1.precioUnitario(cantidad);
    }

    const d = window.DINAMICA || {};
    const promo = d.precioPromo || 8000;
    const full = d.precio || estado.rifa?.precio || 9000;
    const desde = d.desdePromo || 25;
    if (d.preventaActiva) {
        return full;
    }
    if (cantidad >= desde) {
        return promo;
    }

    return full;
}

function formatearMoneda(n) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0
    }).format(n);
}

function calcularMontos(cantidad) {
    const subtotal = cantidad * obtenerPrecioUnitario(cantidad);
    let descuento = 0;

    if (cuponConfig.activo && estado.cupon.aplicado) {
        descuento = Math.round(subtotal * (cuponConfig.descuento / 100));
    }

    return {
        subtotal,
        descuento,
        total: Math.max(0, subtotal - descuento)
    };
}

function initCuponPromo() {
    if (!cuponConfig.activo || !cuponConfig.expira) {
        return;
    }

    autoAplicarCupon();

    const expira = new Date(cuponConfig.expira).getTime();

    const tick = () => {
        const restante = expira - Date.now();

        if (restante <= 0) {
            clearInterval(cuponCountdownTimer);
            cuponConfig.activo = false;
            estado.cupon.aplicado = false;
            $('#cuponCountdownVender').closest('.alert').addClass('d-none');
            actualizarCarritoUI();
            return;
        }

        $('#cuponCountdownVender').text(formatearCuentaRegresiva(restante));
    };

    tick();
    cuponCountdownTimer = setInterval(tick, 1000);
}

function autoAplicarCupon() {
    if (!cuponConfig.activo) {
        estado.cupon.aplicado = false;
        estado.cupon.codigo = '';
        return;
    }

    estado.cupon.aplicado = true;
    estado.cupon.codigo = cuponConfig.codigo;
}

function formatearCuentaRegresiva(ms) {
    const totalSeg = Math.floor(ms / 1000);
    const dias = Math.floor(totalSeg / 86400);
    const horas = Math.floor((totalSeg % 86400) / 3600);
    const minutos = Math.floor((totalSeg % 3600) / 60);
    const segundos = totalSeg % 60;
    const pad = n => String(n).padStart(2, '0');

    if (dias > 0) {
        return `${dias}d ${pad(horas)}:${pad(minutos)}:${pad(segundos)}`;
    }

    return `${pad(horas)}:${pad(minutos)}:${pad(segundos)}`;
}

function initPromo2x1() {
    if (!window.Promo2x1 || !window.PROMO_2X1?.activo) {
        return;
    }

    window.Promo2x1.initCountdown('.promo2x1-countdown', () => {
        actualizarCarritoUI();
        document.querySelectorAll('.promo-2x1-wrap').forEach((el) => el.classList.add('d-none'));
    });
}

function obtenerCodigoCuponParaVenta() {
    return (cuponConfig.activo && estado.cupon.aplicado) ? estado.cupon.codigo : '';
}

function nombreClienteResumen() {
    const nombre = ($('#nombreCliente').val() || '').trim();
    const apellido = ($('#apellidoCliente').val() || '').trim();
    const full = (nombre + ' ' + apellido).trim();
    return full || 'Sin datos';
}

function textoNumerosResumen(cantidad) {
    if (!cantidad) {
        return '0';
    }
    if (window.Promo2x1 && window.Promo2x1.aplica(cantidad)) {
        return cantidad + ' → ' + window.Promo2x1.entregados(cantidad);
    }
    return String(cantidad);
}

// --- RESUMEN ---
function actualizarCarritoUI() {

    const cantidad = estado.cantidadSeleccionada;
    const montos = calcularMontos(cantidad);
    const fmt = formatearMoneda;
    const aplica2x1 = window.Promo2x1 && window.Promo2x1.aplica(cantidad);
    const textoNums = textoNumerosResumen(cantidad);

    $('#lblTotalDesktop, #lblTotalMobile').text(fmt(montos.total));
    $('#lblClienteResumen, #lblClienteResumenMob').text(nombreClienteResumen());
    $('#lblRifaResumen, #lblRifaResumenMob').text($('#selectRifa option:selected').text() || 'Sin rifa');
    $('#lblCantidadMobileBadge, #lblCantidadDesktop').text(textoNums);

    if (montos.descuento > 0) {
        $('#lineaDescuentoVenderDesk, #lineaDescuentoVenderMob').removeClass('d-none');
        $('#montoDescuentoVenderDesk, #montoDescuentoVenderMob').text('-' + fmt(montos.descuento));
    } else {
        $('#lineaDescuentoVenderDesk, #lineaDescuentoVenderMob').addClass('d-none');
    }

    if (aplica2x1) {
        const entregados = window.Promo2x1.entregados(cantidad);
        $('#lineaPreventaVenderDesk, #lineaPreventaVenderMob').removeClass('d-none');
        $('#lblPreventaVenderDesk, #lblPreventaVenderMob').text('Paga ' + cantidad + ', recibe ' + entregados);
        $('#lineaVolumenVenderDesk').addClass('d-none');
    } else {
        $('#lineaPreventaVenderDesk, #lineaPreventaVenderMob').addClass('d-none');
        const d = window.DINAMICA || {};
        const desde = d.desdePromo || 25;
        if (!d.preventaActiva && cantidad >= desde) {
            $('#lineaVolumenVenderDesk').removeClass('d-none');
            $('#lblVolumenVenderDesk').text((d.precioPromo || 8000).toLocaleString('es-CO') + ' c/u');
        } else {
            $('#lineaVolumenVenderDesk').addClass('d-none');
        }
    }
}

let ventaPendiente = null;
let ventaEnCurso = false;

function leerClienteFormulario() {
    return {
        id: $('#idCliente').val(),
        nombre: $('#nombreCliente').val().trim(),
        apellido: $('#apellidoCliente').val().trim(),
        celular: $('#celularCliente').val().trim(),
        email: $('#emailCliente').val().trim(),
        depto: $('#departamento').val(),
        ciudad: $('#ciudad').val()
    };
}

function modalConfirmar() {
    const el = document.getElementById('modalConfirmarVenta');
    return el && typeof bootstrap !== 'undefined'
        ? bootstrap.Modal.getOrCreateInstance(el)
        : null;
}

// --- PROCESAR VENTA ---
function procesarVenta() {

    if (ventaEnCurso) return;

    if (!estado.rifa || !estado.rifa.id)
        return alertify.error("No hay sorteo seleccionado.");

    const cliente = leerClienteFormulario();
    const metodo = obtenerMetodoPago();

    if (estado.cantidadSeleccionada <= 0)
        return alertify.error("Debes indicar la cantidad de números.");

    if (!cliente.nombre || !cliente.apellido || !cliente.celular || !cliente.email || !cliente.depto || !cliente.ciudad)
        return alertify.error("Todos los campos obligatorios (*) deben estar llenos.");

    if (!metodo)
        return alertify.error("Elige si pagó en efectivo o transferencia.");

    const montos = calcularMontos(estado.cantidadSeleccionada);
    const aplica2x1 = window.Promo2x1 && window.Promo2x1.aplica(estado.cantidadSeleccionada);

    ventaPendiente = { cliente, metodo, montos };

    $('#modalCliente').text(nombreClienteResumen());
    $('#modalCantidad').text(textoNumerosResumen(estado.cantidadSeleccionada) + ' nums');
    $('#modalMetodo').text(metodo);
    $('#modalTotal').text(formatearMoneda(montos.total));

    if (aplica2x1) {
        $('#modalLineaPreventa').removeClass('d-none');
        $('#modalPreventa').text(
            'Paga ' + estado.cantidadSeleccionada
            + ', recibe ' + window.Promo2x1.entregados(estado.cantidadSeleccionada)
        );
    } else {
        $('#modalLineaPreventa').addClass('d-none');
    }

    const modal = modalConfirmar();
    if (modal) {
        modal.show();
        return;
    }

    ejecutarVenta();
}

function setBotonesVentaCargando(on) {
    document.querySelectorAll('#btnCompletarVenta, #btnCompletarVentaMob, #btnSiCobrar').forEach((btn) => {
        if (!btn.dataset.label) {
            btn.dataset.label = (btn.textContent || '').replace(/\s+/g, ' ').trim();
        }
        btn.disabled = !!on;
        btn.classList.toggle('is-busy', !!on);
        btn.textContent = on ? 'Procesando…' : btn.dataset.label;
    });
}

async function ejecutarVenta() {

    if (ventaEnCurso || !ventaPendiente) return;

    const { cliente, metodo, montos } = ventaPendiente;
    const codigoVenta = "AP" + Date.now() + Math.floor(Math.random() * 100);

    ventaEnCurso = true;
    setBotonesVentaCargando(true);

    const fd = new FormData();
    fd.append('action', 'crear_venta');
    fd.append('code_sale', codigoVenta);
    fd.append('quantity_sale', estado.cantidadSeleccionada);
    fd.append('id_customer', cliente.id);
    fd.append('id_raffle', estado.rifa.id);
    fd.append('total_sale', montos.total);
    fd.append('coupon_code', obtenerCodigoCuponParaVenta());
    fd.append('payment_method_sale', metodo);
    fd.append('name_customer', cliente.nombre);
    fd.append('lastname_customer', cliente.apellido);
    fd.append('phone_customer', cliente.celular);
    fd.append('email_customer', cliente.email);
    fd.append('department_customer', cliente.depto);
    fd.append('city_customer', cliente.ciudad);

    const resetBotones = () => {
        ventaEnCurso = false;
        setBotonesVentaCargando(false);
    };

    try {

        const res = await fetch(estado.config.rutas.ventas, {
            method: 'POST',
            body: fd
        });

        const text = await res.text();
        let json;
        try {
            json = JSON.parse(text);
        } catch (parseErr) {
            console.error('Respuesta no JSON:', text.slice(0, 400));
            throw new Error('Error en el servidor');
        }

        if (json.success) {

            const modal = modalConfirmar();
            if (modal) modal.hide();
            alertify.success("Venta exitosa");
            if (json.warning) {
                alertify.warning(json.warning);
            }
            generarReciboFinal(json.id_sale);

        } else {

            alertify.error(json.message);
            resetBotones();
        }

    } catch (e) {

        alertify.error("Error en el servidor");
        resetBotones();
    }
}

// --- RECIBO ---
async function generarReciboFinal(idVenta) {

    const fd = new FormData();

    fd.append('action', 'detalle_venta');
    fd.append('id_sale', idVenta);

    try {

        const res = await fetch(estado.config.rutas.ventas, {
            method: 'POST',
            body: fd
        });

        const json = await res.json();

        if (json.success) {

            $('.fixed-bottom').addClass('d-none');

            const container = document.querySelector('.body-wrapper-inner');

            container.innerHTML = `
                <div class="container py-5 animated fadeIn">
                    ${json.html_recibo}
                    <div class="mt-4 text-center no-print">
                        <button class="btn btn-dark fw-bold px-5 rounded-pill shadow" onclick="location.reload()">NUEVA VENTA</button>
                    </div>
                </div>`;

            window.scrollTo(0, 0);
        } else {
            alertify.error(json.message || 'No se pudo cargar el comprobante');
        }

    } catch (e) {

        alertify.error("Error visual al cargar el recibo.");

        setTimeout(() => location.reload(), 3000);
    }
}

// --- HELPERS ---
window.procesarVentaMobile = () => procesarVenta();

function llenarFormulario(c) {

    $('#idCliente').val(c.id_customer);
    $('#nombreCliente').val(c.name_customer);
    $('#apellidoCliente').val(c.lastname_customer);
    $('#celularCliente').val(c.phone_customer);
    $('#emailCliente').val(c.email_customer);

    if (c.department_customer) {

        $('#departamento').val(c.department_customer).trigger('change');

        setTimeout(() =>
            $('#ciudad').val(c.city_customer).trigger('change'), 150);
    }

    $('#btnLimpiarCliente').removeClass('d-none');

    toggleInputs(true);
}

function resetClienteForm() {

    $('#idCliente').val('');

    document.getElementById('formClienteVenta').reset();

    $('#departamento, #ciudad, #buscadorCliente')
        .val(null)
        .trigger('change');

    $('#btnLimpiarCliente').addClass('d-none');

    toggleInputs(false);
}

function toggleInputs(bloquear) {

    $('#formClienteVenta input:not([type="hidden"])')
        .prop('readonly', bloquear);
}

function cargarCiudadesVenta(depto) {

    const $ciudad = $('#ciudad');

    $ciudad.empty().append('<option value="">Seleccione...</option>');

    if (depto && datosColombia[depto]) {

        $ciudad.prop('disabled', false);

        datosColombia[depto].forEach(c =>
            $ciudad.append(new Option(c.display, c.value)));

    } else {

        $ciudad.prop('disabled', true);
    }

    $ciudad.trigger('change');
}

$('.paquete-radio').on('change', function(){

    if(this.value === "custom"){

        $('#cantidadManual').show().focus();
        estado.cantidadSeleccionada = 0;

    }else{

        $('#cantidadManual').hide().val('');

        estado.cantidadSeleccionada = parseInt(this.value);
    }

    actualizarCarritoUI();
});

$('#cantidadManual').on('input', function(){

    const val = parseInt(this.value) || 0;

    estado.cantidadSeleccionada = val;

    actualizarCarritoUI();

});