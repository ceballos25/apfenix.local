<?php
/**
 * Webhook OpenPay – PRODUCCIÓN DEFINITIVA
 * Con idempotencia y logs mejorados
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/apiRequest.controller.php';
require_once __DIR__ . '/../controllers/paymentBackupsController.php';

// =====================================================
// LEER PAYLOAD
// =====================================================
$raw = file_get_contents('php://input');
file_put_contents(
    __DIR__ . '/openpay.log',
    '[' . date('Y-m-d H:i:s') . '] WEBHOOK RECIBIDO: ' . $raw . PHP_EOL,
    FILE_APPEND
);

$data = json_decode($raw, true);

// =====================================================
// VERIFICACIÓN OPENPAY
// =====================================================
if (isset($data['verification_code'])) {
    http_response_code(200);
    echo $data['verification_code'];
    exit;
}

// =====================================================
// VALIDACIÓN BÁSICA
// =====================================================
$type = $data['type'] ?? null;
$tx   = $data['transaction'] ?? null;

if (!$type || !$tx || empty($tx['order_id'])) {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ⚠️ Datos incompletos, ignorando...' . PHP_EOL,
        FILE_APPEND
    );
    http_response_code(200);
    exit;
}

// =====================================================
// BUSCAR RESPALDO
// =====================================================
$backup = PaymentBackupsController::obtenerPorCode($tx['order_id']);
if (!$backup) {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ⚠️ Respaldo no encontrado: ' . $tx['order_id'] . PHP_EOL,
        FILE_APPEND
    );
    http_response_code(200);
    exit;
}

// =====================================================
// IDEMPOTENCIA - CRÍTICO
// Solo procesar si está PENDIENTE (status = 1)
// =====================================================
if ((int)$backup['status_payment_backup'] !== 1) {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ⏭️ YA PROCESADO (status=' . 
        $backup['status_payment_backup'] . ') - Ignorando evento: ' . $type . PHP_EOL,
        FILE_APPEND
    );
    http_response_code(200);
    exit;
}

// =====================================================
// DECISIÓN POR TIPO DE EVENTO
// =====================================================

// ✅ EVENTOS QUE CREAN VENTA
$eventosAprobados = [
    'charge.succeeded',
    'order.completed',
    'order.payment.received'
];

// ❌ EVENTOS QUE CANCELAN / FALLAN
$eventosRechazados = [
    'charge.failed',
    'charge.cancelled',
    'charge.refunded',
    'charge.rescored.to.decline',
    'order.expired',
    'order.cancelled',
    'order.payment.cancelled'
];

if (in_array($type, $eventosAprobados, true)) {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ✅ PROCESANDO APROBACIÓN: ' . $type . ' - ' . $tx['order_id'] . PHP_EOL,
        FILE_APPEND
    );
    PaymentBackupsController::aprobarPago($backup, $tx);
    
} elseif (in_array($type, $eventosRechazados, true)) {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ❌ PROCESANDO RECHAZO: ' . $type . ' - ' . $tx['order_id'] . PHP_EOL,
        FILE_APPEND
    );
    PaymentBackupsController::rechazarPago($backup, $tx);
    
} else {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ℹ️ Evento ignorado: ' . $type . PHP_EOL,
        FILE_APPEND
    );
}

http_response_code(200);
echo 'OK';