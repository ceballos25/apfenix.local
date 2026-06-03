/**
 * frontend-v3.js – SISTEMA POR PAQUETES
 * Caballos Revelo
 */

/* ================== ESTADO GLOBAL ================== */

const estado = {
    rifa: { id: null, precio: 0 },
    inventarioCompleto: [],
    cantidadSeleccionada: 0,
    rutas: {
        numeros: 'front/ajax/numeros.ajax.php',
        ventas: 'front/ajax/ventas.ajax.php',
        clientes: 'front/ajax/clientes.ajax.php'
    }
};

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

    const precioUnitario = obtenerPrecioUnitario(cant);

    const total = cant * precioUnitario;

    actualizarPrecioVisual(cant);

    const fmt = n =>
        new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(n);


    $('#cantTicketsDesktop, #lblCantidadMobile').text(cant);

    $('#totalDineroDesktop, #lblTotalMobile, #resumenTotal').text(fmt(total));


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

    const total =
        estado.cantidadSeleccionada *
        obtenerPrecioUnitario(estado.cantidadSeleccionada);

    $('#totalPagarInput').val(total);

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


    const payload = {

        action: 'crear_respaldo',

        id_raffle: estado.rifa.id,

        quantity: estado.cantidadSeleccionada,

        amount:
            estado.cantidadSeleccionada *
            obtenerPrecioUnitario(estado.cantidadSeleccionada),

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

    const total =
        estado.cantidadSeleccionada *
        obtenerPrecioUnitario(estado.cantidadSeleccionada);

    const formData = new FormData();

    formData.append('action', 'crear_transferencia_completa');

    // 🔥 datos compra
    formData.append('id_raffle', estado.rifa.id);
    formData.append('quantity', estado.cantidadSeleccionada);
    formData.append('amount', total);

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