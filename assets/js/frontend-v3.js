/**
 * frontend-v3.js – SISTEMA POR PAQUETES
 * Caballos Revelo
 */

/* ================== ESTADO GLOBAL ================== */

const estado = {
    rifa: { id: null, precio: 0 },
    inventarioCompleto: [],
    cantidadSeleccionada: 0,
    cupon: {
        aplicado: false,
        codigo: ''
    },
    rutas: {
        numeros: 'front/ajax/numeros.ajax.php',
        ventas: 'front/ajax/ventas.ajax.php',
        clientes: 'front/ajax/clientes.ajax.php'
    }
};

const cuponConfig = window.CUPON_AP_FENIX || { activo: false };
let cuponCountdownTimer = null;

    document.addEventListener('DOMContentLoaded', function () {
    var main = new Splide('#main-carousel', {
        type: 'fade',
        rewind: true,
        pagination: false,
        arrows: true,
    });

    var thumbnails = new Splide('#thumbnail-carousel', {
        fixedWidth: 90,
        fixedHeight: 60,
        gap: 10,
        rewind: true,
        pagination: false,
        isNavigation: true,
        focus: 'center',
        cover: true,
        breakpoints: {
        600: {
            fixedWidth: 60,
            fixedHeight: 44,
        },
        },
    });

    main.sync(thumbnails);
    main.mount();
    thumbnails.mount();
    });

/* ================== INIT ================== */

$(document).ready(function () {

    inicializarSistema();

    $('#celularCliente').on('input paste', function () {

        let val = $(this).val().replace(/\D/g, '');

        if (val.startsWith('57') && val.length > 10)
            val = val.substring(2);

        $(this).val(val);

        if (val.length === 10)
            buscarClientePorCelular(val);

    });

    if (typeof datosColombia !== 'undefined') {

        cargarDepartamentos();

        $('#departamento').on('change', function () {
            cargarCiudades(this.value);
        });

    }

    initCuponPromo();

});


/* ================== SISTEMA ================== */

async function inicializarSistema() {

    const fd = new FormData();
    fd.append('action', 'obtener_rifas');

    const res = await fetch(estado.rutas.numeros, {
        method: 'POST',
        body: fd
    });

    const json = await res.json();

    if (json.success && json.data.length) {

        const r = json.data[0];

        estado.rifa.id = r.id_raffle;
        estado.rifa.precio = parseInt(r.price_raffle, 10);

        actualizarPrecioVisual(0);

        cargarInventario();

    }

}


/* ================== INVENTARIO ================== */

async function cargarInventario() {

    showPreloader();

    const fd = new FormData();
    fd.append('action', 'obtener_inventario');
    fd.append('id_raffle', estado.rifa.id);

    const res = await fetch(estado.rutas.numeros, {
        method: 'POST',
        body: fd
    });

    const json = await res.json();

    if (json.success) {

        estado.inventarioCompleto = json.data.filter(t => t.status_ticket == 0);

    }

    hidePreloader();

}


/* ================== PAQUETES ================== */

$(document).on('change', '.paquete-radio', function () {

    if (this.value === 'custom') {
        $('#cantidadManual').show().focus();
        return;
    }

    $('#cantidadManual').hide();

    estado.cantidadSeleccionada = parseInt(this.value);

    actualizarUI();

});

$('#cantidadManual').on('blur', function () {

    let cant = parseInt(this.value);

    if (!cant) return;

    if (cant < 20) {

        toastError("Recuerda mínimo 20 para participar");

        this.value = 20;
        cant = 20;
    }

    estado.cantidadSeleccionada = cant;

    actualizarUI();

});


/* ================== PRECIOS ================== */

function obtenerPrecioUnitario(cantidad) {

    return cantidad >= 20
        ? 900
        : estado.rifa.precio;

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

    const expira = new Date(cuponConfig.expira).getTime();

    const tick = () => {
        const restante = expira - Date.now();

        if (restante <= 0) {
            clearInterval(cuponCountdownTimer);
            cuponConfig.activo = false;
            estado.cupon.aplicado = false;
            $('#cuponPromoBar, #bloqueCuponCheckout').addClass('d-none');
            actualizarUI();
            return;
        }

        const texto = formatearCuentaRegresiva(restante);
        $('#cuponCountdownBanner, #cuponCountdownModal').text(texto);
    };

    tick();
    cuponCountdownTimer = setInterval(tick, 1000);

    autoAplicarCupon();
}

