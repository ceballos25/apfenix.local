<?php
require_once "../config/config.php";
require_once "../includes/coupon.php";
require_once "../includes/dinamica.php";
require_once "../includes/promo2x1.php";
$page_title = "Nueva Venta";
$couponActive = CouponHelper::isActive();
$promo2x1Active = Promo2x1Helper::isActive();
$extra_css = '
<link rel="stylesheet" href="' . ASSETS_URL . '/css/paquetes.css?v=5" />
<link rel="stylesheet" href="' . ASSETS_URL . '/css/vender.css?v=10" />
';
include_once ROOT_PATH . "/includes/head.php";
?>

<div class="page-wrapper vm-page" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
    <?php include_once ROOT_PATH . "/includes/sidebar.php" ?>

    <div class="body-wrapper bg-light min-vh-100">
        <?php include_once ROOT_PATH . "/includes/header.php" ?>

        <div class="body-wrapper-inner">
            <div class="container-xxl p-2 p-lg-4 pb-5 mb-5">

                <div class="vm-head">
                    <div>
                        <h4>Nueva venta</h4>
                        <p>Cliente, números y cobro en un solo paso</p>
                    </div>
                    <button type="button" class="vm-reset" onclick="location.reload()" title="Reiniciar">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>

                <?php if ($couponActive): ?>
                <div class="alert alert-warning border-warning shadow-sm mb-3 py-2">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span class="fw-bold">🎟️ Cupón <strong>APF15</strong> activo: 15% OFF aplicado automáticamente</span>
                        <span class="badge bg-danger" id="cuponCountdownVender">--:--:--</span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row g-3">

                    <div class="col-lg-8">

                        <div class="card vm-card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0 fw-bold text-dark"><span class="vm-step">1</span>Cliente</h6>
                            </div>
                            <div class="card-body p-3 p-lg-4">

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Buscar existente</label>
                                    <select id="buscadorCliente" class="form-control w-100"></select>
                                </div>

                                <form id="formClienteVenta">
                                    <input type="hidden" id="idCliente" name="id_customer">

                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label class="small fw-bold mb-1">Celular <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" id="celularCliente" required>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="small fw-bold mb-1">Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control text-capitalize" id="nombreCliente" required>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="small fw-bold mb-1">Apellido <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control text-capitalize" id="apellidoCliente" required>
                                        </div>

                                        <div class="col-12 col-md-4">
                                            <label class="small fw-bold mb-1">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control text-lowercase" id="emailCliente">
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="small fw-bold mb-1">Depto <span class="text-danger">*</span></label>
                                            <select class="form-select select2-ubicacion" id="departamento"></select>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <label class="small fw-bold mb-1">Ciudad <span class="text-danger">*</span></label>
                                            <select class="form-select select2-ubicacion" id="ciudad" disabled></select>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold d-none" id="btnLimpiarCliente" onclick="resetClienteForm()">
                                            <i class="ti ti-eraser me-1"></i> Limpiar campos
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card vm-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-dark"><span class="vm-step">2</span>Números</h6>
                            </div>

                            <div class="card-body p-3 p-lg-4">

                                <div class="mb-3">
                                    <select class="form-select fw-bold" id="selectRifa"></select>
                                </div>

                                <div class="row g-2 g-md-3 cr-paquetes-grid" id="paquetesNumeros">
                                    <?= DinamicaHelper::renderPackageCards(false, true) ?>
                                </div>
                                <?php
                                $priceHints = DinamicaHelper::renderPriceHints();
                                if ($priceHints !== ''):
                                ?>
                                <div class="cr-paquetes-hint">
                                    <?= $priceHints ?>
                                </div>
                                <?php endif; ?>

                            </div>
                        </div>

                        <div class="vm-cobro vm-cobro--page d-lg-none mt-3">
                            <div class="vm-cobro__head">
                                <div>
                                    <h6>Cobro</h6>
                                    <span class="vm-cobro__rifa" id="lblRifaResumenMob">Sin rifa</span>
                                </div>
                                <?php if ($promo2x1Active): ?>
                                <span class="vm-count promo2x1-countdown">--:--:--</span>
                                <?php endif; ?>
                            </div>
                            <div class="vm-cobro__body">
                                <div class="vm-row">
                                    <span>Cliente</span>
                                    <strong id="lblClienteResumenMob">Sin datos</strong>
                                </div>
                                <div class="vm-row">
                                    <span>Números</span>
                                    <strong id="lblCantidadMobileBadge">0</strong>
                                </div>
                                <div class="vm-row vm-row--promo d-none" id="lineaPreventaVenderMob">
                                    <span>Preventa</span>
                                    <strong id="lblPreventaVenderMob">—</strong>
                                </div>
                                <div class="d-none vm-row" id="lineaDescuentoVenderMob">
                                    <span>Descuento APF15</span>
                                    <strong id="montoDescuentoVenderMob">-$0</strong>
                                </div>
                                <div class="vm-total">
                                    <span>Total</span>
                                    <strong id="lblTotalMobile">$0</strong>
                                </div>
                            </div>
                            <div class="vm-cobro__pay">
                                <p class="vm-pay-label">Cómo pagó</p>
                                <div class="vm-pay">
                                    <input type="radio" class="btn-check" name="metodoPagoMobile" id="pagoEfecMob" value="Efectivo">
                                    <label for="pagoEfecMob"><i class="ti ti-cash"></i>Efectivo</label>
                                    <input type="radio" class="btn-check" name="metodoPagoMobile" id="pagoTransMob" value="Transferencia">
                                    <label for="pagoTransMob"><i class="ti ti-building-bank"></i>Transferencia</label>
                                </div>
                                <button type="button" class="btn btn-success vm-confirm" id="btnCompletarVentaMob" onclick="procesarVentaMobile()">Cobrar</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 d-none d-lg-block">
                        <div class="vm-cobro sticky-top" style="top: 90px;">
                            <div class="vm-cobro__head">
                                <div>
                                    <h6>Cobro</h6>
                                    <span class="vm-cobro__rifa" id="lblRifaResumen">Sin rifa</span>
                                </div>
                                <?php if ($promo2x1Active): ?>
                                <span class="vm-count promo2x1-countdown">--:--:--</span>
                                <?php endif; ?>
                            </div>
                            <div class="vm-cobro__body">
                                <div class="vm-row">
                                    <span>Cliente</span>
                                    <strong id="lblClienteResumen">Sin datos</strong>
                                </div>
                                <div class="vm-row">
                                    <span>Números</span>
                                    <strong id="lblCantidadDesktop">0</strong>
                                </div>
                                <div class="vm-row vm-row--promo d-none" id="lineaPreventaVenderDesk">
                                    <span>Preventa</span>
                                    <strong id="lblPreventaVenderDesk">—</strong>
                                </div>
                                <div class="vm-row vm-row--promo d-none" id="lineaVolumenVenderDesk">
                                    <span>Desde 25 nums</span>
                                    <strong id="lblVolumenVenderDesk">$8.000 c/u</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-end d-none vm-row" id="lineaDescuentoVenderDesk">
                                    <span class="text-success">Descuento APF15</span>
                                    <strong class="text-success" id="montoDescuentoVenderDesk">-$0</strong>
                                </div>
                                <div class="vm-total">
                                    <span>Total</span>
                                    <strong id="lblTotalDesktop">$0</strong>
                                </div>
                            </div>
                            <div class="vm-cobro__pay">
                                <p class="vm-pay-label">Cómo pagó</p>
                                <div class="vm-pay">
                                    <input type="radio" class="btn-check" name="metodoPago" id="pagoEfecDesk" value="Efectivo">
                                    <label for="pagoEfecDesk"><i class="ti ti-cash"></i>Efectivo</label>
                                    <input type="radio" class="btn-check" name="metodoPago" id="pagoTransDesk" value="Transferencia">
                                    <label for="pagoTransDesk"><i class="ti ti-building-bank"></i>Transferencia</label>
                                </div>
                                <button type="button" class="btn btn-success vm-confirm" id="btnCompletarVenta" onclick="procesarVenta()">Confirmar venta</button>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade vm-modal" id="modalConfirmarVenta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar cobro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-2">
                <div class="vm-ticket">
                    <div class="vm-row">
                        <span>Cliente</span>
                        <strong id="modalCliente">—</strong>
                    </div>
                    <div class="vm-row">
                        <span>Números</span>
                        <strong id="modalCantidad">—</strong>
                    </div>
                    <div class="vm-row">
                        <span>Pago</span>
                        <strong id="modalMetodo">—</strong>
                    </div>
                    <div class="vm-row d-none" id="modalLineaPreventa">
                        <span>Preventa</span>
                        <strong id="modalPreventa">—</strong>
                    </div>
                    <div class="vm-ticket-total">
                        <span>A cobrar</span>
                        <strong id="modalTotal">$0</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost flex-fill" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success flex-fill" id="btnSiCobrar">Sí, cobrar</button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<link href="' . ASSETS_URL . '/libs/select2/css/select2.min.css" rel="stylesheet" />
<link href="' . ASSETS_URL . '/libs/select2/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="' . ASSETS_URL . '/libs/select2/js/select2.min.js"></script>
<script src="' . ASSETS_URL . '/js/departamentos-ciudades.js"></script>
<script>
window.CUPON_AP_FENIX = ' . json_encode([
    'activo' => $couponActive,
    'codigo' => CouponHelper::CODE,
    'descuento' => CouponHelper::DISCOUNT_PERCENT,
    'expira' => $couponActive ? CouponHelper::getExpiresForJs() : null,
], JSON_UNESCAPED_UNICODE) . ';
window.DINAMICA = ' . json_encode(DinamicaHelper::frontendConfig(), JSON_UNESCAPED_UNICODE) . ';
window.PROMO_2X1 = ' . json_encode([
    'activo' => $promo2x1Active,
    'minimo' => Promo2x1Helper::MIN_QTY,
    'expira' => DinamicaHelper::getExpiresForJs(),
], JSON_UNESCAPED_UNICODE) . ';
</script>
<script src="' . ASSETS_URL . '/js/promo-2x1.js?v=38"></script>
<script src="' . ASSETS_URL . '/js/vender.js?v=40"></script>
';
include_once ROOT_PATH . "/includes/footer.php";
?>
