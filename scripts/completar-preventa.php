<?php
/**
 * Completa extras de preventa en ventas que quedaron cortas.
 * Uso: php scripts/completar-preventa.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/apiRequest.controller.php';
require_once __DIR__ . '/../includes/promo2x1-garantia.php';

$report = Promo2x1Garantia::completarVentasPreventa();

echo "Revisadas: {$report['revisadas']}\n";
echo "Ya completas: {$report['completas']}\n";
echo "Corregidas: " . count($report['corregidas']) . "\n";
echo "Errores: " . count($report['errores']) . "\n\n";

foreach ($report['corregidas'] as $f) {
    echo sprintf(
        "OK %s | %s | %s | pagó %d tenía %d ahora %d | %s\n",
        $f['code'],
        $f['nombre'],
        $f['telefono'],
        $f['pagados'],
        $f['tenia'],
        $f['necesita'],
        $f['message']
    );
}

foreach ($report['errores'] as $f) {
    echo sprintf(
        "ERROR %s | %s | %s | pagó %d tenía %d necesita %d | %s\n",
        $f['code'],
        $f['nombre'],
        $f['telefono'],
        $f['pagados'],
        $f['tenia'],
        $f['necesita'],
        $f['message']
    );
}