function autoAplicarCupon() {

    if (!cuponConfig.activo) {
        estado.cupon.aplicado = false;
        estado.cupon.codigo = '';
        return;
    }

    estado.cupon.aplicado = true;
    estado.cupon.codigo = cuponConfig.codigo;
    actualizarUI();
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

function obtenerCodigoCuponParaPago() {
    return (cuponConfig.activo && estado.cupon.aplicado) ? estado.cupon.codigo : '';
}

function actualizarPrecioVisual(cantidad) {

    if (cantidad >= 20) {

        $('#precioBoletaDisplay').html(
            `$8.000 <small class="text-white fs-6">c/u · PROMO 🔥</small>`
        );

    } else {

        $('#precioBoletaDisplay').text(
            '$' + estado.rifa.precio.toLocaleString('es-CO')
        );

    }

}


/* ================== UI ================== */

function actualizarUI() {

    const cant = estado.cantidadSeleccionada;

    const montos = calcularMontos(cant);

    actualizarPrecioVisual(cant);

    const fmt = formatearMoneda;


    $('#cantTicketsDesktop, #lblCantidadMobile').text(cant);

    $('#totalDineroDesktop, #lblTotalMobile, #resumenTotal').text(fmt(montos.total));

    if (montos.descuento > 0) {
        $('#lineaDescuentoDesktop, #lineaDescuentoCheckout').removeClass('d-none');
        $('#montoDescuentoDesktop, #montoDescuentoCheckout').text('-' + fmt(montos.descuento));
    } else {
        $('#lineaDescuentoDesktop, #lineaDescuentoCheckout').addClass('d-none');
    }


    $('#resumenNumeros').html(

        cant
            ? `<span class="fw-bold">${cant}</span>`
            : '<span class="text-muted">Sin selección</span>'

    );


    $('#btnPagarDesktop, #btnPagarMobile').prop('disabled', !cant);


    const bar = document.getElementById('mobileCart');

    if (bar)
        bar.style.display = cant ? '' : 'none';


    // if (cant >= 1)
    //     $('#promoCheckoutmMobile').removeClass('d-none');
    // else
    //     $('#promoCheckoutmMobile').addClass('d-none');

}


/* ================== UBICACIÓN ================== */

function cargarDepartamentos() {

    const $d = $('#departamento');

    $d.empty().append('<option value="">Seleccione...</option>');

    Object.keys(datosColombia)
        .sort()
        .forEach(d => $d.append(new Option(d, d)));

}

function cargarCiudades(dep) {

    const $c = $('#ciudad');

    $c.empty().append('<option value="">Seleccione...</option>');

    if (dep && datosColombia[dep]) {

        datosColombia[dep].forEach(c =>
            $c.append(new Option(c.display || c, c.value || c))
        );

    }

}


/* ================== CLIENTE ================== */

async function buscarClientePorCelular(tel) {

    const fd = new FormData();

    fd.append('action', 'obtener');
    fd.append('search', tel);

    const r = await fetch(estado.rutas.clientes, {
        method: 'POST',
        body: fd
    });

    const j = await r.json();

    if (j.success && j.data.length) {

        const c = j.data[0];

        $('#nombreCliente').val(c.name_customer);
        $('#apellidoCliente').val(c.lastname_customer);
        $('#emailCliente').val(c.email_customer);

        $('#departamento')
            .val(c.department_customer)
            .trigger('change');

        setTimeout(() => {
            $('#ciudad').val(c.city_customer);
        }, 200);

    }

}


/* ================== UTILIDADES ================== */

function toastError(msg) {

    Toastify({
        text: msg,
        backgroundColor: '#dc3545',
        duration: 2500
    }).showToast();

}

function esEmailValido(email) {

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

}

function setLoadingBtn(btnId, loading = true) {

    const btn = document.getElementById(btnId);

    if (!btn) return;

    btn.disabled = loading;

    btn.querySelector('.spinner-border')
        ?.classList.toggle('d-none', !loading);

}


/* ================== CHECKOUT ================== */

function abrirCheckout() {

if (!estado.cantidadSeleccionada || estado.cantidadSeleccionada < 3) {

    toastError('La compra mínima es de 3 números');

    return;

}

    if (estado.cantidadSeleccionada > estado.inventarioCompleto.length) {

        toastError('No hay suficientes números disponibles');

        return;

    }

    const montos = calcularMontos(estado.cantidadSeleccionada);

    $('#totalPagarInput').val(montos.total);

    const modal = new bootstrap.Modal(
        document.getElementById('modalCheckout')
    );

    modal.show();

}


/* ================== PAGO ================== */

async function iniciarPagoPSE() {

    const datos = validarFormularioCheckout();

    if (!datos) return;


    if (estado.cantidadSeleccionada > estado.inventarioCompleto.length) {

        toastError('No hay suficientes números disponibles');

        return;

    }


    setLoadingBtn('btnPagarFinal', true);

    showPreloader();


    const montos = calcularMontos(estado.cantidadSeleccionada);

    const payload = {

        action: 'crear_respaldo',

        id_raffle: estado.rifa.id,

        quantity: estado.cantidadSeleccionada,

        amount: montos.total,

        coupon_code: obtenerCodigoCuponParaPago(),

        name_customer: datos.nombre,
        lastname_customer: datos.apellido,
        phone_customer: datos.celular,
        email_customer: datos.email,
        department_customer: datos.departamento,
        city_customer: datos.ciudad

    };


    try {

        const res = await fetch('front/ajax/web.ajax.php', {
            method: 'POST',
            body: new URLSearchParams(payload)
        });

        const json = await res.json();

        if (!json.success)
            throw new Error(json.message || 'No se pudo crear el respaldo');


        window.PAYMENT_BACKUP_ID = json.id_payment_backup;

        await irAOpenPay();

    }

    catch (e) {

        toastError(e.message);

        setLoadingBtn('btnPagarFinal', false);

        hidePreloader();

    }

}


/* ================== OPENPAY ================== */

async function irAOpenPay() {

    if (!window.PAYMENT_BACKUP_ID) {

        toastError("No hay respaldo de pago");

        return;

    }

    const data = {

        action: 'ir_openpay',

        id_payment_backup: window.PAYMENT_BACKUP_ID,

        name_customer: $('#nombreCliente').val(),
        lastname_customer: $('#apellidoCliente').val(),
        phone_customer: $('#celularCliente').val(),
        email_customer: $('#emailCliente').val()

    };


    try {

        const res = await fetch('front/ajax/web.ajax.php', {
            method: 'POST',
            body: new URLSearchParams(data)
        });

        const json = await res.json();

        if (!json.success)
            throw new Error(json.message || 'Error al ir a OpenPay');


        window.location.href = json.redirect_url;

    }

    catch (e) {

        toastError(e.message);

        setLoadingBtn('btnPagarFinal', false);

        hidePreloader();

    }

}


/* ================== VALIDACIÓN ================== */

function validarFormularioCheckout() {

if (!estado.cantidadSeleccionada || estado.cantidadSeleccionada < 3) {

    toastError("La compra mínima es de 3 números");

    return false;

}

    const datos = {

        nombre: $('#nombreCliente').val().trim(),
        apellido: $('#apellidoCliente').val().trim(),
        celular: $('#celularCliente').val().replace(/\D/g, ''),
        email: $('#emailCliente').val().trim(),
        departamento: $('#departamento').val(),
        ciudad: $('#ciudad').val()

    };


    if (datos.celular.length !== 10)
        return toastError("Celular inválido"), false;

    if (!datos.nombre)
        return toastError("Ingresa tu nombre"), false;

    if (!datos.apellido)
        return toastError("Ingresa tu apellido"), false;

    if (!esEmailValido(datos.email))
        return toastError("Correo inválido"), false;

    if (!datos.departamento)
        return toastError("Selecciona departamento"), false;

    if (!datos.ciudad)
        return toastError("Selecciona ciudad"), false;


    return datos;

}

async function seleccionarMetodo(tipo) {

    const pse = document.getElementById('metodoPSE');
    const transferencia = document.getElementById('metodoTransferencia');

    const metodos = [pse, transferencia];

    // ocultar
    metodos.forEach(el => {
        el.classList.remove('show');
        el.classList.add('d-none');
    });

    // botones activos
    document.querySelectorAll('[data-metodo]').forEach(btn => {
        btn.classList.remove('active');
    });

    const btnActivo = document.querySelector(`[data-metodo="${tipo}"]`);
    if (btnActivo) btnActivo.classList.add('active');

    // 🔥 SOLO TRANSFERENCIA CREA RESPALDO
    if (tipo === 'transferencia') {

        const ok = await crearRespaldoTransferencia();
        if (!ok) return;

    }

    const target = tipo === 'pse' ? pse : transferencia;

    target.classList.remove('d-none');

    requestAnimationFrame(() => {
        target.classList.add('show');
    });
}

async function procesarTransferencia(e) {

    e.preventDefault();

    const datos = validarFormularioCheckout();
    if (!datos) return;

    const file = document.getElementById('comprobantePago').files[0];

    if (!file) {
        toastError("Debes subir el comprobante");
        return;
    }

    if (estado.cantidadSeleccionada < 3) {
        toastError("Mínimo 3 números");
        return;
    }

    const montos = calcularMontos(estado.cantidadSeleccionada);

    const formData = new FormData();

    formData.append('action', 'crear_transferencia_completa');

    // 🔥 datos compra
    formData.append('id_raffle', estado.rifa.id);
    formData.append('quantity', estado.cantidadSeleccionada);
    formData.append('amount', montos.total);
    formData.append('coupon_code', obtenerCodigoCuponParaPago());

    // 🔥 cliente
    formData.append('name_customer', datos.nombre);
    formData.append('lastname_customer', datos.apellido);
    formData.append('phone_customer', datos.celular);
    formData.append('email_customer', datos.email);
    formData.append('department_customer', datos.departamento);
    formData.append('city_customer', datos.ciudad);

    // 🔥 archivo
    formData.append('comprobante', file);

    showPreloader();

    try {

        const res = await fetch('front/ajax/web.ajax.php', {
            method: 'POST',
            body: formData
        });

        const json = await res.json();

        if (!json.success)
            throw new Error(json.message);

        // 🚀 REDIRECCIÓN FINAL
        window.location.href = `transferencia.php?code=${json.code_transfer}`;

    } catch (e) {

        toastError(e.message);

    }

    hidePreloader();
}

function copiarTexto(id) {

    const el = document.getElementById(id);

    if (!el) {
        console.error("No existe el elemento:", id);
        return;
    }

    const texto = el.innerText;

    // ✔️ MÉTODO MODERNO
    if (navigator.clipboard && window.isSecureContext) {

        navigator.clipboard.writeText(texto)
            .then(() => mostrarToastCopiado(texto))
            .catch(() => copiarFallback(texto));

    } else {
        // ❗ fallback para http o navegadores viejos
        copiarFallback(texto);
    }
}


// 🔁 FALLBACK UNIVERSAL
function copiarFallback(texto) {

    const textarea = document.createElement("textarea");
    textarea.value = texto;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";

    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    try {
        document.execCommand("copy");
        mostrarToastCopiado(texto);
    } catch (err) {
        alert("No se pudo copiar automáticamente 😢");
    }

    document.body.removeChild(textarea);
}


// 🎯 TOAST BONITO
function mostrarToastCopiado(texto) {
    Toastify({
        text: "Copiado: " + texto,
        duration: 2000,
        gravity: "top",
        position: "center",
        backgroundColor: "#28a745"
    }).showToast();
}

async function crearRespaldoTransferencia() {
    return true; // TEMPORAL para probar
}