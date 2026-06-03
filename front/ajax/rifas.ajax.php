<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../../config/config.php";
require_once "../../controllers/apiRequest.controller.php"; 
require_once "../../controllers/rifas.controller.php";

Auth::requireLogin();

$action = $_POST['action'] ?? '';
$result = ['success' => false, 'message' => 'Acción no válida'];

$adminOnly = ['crear', 'actualizar', 'eliminar'];

try {
    if (in_array($action, $adminOnly, true) && !Auth::isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit;
    }

    switch ($action) {
        // CASO NUEVO: Reutiliza la lógica existente forzando el estado
        case 'obtener_activas': 
            $_POST['status'] = 1; // Truco: Forzamos filtro de activos
            // No necesitamos 'search', así que lo aseguramos vacío o lo dejamos como venga
            $result = RifasController::obtenerRifas(); 
            break;

        // Casos existentes...
        case 'obtener': 
            $result = RifasController::obtenerRifas(); 
            break;
        case 'crear':   
            $result = RifasController::crearRifa($_POST); 
            break;
        case 'actualizar': 
            $result = RifasController::actualizarRifa($_POST); 
            break;
        case 'eliminar': 
            $result = RifasController::eliminarRifa($_POST); 
            break;
    }
} catch (Throwable $e) { 
    $result = ['success' => false, 'message' => $e->getMessage()]; 
}

echo json_encode($result);
?>