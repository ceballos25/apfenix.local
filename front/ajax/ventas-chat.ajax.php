<?php
require_once "../../config/config.php";
require_once "../../controllers/agentsController.php";

Auth::requireLogin();

const ALLOWED_ACTIONS = [
    'obtener'  => ['AgentsController', 'obtenerAgentes', []],
    'aprobar'  => ['AgentsController', 'aprobarAgente', ['agent' => '$_POST']],
    'rechazar' => ['AgentsController', 'rechazarAgente', ['agent' => '$_POST']],
];

try {
    $action = $_POST['action'] ?? '';

    if (!isset(ALLOWED_ACTIONS[$action])) {
        throw new Exception('Acción no válida');
    }

    [$class, $method, $paramsConfig] = ALLOWED_ACTIONS[$action];

    $args = [];
    foreach ($paramsConfig as $key => $default) {
        if ($default === '$_POST') {
            $args[] = $_POST;
        } else {
            $args[] = $_POST[$key] ?? $default;
        }
    }

    echo json_encode(call_user_func_array([$class, $method], $args));
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
