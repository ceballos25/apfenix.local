<?php
/**
 * Diagnóstico promo 2×1 en el servidor (eliminar o proteger tras verificar).
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/promo2x1.php';

$ventasFile = ROOT_PATH . '/controllers/ventas.controller.php';
$backupFile = ROOT_PATH . '/controllers/paymentBackupsController.php';
$promoFile  = ROOT_PATH . '/includes/promo2x1.php';

echo json_encode([
    'promo_activa' => Promo2x1Helper::isActive(),
    '50_entregados' => Promo2x1Helper::quantityDelivered(50),
    'expira' => Promo2x1Helper::EXPIRES,
    'servidor_hora' => date('Y-m-d H:i:s T'),
    'archivos' => [
        'ventas.controller.php' => [
            'existe' => is_file($ventasFile),
            'tiene_promo' => is_file($ventasFile) && str_contains(file_get_contents($ventasFile), 'Promo2x1Helper'),
            'modificado' => is_file($ventasFile) ? date('Y-m-d H:i:s', filemtime($ventasFile)) : null,
        ],
        'paymentBackupsController.php' => [
            'existe' => is_file($backupFile),
            'tiene_promo' => is_file($backupFile) && str_contains(file_get_contents($backupFile), 'quantity_delivered'),
            'modificado' => is_file($backupFile) ? date('Y-m-d H:i:s', filemtime($backupFile)) : null,
        ],
        'promo2x1.php' => [
            'existe' => is_file($promoFile),
            'modificado' => is_file($promoFile) ? date('Y-m-d H:i:s', filemtime($promoFile)) : null,
        ],
    ],
    'opcache' => function_exists('opcache_get_status') ? (opcache_get_status(false)['opcache_enabled'] ?? null) : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
