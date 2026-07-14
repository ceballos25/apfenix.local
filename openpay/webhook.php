<?php
/**
 * Webhook OpenPay ? PRODUCCI?N DEFINITIVA
 * Con idempotencia, logs y garant?a promo 2?1
 * VERSION: 2026-07-14-promo-garantia
 */

// LiteSpeed / OPcache: forzar recarga de PHP cr?tico
if (function_exists('opcache_reset')) {
    @opcache_reset();
}
foreach ([
    __DIR__ . '/../controllers/ventas.controller.php',
    __DIR__ . '/../controllers/paymentBackupsController.php',
    __DIR__ . '/../includes/promo2x1.php',
    __DIR__ . '/../includes/promo2x1-garantia.php',
] as $phpFile) {
    clearstatcache(true, $phpFile);
    if (function_exists('opcache_invalidate') && is_file($phpFile)) {
        @opcache_invalidate($phpFile, true);
    }
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/apiRequest.controller.php';
require_once __DIR__ . '/../controllers/paymentBackupsController.php';
require_once __DIR__ . '/../includes/promo2x1-garantia.php';

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
// VERIFICACI?0?7N OPENPAY
// =====================================================
if (isset($data['verification_code'])) {
    http_response_code(200);
    echo $data['verification_code'];
    exit;
}

// =====================================================
// VALIDACI?0?7N B?0?9SICA
// =====================================================
$type = $data['type'] ?? null;
$tx   = $data['transaction'] ?? null;

if (!$type || !$tx || empty($tx['order_id'])) {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ?7?2?1?5 Datos incompletos, ignorando...' . PHP_EOL,
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
        '[' . date('Y-m-d H:i:s') . '] ?7?2?1?5 Respaldo no encontrado: ' . $tx['order_id'] . PHP_EOL,
        FILE_APPEND
    );
    http_response_code(200);
    exit;
}

// =====================================================
// IDEMPOTENCIA - CR?0?1TICO
// Solo procesar si est?? PENDIENTE (status = 1)
// =====================================================
if ((int)$backup['status_payment_backup'] !== 1) {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ?7?1?1?5 YA PROCESADO (status=' . 
        $backup['status_payment_backup'] . ') - Ignorando evento: ' . $type . PHP_EOL,
        FILE_APPEND
    );
    http_response_code(200);
    exit;
}

// =====================================================
// DECISI?0?7N POR TIPO DE EVENTO
// =====================================================

// ?7?3 EVENTOS QUE CREAN VENTA
$eventosAprobados = [
    'charge.succeeded',
    'order.completed',
    'order.payment.received'
];

// ?7?4 EVENTOS QUE CANCELAN / FALLAN
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
        '[' . date('Y-m-d H:i:s') . '] PROCESANDO APROBACION: ' . $type . ' - ' . $tx['order_id'] . PHP_EOL,
        FILE_APPEND
    );
    PaymentBackupsController::aprobarPago($backup, $tx);

    // Red de seguridad: si crearVenta no aplic? 2?1, completar aqu?
    try {
        $garantia = Promo2x1Garantia::asegurarPorCodigoVenta((string) $tx['order_id']);
        file_put_contents(
            __DIR__ . '/openpay.log',
            '[' . date('Y-m-d H:i:s') . '] PROMO_GARANTIA: ' . json_encode($garantia, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );
    } catch (Throwable $e) {
        file_put_contents(
            __DIR__ . '/openpay.log',
            '[' . date('Y-m-d H:i:s') . '] PROMO_GARANTIA_ERROR: ' . $e->getMessage() . PHP_EOL,
            FILE_APPEND
        );
    }

} elseif (in_array($type, $eventosRechazados, true)) {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ?7?4 PROCESANDO RECHAZO: ' . $type . ' - ' . $tx['order_id'] . PHP_EOL,
        FILE_APPEND
    );
    PaymentBackupsController::rechazarPago($backup, $tx);
    
} else {
    file_put_contents(
        __DIR__ . '/openpay.log',
        '[' . date('Y-m-d H:i:s') . '] ?6?7?1?5 Evento ignorado: ' . $type . PHP_EOL,
        FILE_APPEND
    );
}

http_response_code(200);
echo 'OK';