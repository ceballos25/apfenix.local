<?php
require_once "../config/config.php";
if (!Auth::isAdmin()) {
    Auth::redirectToDashboard();
}
$page_title = "Gestión de Vendedores";
include_once ROOT_PATH . "/includes/head.php";
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full">
    <?php include_once ROOT_PATH . "/includes/sidebar.php" ?>

    <div class="body-wrapper">
        <?php include_once ROOT_PATH . "/includes/header.php" ?>

        <div class="body-wrapper-inner">
            <div class="container-fluid">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="ti ti-user-star me-1"></i>Vendedores</h2>
                        <small class="text-muted">Crear y administrar vendedores con metas diarias</small>
                    </div>
                    <button class="btn btn-primary" onclick="abrirModalVendedor()">
                        <i class="ti ti-plus"></i> Nuevo Vendedor
                    </button>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Nombre</th>
                                        <th>Usuario</th>
                                        <th>Meta</th>
                                        <th>Estado</th>
                                        <th width="120">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyTablaVendedores">
                                    <tr><td colspan="5" class="text-center py-5">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal Vendedor -->
<div class="modal fade" id="modalVendedor" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVendedorTitle">Nuevo Vendedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formVendedor">
                    <input type="hidden" id="vendedorId" name="id_admin">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre completo</label>
                        <input type="text" class="form-control" id="name_admin" name="name_admin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Usuario (login)</label>
                        <input type="text" class="form-control" id="email_admin" name="email_admin" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contraseña <small class="text-muted" id="passHint">(obligatoria)</small></label>
                        <input type="password" class="form-control" id="password_admin" name="password_admin" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Estado</label>
                        <select class="form-select" id="status_admin" name="status_admin">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de meta diaria</label>
                        <select class="form-select" id="goal_type_admin" name="goal_type_admin">
                            <option value="ventas">Cantidad de ventas</option>
                            <option value="numeros">Cantidad de números vendidos</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Valor de meta diaria</label>
                        <input type="number" class="form-control" id="goal_value_admin" name="goal_value_admin" min="1" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarVendedor()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="' . ASSETS_URL . '/js/vendedores.js"></script>';
include_once ROOT_PATH . "/includes/footer.php";
?>
