<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once "../../config/config.php";
require_once "../../controllers/apiRequest.controller.php"; 
require_once "../../controllers/dashboard.controller.php";

const ALLOWED_ACTIONS = [
    'obtener_dashboard' => ['DashboardController', 'obtenerDashboard', []], // POST params handled inside
    'obtener_rifas'     => ['DashboardController', 'listarRifas', []]
];

try {
    $action = $_POST['action'] ?? '';
    
    if (!isset(ALLOWED_ACTIONS[$action])) throw new Exception('Acción no válida');
    
    [$class, $method, $paramsConfig] = ALLOWED_ACTIONS[$action];
    
    if (method_exists($class, $method)) {
        // Pasamos POST implícitamente o explícitamente según se requiera, 
        // aquí el controlador lee $_POST directamente para simplificar.
        echo json_encode(call_user_func([$class, $method]));
    } else {
        throw new Exception("Método no encontrado");
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}