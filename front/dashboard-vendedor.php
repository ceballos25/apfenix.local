<?php
require_once "../config/config.php";
if (!Auth::isVendedor()) {
    header('Location: ' . BASE_URL . '/front/dashboard.php');
    exit;
}
$page_title = "Mi Dashboard";
include_once ROOT_PATH . "/includes/head.php";
?>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full">
    <?php include_once ROOT_PATH . "/includes/sidebar.php"; ?>

    <div class="body-wrapper">
        <?php include_once ROOT_PATH . "/includes/header.php"; ?>

        <div class="body-wrapper-inner">
            <div class="container-fluid" style="padding-top: 20px;">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="mb-0 fw-bold text-dark">
                            <i class="ti ti-chart-bar text-primary me-2"></i>
                            Hola, <span id="nombreVendedor">Vendedor</span>
                        </h3>
                        <small class="text-muted">Resumen del día — <span id="fechaHoy"></span></small>
                    </div>
                    <a href="vender.php" class="btn btn-primary">
                        <i class="ti ti-shopping-cart me-1"></i> Nueva Venta
                    </a>
                </div>

                <!-- Barra de progreso meta diaria -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0 fw-bold">Meta diaria</h5>
                            <span class="badge bg-primary-subtle text-primary fs-6" id="badgePorcentaje">0%</span>
                        </div>
                        <p class="text-muted small mb-3" id="textoMeta">
                            Meta: 0 ventas — Llevas: 0
                        </p>
                        <div class="progress" style="height: 28px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                 id="barraProgreso" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span class="fw-bold" id="textoBarra">0%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPIs del día -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="bg-primary-subtle text-primary rounded-circle p-3 me-3">
                                    <i class="ti ti-receipt fs-2"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small text-uppercase fw-bold mb-1">Ventas Hoy</h6>
                                    <h3 class="mb-0 fw-bolder" id="kpiVentasHoy">0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="bg-success-subtle text-success rounded-circle p-3 me-3">
                                    <i class="ti ti-ticket fs-2"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small text-uppercase fw-bold mb-1">Números Hoy</h6>
                                    <h3 class="mb-0 fw-bolder" id="kpiNumerosHoy">0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="bg-warning-subtle text-warning rounded-circle p-3 me-3">
                                    <i class="ti ti-currency-dollar fs-2"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Hoy</h6>
                                    <h3 class="mb-0 fw-bolder" id="kpiDineroHoy">$0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimas ventas del día -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Mis ventas de hoy</h5>
                        <a href="ventas.php" class="btn btn-sm btn-outline-primary">Ver historial completo</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Código</th>
                                        <th>Cliente</th>
                                        <th>Rifa</th>
                                        <th>Nums</th>
                                        <th>Total</th>
                                        <th>Hora</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyVentasHoy">
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="' . ASSETS_URL . '/js/dashboard-vendedor.js"></script>';
include_once ROOT_PATH . "/includes/footer.php";
?>
