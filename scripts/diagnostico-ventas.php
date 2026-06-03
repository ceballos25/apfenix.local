<?php
/**
 * Diagnóstico rápido de ventas / números vendidos / informe.
 * Uso en servidor: php scripts/diagnostico-ventas.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/controllers/apiRequest.controller.php';
require_once ROOT_PATH . '/controllers/informeGerencial.controller.php';
require_once ROOT_PATH . '/controllers/ventas.controller.php';
require_once ROOT_PATH . '/controllers/numeros.controller.php';
require_once ROOT_PATH . '/controllers/vendedor.controller.php';

$fecha = date('Y-m-d');
echo "=== Diagnóstico ventas — {$fecha} " . date('H:i:s') . " ===\n";
echo 'API_BASE: ' . API_BASE . "\n";
echo 'TIMEZONE: ' . date_default_timezone_get() . "\n\n";

// 1. API responde
$ping = ApiRequest::get('raffles', ['startAt' => 0, 'endAt' => 1, 'select' => 'id_raffle']);
echo '[1] API ping: ' . (ApiRequest::isSuccess($ping) ? 'OK' : 'FALLO — ' . json_encode($ping->results ?? '')) . "\n";

// 2. Total ventas en tabla sales
$sales = ApiRequest::get('sales', ['select' => 'id_sale,id_admin_sale,date_created_sale', 'startAt' => 0, 'endAt' => 50000]);
$totalSales = 0;
$conAdmin = 0;
$sinAdmin = 0;
$hoy = 0;
$hoyConAdmin = 0;
if (ApiRequest::isSuccess($sales) && !empty($sales->results)) {
    $rows = is_array($sales->results) ? $sales->results : [$sales->results];
    $totalSales = count($rows);
    foreach ($rows as $s) {
        if (!empty($s->id_admin_sale)) {
            $conAdmin++;
        } else {
            $sinAdmin++;
        }
        if (substr($s->date_created_sale ?? '', 0, 10) === $fecha) {
            $hoy++;
            if (!empty($s->id_admin_sale)) {
                $hoyConAdmin++;
            }
        }
    }
}
echo "[2] Ventas totales (API sales): {$totalSales}\n";
echo "    Con id_admin_sale: {$conAdmin} | Sin vendedor (NULL): {$sinAdmin}\n";
echo "    Hoy ({$fecha}): {$hoy} | Hoy con vendedor: {$hoyConAdmin}\n";

// 3. Ventas vía relations SIN join admins (como ventas.php corregido)
$rel = ApiRequest::get('relations', [
    'rel' => 'sales,customers,raffles',
    'type' => 'sale,customer,raffle',
    'select' => 'id_sale',
    'startAt' => 0,
    'endAt' => 50000,
]);
$relCount = ApiRequest::isSuccess($rel) && !empty($rel->results)
    ? count(is_array($rel->results) ? $rel->results : [$rel->results])
    : 0;
echo "[3] Ventas relations (sin admins): {$relCount}";
if (!ApiRequest::isSuccess($rel)) {
    echo ' — ERROR: ' . json_encode($rel->results ?? '');
}
echo "\n";

// 4. Ventas relations CON join admins (comportamiento antiguo)
$relOld = ApiRequest::get('relations', [
    'rel' => 'sales,customers,raffles,admins',
    'type' => 'sale,customer,raffle,admin',
    'select' => 'id_sale',
    'startAt' => 0,
    'endAt' => 50000,
]);
$relOldCount = ApiRequest::isSuccess($relOld) && !empty($relOld->results)
    ? count(is_array($relOld->results) ? $relOld->results : [$relOld->results])
    : 0;
echo "[4] Ventas relations (con admins — antiguo): {$relOldCount}";
if ($relOldCount < $relCount) {
    echo " ⚠ pierde " . ($relCount - $relOldCount) . " ventas sin vendedor asignado";
}
echo "\n";

// 5. Números vendidos
$_POST = ['search' => '', 'id_raffle' => ''];
$numeros = NumerosController::obtenerNumerosVendidos();
$numCount = count($numeros['data'] ?? []);
echo "[5] Números vendidos (NumerosController): {$numCount}\n";

// 6. Vendedores activos
$vendedores = InformeGerencialController::obtenerVendedoresActivos();
echo '[6] Vendedores activos: ' . count($vendedores) . "\n";

// 7. Informe del día
$datos = InformeGerencialController::construirDatosInforme('cierre');
echo '[7] Informe cierre — ventas: ' . $datos['totales']['ventas_count'];
echo ' | números: ' . $datos['totales']['numeros_count'];
echo ' | dinero: $' . number_format($datos['totales']['dinero'], 0, ',', '.') . "\n";

// 8. Prueba filtro vendedor (id 2 si existe, sino el primero)
$testId = !empty($vendedores[0]->id_admin) ? (int) $vendedores[0]->id_admin : 2;
$bad = ApiRequest::get('relations', [
    'rel' => 'sales,customers,raffles',
    'type' => 'sale,customer,raffle',
    'select' => 'id_sale',
    'linkTo' => 'id_admin_sale',
    'equalTo' => $testId,
    'startAt' => 0,
    'endAt' => 5,
]);
$good = ApiRequest::get('relations', [
    'rel' => 'sales,customers,raffles',
    'type' => 'sale,customer,raffle',
    'select' => 'id_sale',
    'linkTo' => 'id_admin_sale',
    'search' => (string) $testId,
    'startAt' => 0,
    'endAt' => 5000,
]);
echo "[8] Filtro vendedor #{$testId}: equalTo=" . ($bad->status ?? '?');
echo ' (count=' . (is_array($bad->results ?? null) ? count($bad->results) : 0) . ')';
echo ' | search=' . ($good->status ?? '?');
echo ' (count=' . (is_array($good->results ?? null) ? count($good->results) : 0) . ")\n";

echo "\n--- Recomendaciones ---\n";
if ($sinAdmin > 0) {
    echo "- Hay {$sinAdmin} ventas sin id_admin_sale (web/transferencias). No aparecen en informe por vendedor.\n";
}
if ($hoy > 0 && $hoyConAdmin === 0) {
    echo "- Hoy hay ventas pero ninguna tiene vendedor asignado. Verificar deploy de ventas.controller (id_admin_sale al crear).\n";
}
if (($bad->status ?? 0) !== 200 && ($good->status ?? 0) === 200) {
    echo "- La API no acepta equalTo en id_admin_sale; usar search (ya corregido en vendedor.controller.php).\n";
}
if ($relOldCount < $relCount) {
    echo "- Quitar join con admins en listado de ventas (ya corregido en ventas.controller.php).\n";
}
echo "=== Fin ===\n";
