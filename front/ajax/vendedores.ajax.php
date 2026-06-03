<?php
/**
 * AJAX Handler — Vendedores (solo administrador)
 */
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once "../../config/config.php";
require_once "../../controllers/apiRequest.controller.php";
require_once "../../controllers/vendedores.controller.php";

Auth::requireAdmin();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'obtener':
            echo json_encode(VendedoresController::listar());
            break;
        case 'obtener_uno':
            echo json_encode(VendedoresController::obtener($_POST['id_admin'] ?? 0));
            break;
        case 'crear':
            echo json_encode(VendedoresController::crear($_POST));
            break;
        case 'actualizar':
            echo json_encode(VendedoresController::actualizar($_POST));
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
