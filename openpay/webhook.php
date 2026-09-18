<?php
/**
 * Webhook OpenPay
 * VERSION: 2026-09-18-pse-extras
 */

ignore_user_abort(true);
@set_time_limit(120);

if (function_exists('opcache_reset')) {
    @opcache_reset();
}
foreach ([
    __DIR__ . '/../controllers/ventas.controller.php',
    __DIR__ . '/../controllers/paymentBackupsController.php',
    __DIR__ . '/../controllers/mail.controller.php',
    __DIR__ . '/../includes/promo2x1.php',
    __DIR__ . '/../includes/promo2x1-garantia.php',
    __DIR__ . '/../includes/preventa-qty.php',
    __DIR__ . '/../includes/dinamica.php',
    __DIR__ . '/../includes/pse-preventa-fix.php',
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
require_once __DIR__ . '/../includes/pse-preventa-fix.php';
require_once __DIR__ . '/../controllers/mail.controller.php';

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

    $orderId = (string) $tx['order_id'];
    $idSaleMail = 0;

    try {
        $fix = PsePreventaFix::completarPorCodigo($orderId);
        file_put_contents(
            __DIR__ . '/openpay.log',
            '[' . date('Y-m-d H:i:s') . '] PSE_PREVENTA: ' . json_encode($fix, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );
        $idSaleMail = (int) ($fix['id_sale'] ?? 0);
    } catch (Throwable $e) {
        file_put_contents(
            __DIR__ . '/openpay.log',
            '[' . date('Y-m-d H:i:s') . '] PSE_PREVENTA_ERROR: ' . $e->getMessage() . PHP_EOL,
            FILE_APPEND
        );
        try {
            $garantia = Promo2x1Garantia::asegurarPorCodigoVenta($orderId);
            $idSaleMail = (int) ($garantia['id_sale'] ?? 0);
            file_put_contents(
                __DIR__ . '/openpay.log',
                '[' . date('Y-m-d H:i:s') . '] PROMO_GARANTIA: ' . json_encode($garantia, JSON_UNESCAPED_UNICODE) . PHP_EOL,
                FILE_APPEND
            );
        } catch (Throwable $e2) {
            file_put_contents(
                __DIR__ . '/openpay.log',
                '[' . date('Y-m-d H:i:s') . '] PROMO_GARANTIA_ERROR: ' . $e2->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    http_response_code(200);
    echo 'OK';
    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    }

    if ($idSaleMail > 0) {
        try {
            $mailOk = MailController::enviarCorreoVenta($idSaleMail);
            file_put_contents(
                __DIR__ . '/openpay.log',
                '[' . date('Y-m-d H:i:s') . '] CORREO: ' . ($mailOk ? 'enviado' : 'fallo') . ' venta ' . $idSaleMail . PHP_EOL,
                FILE_APPEND
            );
        } catch (Throwable $e) {
            file_put_contents(
                __DIR__ . '/openpay.log',
                '[' . date('Y-m-d H:i:s') . '] CORREO_ERROR: ' . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }
    exit;

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