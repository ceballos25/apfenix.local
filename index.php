<?php
require_once "config/config.php";
require_once "includes/coupon.php";
$porcentaje_venta = 46.6;
$couponActive = CouponHelper::isActive();
?>
<!doctype html>
<html lang="es">

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
    <link rel="stylesheet" href="assets/css/styles-v20.css?v=9">
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

    <!-- PROMO $900 (scroll normal) -->
    <div class="promo-bar text-center py-2">
        <div class="container fw-bold">
            🚨 ¡ATENCIÓN! sticker a <strong>$900</strong> 🚨
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

    <!-- NAV -->
    <nav class="navbar navbar-expand-lg navbar-custom shadow-sm py-2">
        <div class="container justify-content-center justify-content-lg-between align-items-center">
            <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="#">
                <img src="assets/images/logos/logo.jpg" class="navbar-logo" alt="AP Fenix">
                <span class="fw-bold text-light lh-sm navbar-brand-text">AP FENIX</span>
            </a>
            <div id="promoCheckoutmMobile"
             class="alert alert-success py-2 mt-2 text-center fw-bold d-none">
                
            </div>
            <span class="badge bg-dark text-light px-3 py-2 d-none d-lg-inline">
                <i class="ti ti-calendar-event me-1"></i> Juega cuando lleguemos al 100% de las ventas
            </span>
        </div>
    </nav>

    <!-- HERO -->
    <section class="py-3">
        <div class="container">
            <div class="row g-3 align-items-start">

                <div class="col-lg-6 hero-fotos-col">

                    <h1 class="hero-title mb-3">
                        ¡Gran combo <br><span class="millonario">Millonario 🤑</span>!
                    </h1>

                    <p class="text-muted fw-semibold mb-4 d-none">
                    </p>

                    <div class="card border-0 bg-transparent shadow-none rounded-4 overflow-hidden" style="text-align: center;">

                    <!-- Carrusel principal -->
                    <section id="main-carousel" class="splide">
                        <div class="splide__track">
                        <ul class="splide__list">
                            <li class="splide__slide">
                            <img class="premios-primer-sorteo" src="assets/images/1.jpg" alt="Imagen 1" loading="lazy">
                            </li>
                            <li class="splide__slide">
                            <img class="premios-primer-sorteo" src="assets/images/2.jpg" alt="Imagen 2" loading="lazy">
                            </li>
                        </ul>
                        </div>
                    </section>

                    <!-- Miniaturas -->
                    <section id="thumbnail-carousel" class="splide mt-3">
                        <div class="splide__track">
                        <ul class="splide__list">
                            <li class="splide__slide">
                            <img src="assets/images/1.jpg" alt="Miniatura 1" loading="lazy">
                            </li>
                            <li class="splide__slide">
                            <img src="assets/images/2.jpg" alt="Miniatura 2" loading="lazy">
                            </li>
                        </ul>
                        </div>
                    </section>
                    </div>

                    <!-- Progreso bajo fotos (desktop) -->
                    <div class="card border-0 shadow-sm text-center mt-3 d-none d-lg-block">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>🔥 Vendidos</span>
                                <span><?= $porcentaje_venta ?>%</span>
                            </div>
                            <div class="progress my-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    style="width:<?= $porcentaje_venta ?>%"></div>
                            </div>
                            <small class="text-muted">Juega cuando lleguemos al 100% de las ventas</small>
                        </div>
                    </div>

                </div>

                <div class="col-lg-6 hero-premios-col">

                    <div class="row g-3 mb-4">

                        <!-- PREMIO MAYOR -->
                        <div class="col-12 my-2">
                            <div class="my-2">
                                <div class="card-body d-flex align-items-start gap-3 premio-mayor">

                                    <div class="bg-warning bg-opacity-25 rounded-circle p-3">
                                        <i class="ti ti-trophy fs-4 text-warning"></i>
                                    </div>

                                    <div>
                                        <h5 class="fw-bold mb-1 title-premio-mayor">Premio Mayor</h5>
                                        <p class="fs-6 text-muted fw-bold  mb-0">
                                            Nmax 0 km +
                                            $3.000.000 💰
                                        </p>
                                        <span class="small text-muted">Por la loter1a de Medellin 🎫</span>
                                    </div>

                                </div>
                            </div>
                        </div>


                        <!-- NUMERO INVERTIDO -->
                        <div class="col-md-12 d-flex my-2">
                            <div class="card border-0 shadow-sm text-center w-100">
                                <div class="card-body">

                                    <h3 class="fw-bold mb-1">
                                        Pulsar NS 125 FI + <span class="color-dinero-premio"> $2.000.000</span>
                                    </h3>

                                    <small class="fw-bold text-muted">
                                        Número invertido
                                    </small>
                                    
                                </div>
                                <span class="small text-muted">Por la loter1a de Medellin 🎫</span>
                            </div>
                        </div>                        


                        <!-- ANTICIPADOS -->
                        <div class="col-md-12 d-flex my-2">
                            <div class="card border-0 shadow-sm text-center w-100">
                                <div class="card-body">

                                    <h3 class="fw-bold mb-2">
                                        10 Anticipados de <span class="color-dinero-premio">$500.000</span>
                                    </h3>

                                    <div class="confirmados-panel confirmados-panel--anticipado text-start mt-3">
                                        <p class="confirmados-panel__aviso small fw-bold mb-1">
                                            <i class="ti ti-info-circle me-1"></i> 1 anticipado se acumuló
                                        </p>
                                        <p class="small fw-bold text-muted mb-2">2° anticipado quedó en $1.000.000</p>
                                        <div class="confirmados-fila">
                                            <span class="confirmados-fila__fecha">29 de mayo</span>
                                            <span class="confirmados-fila__nombre">Laura Cortés</span>
                                            <span class="confirmados-fila__extra color-dinero-premio">Bendición $1.000.000</span>
                                            <span class="numero-apfenix numero-apfenix--bendecido">32985</span>
                                        </div>
                                        <p class="confirmados-fila__nota small text-muted mb-0 mt-2">Por la de Medellín 🎫</p>
                                    </div>

                                    <small class="fw-bold text-muted d-none">
                                        Aprovecha nuestro primer anticipado éste viernes con la de Medellín 🎫 <br>
                                        ¡Pago Inmediato!
                                    </small>

                                </div>
                                <span class="small text-muted"></span>
                            </div>
                        </div>

                        <!-- AFORTUNADOS -->
                        <div class="col-12 d-flex">
                            <div class="card border-0 shadow-sm text-center w-100">
                                <div class="card-body">

                                    <h3 class="fw-bold text-dark mb-1">
                                        10 Bendecidos de <span class="color-dinero-premio">$500.000</span>
                                    </h3>
                                    <button class="btn btn-success m-2 fw-bold tachado">30405</button>
                                    <button class="btn btn-success m-2 fw-bold">00007</button>
                                    <button class="btn btn-success m-2 fw-bold tachado">30068</button>
                                    <button class="btn btn-success m-2 fw-bold">26034</button>
                                    <button class="btn btn-success m-2 fw-bold">77777</button>
                                    <button class="btn btn-success m-2 fw-bold tachado">82041</button>
                                    <button class="btn btn-success m-2 fw-bold tachado">12998</button>
                                    <button class="btn btn-success m-2 fw-bold tachado">95585</button>
                                    <button class="btn btn-success m-2 fw-bold">57001</button>
                                    <button class="btn btn-success m-2 fw-bold">53760</button>

                                    <div class="display-flex m-1">
                                    <small class="fw-bold text-muted m-5 justify-content-center">
                                        ¡Pago Inmediato!
                                    </small>
                                    </div>

                                    <div class="confirmados-panel text-start mt-2 pt-3 border-top">
                                        <p class="confirmados-panel__resumen small fw-bold mb-2">
                                            Quedan <span class="color-dinero-premio">6</span> anticipados y
                                            <span class="color-dinero-premio">5</span> bendecidos por jugar
                                        </p>
                                        <h4 class="confirmados-panel__titulo fw-bold mb-2">✨ Bendecidos confirmados</h4>
                                        <div class="confirmados-lista">
                                            <div class="confirmados-fila">
                                                <span class="confirmados-fila__fecha">16 de abril</span>
                                                <span class="confirmados-fila__nombre">Henry Carrillo</span>
                                                <span class="numero-apfenix numero-apfenix--bendecido">95585</span>
                                            </div>
                                            <div class="confirmados-fila">
                                                <span class="confirmados-fila__fecha">23 de abril</span>
                                                <span class="confirmados-fila__nombre">Fabio Gómez</span>
                                                <span class="numero-apfenix numero-apfenix--bendecido">30405</span>
                                            </div>
                                            <div class="confirmados-fila">
                                                <span class="confirmados-fila__fecha">07 de mayo</span>
                                                <span class="confirmados-fila__nombre">Luz Goez</span>
                                                <span class="numero-apfenix numero-apfenix--bendecido">82041</span>
                                            </div>
                                            <div class="confirmados-fila">
                                                <span class="confirmados-fila__fecha">19 de mayo</span>
                                                <span class="confirmados-fila__nombre">Migdonia García</span>
                                                <span class="numero-apfenix numero-apfenix--bendecido">30068</span>
                                            </div>
                                            <div class="confirmados-fila">
                                                <span class="confirmados-fila__fecha">24 de mayo</span>
                                                <span class="confirmados-fila__nombre">Antonio Martínez</span>
                                                <span class="numero-apfenix numero-apfenix--bendecido">12998</span>
                                            </div>
                                            <div class="confirmados-fila">
                                                <span class="confirmados-fila__fecha">19 de junio</span>
                                                <span class="confirmados-fila__nombre">María Espinosa</span>
                                                <span class="numero-apfenix numero-apfenix--bendecido">31903</span>
                                            </div>                                            

                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>


                    <!-- PROGRESO (móvil / tablet) -->
                    <div class="card border-0 shadow-sm text-center mb-3 d-lg-none">
                        <div class="card-body">

                            <div class="d-flex justify-content-between fw-bold">
                                <span>🔥 Vendidos</span>
                                <span><?= $porcentaje_venta ?>%</span>
                            </div>

                            <div class="progress my-2">
                                <div class="progress-bar bg- progress-bar-striped progress-bar-animated"
                                    style="width:<?= $porcentaje_venta ?>%">
                                </div>
                            </div>

                            <small class="text-muted">
                                 Juega cuando lleguemos al 100% de las ventas
                            </small>

                        </div>
                    </div>


                    <!-- PRECIO BOLETA -->
                    <div class="card bg-dark text-center mb-3 d-none">
                        <div class="card-body">

                            <h2 class="fw-bold text-warning display-6 mb-2" id="precioBoletaDisplay">
                                <div class="spinner-border spinner-border-sm"></div>
                            </h2>
                
                            <!-- Minimo -->

                            <small class="fw-bold text-center text-white mt-2">
                                Mínimo 20 para participar
                            </small>

                        </div>
                    </div>


                </div>

            </div>
        </div>
    </section>

    <!-- COMPRA -->
    <section id="compra" class="py-2 bg-white border-top">
        <div class="container">
            <h2 class="text-center fw-bold mb-3 mt-3">🎟️ Paquetes</h2>

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
                                    <small class="text-muted py-1 px-3 d-flex justify-content-center align-items-center">🚧 Mínimo 20 para participar</small>     
                                </p>
                        </div>

                        <div class="card-body bg-light">

                            <div class="row g-4" id="paquetesNumeros">

                            <!-- 3 -->
                            <div class="col-6 col-md-4">
                            <input type="radio" class="btn-check paquete-radio" name="paqueteNumeros" id="paq3" value="20">
                            <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center paquete-card" for="paq3">
                            <div class="fw-bold">20</div>
                            <div class="fs-5 fw-bold">$18.000</div>
                            </label>
                            </div>


                            <!-- 4 -->
                            <div class="col-6 col-md-4">
                            <input type="radio" class="btn-check paquete-radio" name="paqueteNumeros" id="paq4" value="30">
                            <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center paquete-card" for="paq4">
                            <div class="fw-bold">30</div>
                            <div class="fs-5 fw-bold">$27.000</div>
                            </label>
                            </div>


                            <!-- 5 -->
                            <div class="col-6 col-md-4">
                            <input type="radio" class="btn-check paquete-radio" name="paqueteNumeros" id="paq5" value="50">
                            <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center paquete-card popular" for="paq5">

                            <span class="badge-paquete">🎯 Popular</span>

                            <div class="fw-bold">50</div>
                            <div class="fs-5 fw-bold">$45.000</div>

                            </label>
                            </div>


                            <!-- 7 -->
                            <div class="col-6 col-md-4">
                            <input type="radio" class="btn-check paquete-radio" name="paqueteNumeros" id="paq7" value="70">
                            <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center paquete-card recomendado" for="paq7">

                            <span class="badge-paquete">⭐ Recomendado</span>

                            <div class="fw-bold">70</div>
                            <div class="fs-5 fw-bold">$63.000</div>

                            </label>
                            </div>


                            <!-- 10 -->
                            <div class="col-6 col-md-4">
                            <input type="radio" class="btn-check paquete-radio" name="paqueteNumeros" id="paq10" value="100">
                            <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center paquete-card mas-vendido" for="paq10">

                            <span class="badge-paquete">🔥 Más vendido</span>

                            <div class="fw-bold">100</div>
                            <div class="fs-5 fw-bold">$90.000</div>

                            </label>
                            </div>


                            <!-- 20 -->
                            <div class="col-6 col-md-4">
                            <input type="radio" class="btn-check paquete-radio" name="paqueteNumeros" id="paq20" value="200">
                            <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center paquete-card mejor-valor" for="paq20">

                            <span class="badge-paquete">💰 VIP</span>

                            <div class="fw-bold">200</div>
                            <div class="fs-5 fw-bold">$180.000</div>

                            </label>
                            </div>



                            <!-- CUSTOM -->
                            <div class="col-6 col-md-4">

                            <input type="radio"
                            class="btn-check paquete-radio"
                            name="paqueteNumeros"
                            id="paqCustom"
                            value="custom">

                            <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center paquete-card custom"
                            for="paqCustom">

                            <span class="badge-paquete">🎯 Personalizado</span>

                            <div class="fw-bold">Otro</div>

                            </label>

                            <input
                            type="tel"
                            id="cantidadManual"
                            class="form-control form-control-sm text-center mt-1"
                            min="3"
                            placeholder="#"
                            style="display:none;"
                            >

                            </div>

                            </div>

                            <div class="alert alert-warning text-center small fw-bold mt-3">
                                🎯 Más números = más oportunidades de ganar
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
                                <li class="list-group-item d-flex justify-content-between text-success d-none" id="lineaDescuentoDesktop">
                                    <span>Descuento APF15 (15%)</span>
                                    <strong id="montoDescuentoDesktop">-$0</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between fw-bold">
                                    <span>Total</span>
                                    <strong class="text-success" id="totalDineroDesktop">$0</strong>
                                </li>
                            </ul>

                            <div class="alert alert-warning small text-center fw-bold">
                                🔥 Estás a un paso de participar
                            </div>

                            <button class="btn btn-dark w-100 py-3 fw-bold" onclick="abrirCheckout()"
                                id="btnPagarDesktop" disabled>
                                Pagar ahora →
                            </button>

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
    
      <section class="texto-ganadores d-none">
        <div  >
            <div id="prizesCarousel" class="carousel slide" data-bs-ride="carousel">
                <h2 class="title-ganadores text-center title-premios">¡Últimos ganadores! 🥳</h2>
            </div>
        </div>
    </section>

    <section class="container-ganadores d-none">
        <div id="exampleCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item">
                    <img src="" class="d-block w-100" alt="Ganador 1">
                </div>
                <div class="carousel-item">
                    <img src="" class="d-block w-100" alt="Ganador 1">
                </div>
                <div class="carousel-item active">
                    <img src="" class="d-block w-100" alt="Ganador 1">
                </div>
                <div class="carousel-item">
                    <img src="" class="d-block w-100" alt="Ganador 1">
                </div>
                <div class="carousel-item">
                    <img src="" class="d-block w-100" alt="Ganador 1">
                </div>
                <div class="carousel-item">
                    <img src="" class="d-block w-100" alt="Ganador 1">
                </div>

            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#exampleCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#exampleCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
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
                        <a href="https://wa.me/573202925348?text=Hola%20" target="_blank" class="btn btn-outline-success btn-sm rounded-circle">
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
                            <a href="https://wa.me/573202925348?text=Hola%20" class="text-secondary text-decoration-none">Soporte</a>
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
                        <i class="ti ti-phone me-2"></i> (+57) 320 292 5348
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

            <!-- Redes en Mobile -->
            <div class="d-flex d-md-none justify-content-center gap-3 mb-3">
                <a href="https://www.instagram.com/angelica_paez00?igsh=MTNvdGJmNnpxd2xxaw%3D%3D&utm_source=qr" class="btn btn-outline-light rounded-circle">
                    <i class="ti ti-brand-instagram"></i>
                </a>
                <a href="https://wa.me/573202925348?text=Hola%20" class="btn btn-outline-success rounded-circle">
                    <i class="ti ti-brand-whatsapp"></i>
                </a>
                <a href="" class="d-none btn btn-outline-primary rounded-circle">
                    <i class="ti ti-brand-facebook"></i>
                </a>
            </div>

            <!-- Copyright -->
            <div class="text-center small text-secondary pb-2">
                © <?= date('Y'); ?> AP Fenix · Todos los derechos reservados <br>
            Desarrollado con ❤️ por 
            <strong class="text-warning">
              <a 
                href="https://wa.me/573245894268?text=Hola%20vi%20la%20p%C3%A1gina%20de%20Ap%20Fenix%20y%20quiero%20obtener%20m%C3%A1s%20informaci%C3%B3n%20sobre%20el%20sistema%20de%20rifas"
                target="_blank"
                rel="noopener"
                class="text-warning"
              >
                Cristian Ceballos <i class="ti ti-brand-whatsapp"></i><i class="ti ti-link"></i>
              </a>
            </strong>
            </div>

        </div>
    </footer>


    <!-- MOBILE CART -->
    <div class="mobile-cart-bar d-lg-none" id="mobileCart" style="display:none!important">
        <div class="d-flex align-items-center gap-3">
            <div class="mobile-cart-info">
                <span class="mobile-cart-label">Total a Pagar</span>
                <div>
                    <span class="mobile-cart-price" id="lblTotalMobile">$0</span>                    
                </div>
                <div>
                    <span class="mobile-cart-count"><span id="lblCantidadMobile">0</span> Núms</span>
                </div>
            </div>
        </div>
        <button class="btn btn-warning rounded-pill px-4 fw-bold shadow" onclick="abrirCheckout()" id="btnPagarMobile">
            PAGAR 🔥
        </button>
    </div>

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
    </script>
    <script src="assets/js/frontend-v3.js?v=cupon2"></script>
    <script src="assets/js/buscarTickets.js"></script>
    


    <script>
    document.addEventListener('DOMContentLoaded', function() {
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