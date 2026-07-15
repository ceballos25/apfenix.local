<?php
/**
 * Diagnóstico promo 2×1 en el servidor.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/promo2x1.php';

$ventasFile = ROOT_PATH . '/controllers/ventas.controller.php';
$backupFile = ROOT_PATH . '/controllers/paymentBackupsController.php';
$promoFile  = ROOT_PATH . '/includes/promo2x1.php';
$garantiaFile = ROOT_PATH . '/includes/promo2x1-garantia.php';
$webhookFile = ROOT_PATH . '/openpay/webhook.php';

$read = static fn(string $f): string => is_file($f) ? (string) file_get_contents($f) : '';

echo json_encode([
    'promo_activa' => Promo2x1Helper::isActive(),
    '50_entregados' => Promo2x1Helper::quantityDelivered(50),
    'expira' => Promo2x1Helper::EXPIRES,
    'servidor_hora' => date('Y-m-d H:i:s T'),
    'archivos' => [
        'ventas.controller.php' => [
            'existe' => is_file($ventasFile),
            'tiene_promo' => str_contains($read($ventasFile), 'Promo2x1Helper'),
            'modificado' => is_file($ventasFile) ? date('Y-m-d H:i:s', filemtime($ventasFile)) : null,
        ],
        'paymentBackupsController.php' => [
            'existe' => is_file($backupFile),
            'tiene_promo' => str_contains($read($backupFile), 'quantity_delivered'),
            'tiene_garantia' => str_contains($read($backupFile), 'Promo2x1Garantia'),
            'modificado' => is_file($backupFile) ? date('Y-m-d H:i:s', filemtime($backupFile)) : null,
        ],
        'promo2x1-garantia.php' => [
            'existe' => is_file($garantiaFile),
            'modificado' => is_file($garantiaFile) ? date('Y-m-d H:i:s', filemtime($garantiaFile)) : null,
        ],
        'webhook.php' => [
            'existe' => is_file($webhookFile),
            'tiene_garantia' => str_contains($read($webhookFile), 'Promo2x1Garantia'),
            'version' => preg_match('/VERSION:\s*(.+)/', $read($webhookFile), $m) ? trim($m[1]) : null,
            'modificado' => is_file($webhookFile) ? date('Y-m-d H:i:s', filemtime($webhookFile)) : null,
        ],
        'promo2x1.php' => [
            'existe' => is_file($promoFile),
            'modificado' => is_file($promoFile) ? date('Y-m-d H:i:s', filemtime($promoFile)) : null,
        ],
    ],
    'opcache' => function_exists('opcache_get_status') ? (opcache_get_status(false)['opcache_enabled'] ?? null) : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
