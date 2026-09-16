<?php
require_once "config/config.php";
require_once "includes/coupon.php";
require_once "includes/dinamica.php";
require_once "includes/promo2x1.php";
require_once "includes/salesClosed.php";
$couponActive = CouponHelper::isActive();
$promo2x1Active = Promo2x1Helper::isActive();
$preventaPhase = DinamicaHelper::preventaPhase();
$preventaActive = DinamicaHelper::isPreventaActive();
$salesClosed = SalesClosedHelper::isActive();
$salesClosedMessage = SalesClosedHelper::message();
$proximoAnticipado = DinamicaHelper::proximoAnticipado();
?>
<!doctype html>
<html lang="es" data-sales-closed="<?= $salesClosed ? '1' : '0' ?>">

<head>
   <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ap Fenix</title>
    <meta name="description" content="App Fenix, el poder de ganar comienza aquí. Accede a motos, carros, casas y mucho más. Participa fácil, rápido y seguro desde cualquier lugar de Colombia.">
    <link rel="shortcut icon" href="assets/images/logos/logo.ico" type="image/x-icon">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="assets/css/styles-v20.css?v=35">
    <link rel="stylesheet" href="assets/css/paquetes.css?v=5">
    <link rel="stylesheet" href="assets/css/urgencia.css?v=5">
    <script src="https://t.contentsquare.net/uxa/8c88e0bc219df.js"></script>


    
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1574275570334087');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=1574275570334087&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    
</head>

