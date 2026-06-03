<?php
/**
 * AJAX Handler — Dashboard vendedor
 */
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once "../../config/config.php";
require_once "../../controllers/apiRequest.controller.php";
require_once "../../controllers/vendedor.controller.php";

Auth::requireLogin();

if (!Auth::isVendedor()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'obtener_dashboard':
            echo json_encode(VendedorController::obtenerDashboard());
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
