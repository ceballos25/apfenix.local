<?php
/**
 * AJAX Handler - Ventas
 * Maneja ventas y reportes de números vendidos
 */

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once "../../config/config.php";
require_once "../../controllers/apiRequest.controller.php"; 
require_once "../../controllers/ventas.controller.php";
require_once "../../controllers/numeros.controller.php"; // <--- IMPORTANTE: Incluir esto

const ALLOWED_ACTIONS = [
    // VentasController
    'obtener'             => ['VentasController', 'obtenerVentas', []],
    'obtener_rifas'       => ['VentasController', 'listarRifas', []],
    'crear_venta'         => ['VentasController', 'crearVenta', ['data' => '$_POST']],
    'obtener_por_codigo'  => ['VentasController', 'obtenerVentaPorCodigo', ['code_sale' => null]],
    'obtener_disponibles' => ['VentasController', 'obtenerTicketsDisponibles', ['id_raffle' => 0]],
    'detalle_venta'       => ['VentasController', 'obtenerDetalleVenta', ['id_sale' => null]],
    'obtener_por_celular' => ['VentasController', 'buscarTicketsPorCelular', ['phone_customer' => null]],    
    'numeros_vendidos'    => ['NumerosController', 'obtenerNumerosVendidos', []],
    'obtener_admins' => ['VentasController', 'obtenerAdmins', []],    
    'anular' => ['VentasController', 'anularVenta', ['id_sale' => null]]
];

try {
    $action = $_POST['action'] ?? '';
    
    if (!isset(ALLOWED_ACTIONS[$action])) throw new Exception('Acción no válida');
    
    [$class, $method, $paramsConfig] = ALLOWED_ACTIONS[$action];
    
    $args = [];
    foreach ($paramsConfig as $key => $default) {
        if ($default === '$_POST') $args[] = $_POST;
        else $args[] = $_POST[$key] ?? $default;
    }
    
    if (method_exists($class, $method)) {
        echo json_encode(call_user_func_array([$class, $method], $args));
    } else {
        throw new Exception("Método $method no encontrado en $class");
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}