<body>

    <!-- PRECIO / PREVENTA -->
    <div class="promo-bar text-center py-2">
        <div class="container fw-bold">
            <?php if ($preventaPhase === 'upcoming'): ?>
                Preventa desde el <strong>21 de septiembre</strong> · paga menos, recibe más
            <?php elseif ($preventaActive): ?>
                PREVENTA · <strong>paga menos, recibe más</strong> · $9.000
            <?php else: ?>
                Números a <strong>$9.000</strong> · Desde 25 a <strong>$8.000</strong>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($couponActive): ?>
    <div class="cupon-promo-sticky" id="cuponPromoBar">
        <div class="container">
            <div class="cupon-promo-sticky-inner">
                <span class="cupon-promo-sticky-text">
                    🎟️ <strong>CUPON aplicado</strong> · APF15 · 15% OFF
                </span>
                <span class="cupon-promo-sticky-countdown">
                    Termina en <span id="cuponCountdownBanner" class="promo-countdown-value">--:--:--</span>
                    <span class="promo-countdown-note">(hasta el lunes 23:59)</span>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="promo-2x1-sticky promo-2x1-wrap" id="promo2x1Bar">
        <div class="container">
            <div class="promo-2x1-sticky-inner">
                <?php if ($preventaPhase === 'upcoming'): ?>
                    <span class="promo-2x1-badge">PREVENTA</span>
                    <span>Arranca el <strong>21 de septiembre</strong> · paga menos, recibe más</span>
                    <span class="promo-2x1-countdown">
                        Inicia en <span class="promo-countdown-value promo2x1-countdown">--:--:--</span>
                    </span>
                <?php elseif ($preventaActive): ?>
                    <span class="promo-2x1-badge">PREVENTA</span>
                    <span><strong>paga menos, recibe más</strong></span>
                    <span class="d-none d-md-inline">· 3+1 · 5+2 · 10+3</span>
                    <span class="promo-2x1-countdown">
                        Termina en <span class="promo-countdown-value promo2x1-countdown">--:--:--</span>
                    </span>
                <?php else: ?>
                    <span class="promo-2x1-badge">7 NOV</span>
                    <span><strong><?= htmlspecialchars(DinamicaHelper::DRAW_WITH_LOTTERY, ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <span class="promo-2x1-countdown">
                        Faltan <span class="promo-countdown-value promo2x1-countdown">--:--:--</span>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- NAV -->
    <nav class="navbar navbar-custom">
        <div class="container navbar-inner">
            <a class="navbar-brand m-0 p-0" href="#">
                <img src="assets/images/logos/logo.jpg" class="navbar-logo" alt="AP Fenix">
            </a>
            <span class="navbar-draw">7 Nov · Por Boyacá</span>
        </div>
    </nav>

    <!-- HERO -->
    <section class="py-3">
        <div class="container">

            <div class="row g-3 align-items-start">

                <div class="col-lg-5">
                    <h1 class="hero-title mb-2">
                        DÚO <br><span class="millonario">FÉNIX</span>!
                    </h1>
                    <p class="hero-premio-copy text-muted fw-semibold mb-3">
                        <?= htmlspecialchars(DinamicaHelper::PREMIO_COPY, ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <div class="card border-0 shadow-sm text-center d-none d-lg-block">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Vendidos</span>
                                <span class="progreso-venta-pct">--%</span>
                            </div>
                            <div class="progress my-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated progreso-venta-bar"
                                    style="width:0%"></div>
                            </div>
                            <small class="text-muted d-block"><?= htmlspecialchars(DinamicaHelper::DRAW_WITH_LOTTERY, ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 hero-premios-col">
                    <div class="row g-3 mb-3">

                        <div class="col-12">
                            <div class="card-body d-flex align-items-center gap-3 premio-mayor px-0">
                                <div class="bg-warning bg-opacity-25 rounded-circle p-3 fs-3 lh-1">
                                    🏍️
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1 title-premio-mayor"><?= htmlspecialchars(DinamicaHelper::PREMIO_MAYOR, ENT_QUOTES, 'UTF-8') ?></h5>
                                    <span class="small title-premio-mayor">Por las 4 de Boyacá</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 shadow-sm text-center w-100">
                                <div class="card-body py-3">
                                    <h3 class="fw-bold mb-1"><?= htmlspecialchars(DinamicaHelper::PREMIO_INVERTIDO, ENT_QUOTES, 'UTF-8') ?></h3>
                                    <small class="fw-bold text-muted">Y $7 Millones con el invertido</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 shadow-sm w-100">
                                <div class="card-body py-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                                        <h3 class="fw-bold mb-0 fs-5">Anticipados</h3>
                                        <span class="small text-muted">5 × <strong class="color-dinero-premio">$500.000</strong> · pago inmediato</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <?= DinamicaHelper::renderBendecidosGrupo(DinamicaHelper::BENDECIDOS_200, '10 Bendecidos', '$200.000', '200') ?>
                        </div>
                        <div class="col-12">
                            <?= DinamicaHelper::renderBendecidosGrupo(DinamicaHelper::BENDECIDOS_300, '20 Bendecidos', '$300.000', '300') ?>
                        </div>

                    </div>

                    <div class="card border-0 shadow-sm text-center mb-0 d-lg-none">
                        <div class="card-body">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Vendidos</span>
                                <span class="progreso-venta-pct">--%</span>
                            </div>
                            <div class="progress my-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated progreso-venta-bar"
                                    style="width:0%"></div>
                            </div>
                            <small class="text-muted d-block">
                                <?= htmlspecialchars(DinamicaHelper::DRAW_WITH_LOTTERY, ENT_QUOTES, 'UTF-8') ?>
                            </small>
                        </div>
                    </div>

                    <div class="card bg-dark text-center mb-3 d-none">
                        <div class="card-body">
                            <h2 class="fw-bold text-warning display-6 mb-2" id="precioBoletaDisplay">
                                <div class="spinner-border spinner-border-sm"></div>
                            </h2>
                            <small class="fw-bold text-center text-white mt-2">
                                Mínimo 3 para participar
                            </small>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- COMPRA -->
    <section id="compra" class="py-2 bg-white border-top<?= $salesClosed ? ' ventas-cerradas' : '' ?>">
        <div class="container">
            <h2 class="text-center fw-bold mb-3 mt-3">🎟️ Paquetes</h2>

            <?php if ($salesClosed): ?>
            <div class="alert alert-warning text-center fw-bold shadow-sm mb-4" role="alert">
                <i class="ti ti-lock me-1"></i>
                <?= htmlspecialchars($salesClosedMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>

            <div class="modal fade" id="modalVentasCerradas" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-header bg-dark text-white border-0">
                            <h5 class="modal-title fw-bold">Ventas cerradas</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4 text-center">
                            <div class="display-4 mb-3">🔒</div>
                            <p class="mb-0 fw-semibold text-dark" id="mensajeVentasCerradas">
                                <?= htmlspecialchars($salesClosedMessage, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
                            <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">Entendido</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">

                        <div class="card-header bg-white py-3 px-3">
                            <div class="d-flex justify-content-center align-items-center mb-3">
                                <p class="fw-bold mb-0 text-dark">
                                    <i class="ti ti-grid-dots me-2 text-warning"></i>Selecciona la cantidad                                      
                                </p>
                          
                            </div>
                                <p>
                                    <small class="text-muted py-1 px-3 d-flex justify-content-center align-items-center"><?= htmlspecialchars(DinamicaHelper::packagesSubtitle(), ENT_QUOTES, 'UTF-8') ?></small>
                                </p>
                        </div>

                        <div class="card-body bg-light position-relative">

                            <div class="row g-2 g-md-3 cr-paquetes-grid" id="paquetesNumeros">
                                <?= DinamicaHelper::renderPackageCards($salesClosed) ?>
                            </div>

                            <?php if ($salesClosed): ?>
                            <div class="ventas-cerradas-overlay" id="overlayVentasCerradas" role="button" aria-label="Ventas cerradas"></div>
                            <?php endif; ?>

                            <div class="cr-paquetes-hint">
                                <?= DinamicaHelper::renderPriceHints() ?>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- SIDEBAR DESKTOP -->
                <div class="col-lg-4 d-none d-lg-block">
                    <div class="card border-0 shadow sticky-top" style="">
                        <div class="card-body">
                            <h4 class="fw-bold mb-3">Tu Compra</h4>

                            <div id="listaTicketsDesktop" class="mb-3"></div>

                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Cantidad</span>
                                    <strong id="cantTicketsDesktop">0</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between text-promo-2x1 fw-bold d-none" id="lineaPromo2x1Desktop">
                                    <span>Preventa</span>
                                    <strong id="textoPromo2x1Desktop">—</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between text-success d-none" id="lineaAhorroDesktop">
                                    <span>Ahorro $8 mil</span>
                                    <strong id="textoAhorroDesktop">—</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between text-success d-none" id="lineaDescuentoDesktop">
                                    <span>Descuento APF15 (15%)</span>
                                    <strong id="montoDescuentoDesktop">-$0</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between fw-bold">
                                    <span>Total</span>
                                    <strong class="text-success" id="totalDineroDesktop">$0</strong>
                                </li>
                            </ul>

                            <div class="alert alert-warning small text-center fw-bold<?= $salesClosed ? ' d-none' : '' ?>" id="alertaPasoCompra">
                                🔥 Estás a un paso de participar
                            </div>

                            <?php if ($salesClosed): ?>
                            <button type="button" class="btn btn-secondary w-100 py-3 fw-bold" id="btnVentasCerradasDesktop">
                                Ventas cerradas
                            </button>
                            <?php else: ?>
                            <button class="btn btn-dark w-100 py-3 fw-bold" onclick="abrirCheckout()"
                                id="btnPagarDesktop" disabled>
                                Pagar ahora →
                            </button>
                            <?php endif; ?>

                            <div class="mt-3 pt-3 border-top text-center">
                                <p class="small text-muted mb-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="ti ti-lock-square-rounded text-success fs-5"></i>
                                    Pagos 100% seguros y confirmación inmediata
                                </p>
                                <div class="d-flex justify-content-center align-items-center gap-3 grayscale-hover">
                                    <img src="assets/images/logos/pse.png" alt="PSE" style="height: 40px; width: auto;">
                                    <!--<img src="assets/images/logos/open.jpg" alt="OpenPay"-->
                                    <!--    style="height: 40px; width: auto;">-->
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    
    <section class="seccion-ganadores" id="ultimosBendecidos">
        <canvas id="confetiGanadores" class="seccion-ganadores__canvas" aria-hidden="true"></canvas>
        <div class="container position-relative">
            <h2 class="title-ganadores text-center title-premios mb-3">¡Últimos bendecidos! 🥳</h2>
            <div class="ganadores-carousel-wrap">
                <div id="ganadores-carousel" class="splide" aria-label="Últimos bendecidos">
                    <div class="splide__track">
                        <ul class="splide__list">
                            <li class="splide__slide">
                                <figure class="ganador-card">
                                    <picture>
                                        <source type="image/webp" media="(max-width: 767px)" srcset="assets/images/ganadores/nmax-combo-m.webp">
                                        <source type="image/webp" srcset="assets/images/ganadores/nmax-combo.webp">
                                        <img src="assets/images/ganadores/nmax-combo.jpg" alt="Bendecido NMAX Combo Millonario" width="1050" height="1400" loading="lazy" decoding="async">
                                    </picture>
                                    <figcaption>NMAX · Combo Millonario</figcaption>
                                </figure>
                            </li>
                            <li class="splide__slide">
                                <figure class="ganador-card">
                                    <picture>
                                        <source type="image/webp" media="(max-width: 767px)" srcset="assets/images/ganadores/pulsar-ns-combo-m.webp">
                                        <source type="image/webp" srcset="assets/images/ganadores/pulsar-ns-combo.webp">
                                        <img src="assets/images/ganadores/pulsar-ns-combo.jpg" alt="Bendecido Pulsar NS Combo Millonario" width="787" height="1400" loading="lazy" decoding="async">
                                    </picture>
                                    <figcaption>Pulsar NS · Combo Millonario</figcaption>
                                </figure>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <footer class="bg-dark text-light pt-5">
        <div class="container">

            <div class="row g-4 text-center text-md-start">

                <!-- Marca -->
                <div class="col-md-4">
                    <h5 class="fw-bold text-warning">Ap Fenix</h5>
                    <p class="small text-secondary">
                        ¡El poder de ganar comienza aquí!.<br>
                        Plataforma oficial con Respaldo y Transparencia, seguridad en cada dinámica.
                    </p>

                    <!-- Redes (desktop) -->
                    <div class="d-none d-md-flex gap-2 mt-3">
                        <a href="https://www.instagram.com/angelica_paez00" target="_blank" class="btn btn-outline-light btn-sm rounded-circle">
                            <i class="ti ti-brand-instagram"></i>
                        </a>
                        <a href="https://wa.me/573106817993?text=Hola%20" target="_blank" class="btn btn-outline-success btn-sm rounded-circle">
                            <i class="ti ti-brand-whatsapp"></i>
                        </a>
                        <a href="" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle d-none">
                            <i class="ti ti-brand-facebook"></i>
                        </a>
                    </div>
                </div>

                <!-- Enlaces -->
                <div class="col-md-4">
                    <h6 class="fw-bold text-uppercase mb-3">Enlaces de interés</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <a href="#compra" class="text-secondary text-decoration-none">Comprar stickers</a>
                        </li>
                        <li class="mb-2">
                            <a href="assets/doc/ptd.pdf" class="text-secondary text-decoration-none">Política de privacidad</a>
                        </li>
                        <li class="mb-2">
                            <a href="https://wa.me/573106817993?text=Hola%20" class="text-secondary text-decoration-none">Soporte</a>
                        </li>
                        <li class="mb-2">
                            <button class="btn btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalBuscarTickets">
                                Mis tickets 🎫🔍
                            </button>
                        </li>                       
                    </ul>
                </div>

                <!-- Contacto -->
                <div class="col-md-4">
                    <h6 class="fw-bold text-uppercase mb-3">Contacto</h6>
                    <p class="small text-secondary mb-2">
                        <i class="ti ti-phone me-2"></i> (+57) 310 681 7993
                    </p>
                    <p class="small text-secondary mb-2">
                        <i class="ti ti-mail me-2"></i> info@apfenix.com
                    </p>
                    <p class="small text-secondary">
                        <i class="ti ti-map-pin me-2"></i> Colombia
                    </p>

                    <p class="small text-secondary">
                        <i class="ti ti-map-pin me-2"></i> Pagos Procesados por:
                    </p>
                    <div class="mt-3">
                        <img src="assets/images/logos/pse.png" height="50" class="me-2">
                    </div>
                </div>

            </div>

            <hr class="border-secondary my-4">

            <div class="text-center mb-5">
                <p>Desarrollado por</p>
                <a href="https://ccmsoftware.com.co"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="ccm-footer-brand"
                    title="Visitar CCM Software — ccmsoftware.com.co"
                    aria-label="CCM Software">
                    <img src="<?= htmlspecialchars(ASSETS_URL . '/images/logos/logo-ccm-software.png', ENT_QUOTES, 'UTF-8') ?>"
                         alt="CCM Software"
                         class="ccm-footer-brand__logo"
                         width="163"
                         height="32"
                         decoding="async">
                    <span class="ccm-footer-brand__hint" aria-hidden="true" title="Abrir sitio">↗</span>
                </a>
            </div>

            <!-- Redes mobile -->
            <div class="d-flex d-md-none justify-content-center gap-3 mb-3">
                <a href="https://www.instagram.com/angelica_paez00?igsh=MTNvdGJmNnpxd2xxaw%3D%3D&utm_source=qr" class="btn btn-outline-light rounded-circle">
                    <i class="ti ti-brand-instagram"></i>
                </a>
                <a href="https://wa.me/573106817993?text=Hola%20" class="btn btn-outline-success rounded-circle">
                    <i class="ti ti-brand-whatsapp"></i>
                </a>
                <a href="" class="d-none btn btn-outline-primary rounded-circle">
                    <i class="ti ti-brand-facebook"></i>
                </a>
            </div>

            <!-- Copyright -->
            <div class="text-center small text-secondary pb-2">
                © <?= date('Y'); ?> AP Fenix · Todos los derechos reservados
            </div>

        </div>
    </footer>


    <!-- MOBILE CART -->
    <?php if (!$salesClosed): ?>
    <div class="mobile-cart-bar d-lg-none" id="mobileCart" style="display:none">
        <div class="mobile-cart-info">
            <div class="mobile-cart-top">
                <span class="mobile-cart-label">Total a pagar</span>
                <span class="mobile-cart-price" id="lblTotalMobile">$0</span>
            </div>
            <div class="mobile-cart-detail d-none" id="mobileCartDetail"></div>
        </div>
        <button class="btn btn-warning rounded-pill px-4 fw-bold shadow" onclick="abrirCheckout()" id="btnPagarMobile">
            PAGAR 🔥
        </button>
    </div>
    <?php endif; ?>

    <!-- MODAL SEARCH TICKETS -->
    <div class="modal fade" id="modalBuscarTickets" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">🔍 Buscar mis tickets</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <input type="tel"
                        id="inputBuscarTickets"
                        class="form-control text-center mb-3"
                        placeholder="Ej: 3001234567">

                    <button class="btn btn-warning w-100 fw-bold"
                            onclick="buscarTickets()">
                        Buscar
                    </button>

                    <div id="resultadoBusqueda" class="mt-3"></div>

                </div>

            </div>
        </div>
    </div>    


    <!-- SCRIPTS -->
    <script src="<?= ASSETS_URL ?>/libs/jquery/dist/jquery.min.js"></script>
    <script src="<?= ASSETS_URL ?>/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="assets/js/departamentos-ciudades.js"></script>
    <script>
    window.CUPON_AP_FENIX = <?= json_encode([
        'activo' => $couponActive,
        'codigo' => CouponHelper::CODE,
        'descuento' => CouponHelper::DISCOUNT_PERCENT,
        'expira' => $couponActive ? CouponHelper::getExpiresForJs() : null,
    ], JSON_UNESCAPED_UNICODE) ?>;
    window.DINAMICA = <?= json_encode(DinamicaHelper::frontendConfig(), JSON_UNESCAPED_UNICODE) ?>;
    window.PROMO_2X1 = <?= json_encode([
        'activo' => $promo2x1Active,
        'minimo' => Promo2x1Helper::MIN_QTY,
        'expira' => DinamicaHelper::getExpiresForJs(),
    ], JSON_UNESCAPED_UNICODE) ?>;
    window.SALES_CLOSED = <?= json_encode(SalesClosedHelper::frontendConfig(), JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/js/promo-2x1.js?v=36"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <script src="assets/js/countdown-urgencia.js?v=1"></script>
    <script src="assets/js/frontend-v3.js?v=38"></script>
    <script src="assets/js/confeti-ganadores.js?v=1"></script>
    <script src="assets/js/progreso-ventas.js?v=29"></script>
    <script src="assets/js/buscarTickets.js?v=29"></script>

    <?php if ($salesClosed): ?>
    <script>
    (function () {
        function abrirVentasCerradas() {
            var modalEl = document.getElementById('modalVentasCerradas');
            if (!modalEl || typeof bootstrap === 'undefined') {
                return;
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        window.mostrarModalVentasCerradas = abrirVentasCerradas;

        document.addEventListener('DOMContentLoaded', function () {
            var overlay = document.getElementById('overlayVentasCerradas');
            if (overlay) {
                overlay.addEventListener('click', abrirVentasCerradas);
            }

            var btn = document.getElementById('btnVentasCerradasDesktop');
            if (btn) {
                btn.addEventListener('click', abrirVentasCerradas);
            }

            document.querySelectorAll('.paquete-card').forEach(function (label) {
                label.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    abrirVentasCerradas();
                });
            });
        });
    })();
    </script>
    <?php endif; ?>
    


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Splide === 'undefined' || !document.getElementById('main-carousel')) {
            return;
        }
        new Splide('#main-carousel', {
            type: 'fade',
            autoplay: true,
            interval: 3000,
            arrows: false,
            pagination: false
        }).mount();
    });
    </script>

    <div class="modal fade" id="modalCheckout" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <div class="modal-header bg-dark text-white border-bottom border-warning">
                    <h5 class="modal-title fw-bold">🚀 Finalizar Compra</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <form id="formCheckout">
                        <input type="hidden" id="totalPagarInput" name="totalPagar">

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-uppercase fw-bold text-muted">Tus Números</small><br>
                                    <span class="fw-bold text-dark" id="resumenNumeros">...</span>
                                </div>
                                <div class="text-end">
                                    <small class="text-uppercase fw-bold text-muted">Total</small><br>
                                    <span class="fw-bold text-success fs-5" id="resumenTotal">$0</span>
                                    <div class="small text-success d-none" id="lineaDescuentoCheckout">
                                        Descuento 15%: <span id="montoDescuentoCheckout">-$0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($couponActive): ?>
                        <div class="card border-warning mb-4 shadow-sm" id="bloqueCuponCheckout">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <span class="fw-bold text-warning d-block">🎟️ Descuento del 15% activo</span>
                                        <span class="small text-muted">Cupón <strong>APF15</strong> aplicado automáticamente a tu compra</span>
                                    </div>
                                    <span class="badge bg-danger" id="cuponCountdownModal">--:--:--</span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($promo2x1Active): ?>
                        <div class="card promo-2x1-checkout mb-4 shadow-sm promo-2x1-wrap" id="bloquePromo2x1Checkout">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <span class="promo-2x1-badge">PREVENTA</span>
                                        <span class="fw-bold text-promo-2x1 ms-1">Preventa: paga menos, recibe más</span>
                                        <span class="small text-muted d-block" id="textoPromo2x1Checkout">paga menos, recibe más</span>
                                    </div>
                                    <span class="badge badge-promo-2x1 promo2x1-countdown">--:--:--</span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <h6 class="fw-bold mb-3">Datos del Comprador</h6>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="celularCliente" required placeholder="Celular">
                            <label>Celular</label>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nombreCliente" required
                                        placeholder="Nombre">
                                    <label>Nombre</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="apellidoCliente" required
                                        placeholder="Apellido">
                                    <label>Apellido</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="emailCliente" required placeholder="Correo">
                            <label>Correo Electrónico</label>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <div class="select-floating-label-group">
                                    <select class="form-select select2-ubicacion" id="departamento" required>
                                        <option value="">Departamento...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="select-floating-label-group">
                                    <select class="form-select select2-ubicacion" id="ciudad" required>
                                        <option value="">Ciudad...</option>
                                    </select>
                            </div>
                        </div>

                        <hr>

                            <h6 class="fw-bold mb-3 mt-4">Selecciona tu método de pago</h6>
                            
                            <div class="d-flex gap-2 mb-4">
                                <button type="button"
                                    class="btn btn-outline-success w-100 py-1 fw-bold shadow-sm d-flex flex-column align-items-center"
                                    data-metodo="pse"
                                    onclick="seleccionarMetodo('pse')">
                                    <img src="assets/images/logos/pse.png" alt="PSE" style="height: 25px;" class="mb-2">
                                    <span class="small">Pagar con PSE</span>
                                </button>
                            
                                <button type="button"
                                    class="btn btn-outline-success w-100 py-1 fw-bold shadow-sm d-flex flex-column align-items-center"
                                    data-metodo="transferencia"
                                    onclick="seleccionarMetodo('transferencia')">
                                    <span class="fs-4 mb-1">🏦</span>
                                    <span class="small">Transferencia</span>
                                </button>
                            </div>

                        <div id="contenedorMetodoPago">
                            <div id="metodoPSE" class="metodo-pago d-none border rounded p-4 text-center bg-light shadow-sm">
                                <p class="text-muted small">Serás redirigido a la plataforma segura de PSE.</p>
                                <button type="button" class="btn btn-warning w-100 py-3 fw-bold text-uppercase shadow" onclick="iniciarPagoPSE()">
                                    Pagar
                                </button>
                            </div>
                        
                            <div id="metodoTransferencia" class="metodo-pago d-none">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-success text-white fw-bold py-3 text-center">
                                        Datos para tu transferencia
                                    </div>
                                    <div class="card-body bg-light">
                                        
                                        <div class="bg-white p-3 rounded border mb-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="text-secondary fw-bold small text-uppercase">Ahorros Bancolombia</div>
                                                <span id="bancolombia" class="h5 fw-bold mb-0">68005493483</span><br>
                                                <small class="text-muted">Titular: Angélica Paez</small>
                                            </div>
                                            <button type="button" class="btn btn-dark btn-sm rounded-pill" onclick="copiarTexto('bancolombia')">
                                                Copiar
                                            </button>
                                        </div>
                        
                                        <div class="bg-white p-3 rounded border mb-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="text-secondary fw-bold small text-uppercase">Llave Breve 🔑</div>
                                                <span id="llave" class="h5 fw-bold mb-0">@angelicap5037</span><br>
                                                <small class="text-muted">Titular: Angélica Paez</small>
                                            </div>
                                            <button type="button" class="btn btn-dark btn-sm rounded-pill" onclick="copiarTexto('llave')">
                                                Copiar
                                            </button>
                                        </div>
                                        
                                        <div class="bg-white p-3 rounded border mb-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="text-secondary fw-bold small text-uppercase">Nequi</div>
                                                <span id="nequi" class="h5 fw-bold mb-0">3202925348</span><br>
                                                <small class="text-muted">Titular: Angélica Paez</small>
                                            </div>
                                            <button type="button" class="btn btn-dark btn-sm rounded-pill" onclick="copiarTexto('nequi')">
                                                Copiar
                                            </button>
                                        </div>                                        
                        
                                        <div class="mt-4 p-3 bg-white rounded border">
                                            <label class="fw-bold mb-2 small text-uppercase">📤 Sube tu comprobante</label>
                                            <input type="file" class="form-control mb-3" id="comprobantePago" accept="image/*,application/pdf">
                                            <button type="button" class="btn btn-success w-100 py-3 fw-bold shadow-sm" onclick="procesarTransferencia(event)">
                                                CONFIRMAR PAGO 🚀
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>                  

                    </form>
                    <div class="mt-3 pt-3 border-top text-center">
                        <p class="small text-muted mb-2 d-flex align-items-center justify-content-center gap-1">
                            <i class="ti ti-lock-square-rounded text-success fs-5"></i>
                            Pagos 100% seguros y confirmación inmediata
                        </p>
                        <div class="d-flex justify-content-center align-items-center gap-3 grayscale-hover">
                            <!--<img src="assets/images/logos/pse.png" alt="PSE" style="height: 40px; width: auto;">-->
                            <!--<img src="assets/images/logos/open.jpg" alt="OpenPay" style="height: 40px; width: auto;">-->
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

<?php
include "includes/preloader.php";
include "includes/btn-share.php";
?>
    
</body>

</html>