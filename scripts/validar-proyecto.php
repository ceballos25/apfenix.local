<?php
/**
 * Validación de lógica del proyecto (solo lectura + PDF temporal, sin enviar correos).
 *
 * Uso: php scripts/validar-proyecto.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/controllers/apiRequest.controller.php';
require_once ROOT_PATH . '/controllers/informeGerencial.controller.php';
require_once ROOT_PATH . '/controllers/vendedores.controller.php';
require_once ROOT_PATH . '/includes/auth.php';

$passed = 0;
$failed = 0;
$warnings = 0;

echo "=== Validación Ap Fenix — " . date('Y-m-d H:i:s') . " ===\n\n";

// ── 1. Sintaxis PHP archivos críticos ──
$criticalFiles = [
    'config/config.php',
    'includes/auth.php',
    'controllers/informeGerencial.controller.php',
    'controllers/vendedores.controller.php',
    'controllers/ventas.controller.php',
    'controllers/vendedor.controller.php',
    'cron/enviar-informe-gerencial.php',
    'includes/templates/informe-gerencial-pdf.php',
];

foreach ($criticalFiles as $file) {
    $path = ROOT_PATH . '/' . $file;
    assertTest(
        "Sintaxis: {$file}",
        file_exists($path) && syntaxOk($path),
        'Archivo no encontrado o error de sintaxis'
    );
}

// ── 2. Configuración esencial ──
assertTest('API_BASE configurado', defined('API_BASE') && API_BASE !== '', 'Falta API_BASE');
assertTest('API_KEY configurado', defined('API_KEY') && API_KEY !== '', 'Falta API_KEY');
assertTest('SITE_NAME configurado', defined('SITE_NAME') && SITE_NAME !== '', 'Falta SITE_NAME');
assertTest(
    'Informe gerencial habilitado',
    defined('INFORME_GERENCIAL_ENABLED'),
    'Falta INFORME_GERENCIAL_ENABLED'
);
if (empty(CORREO_INFORME)) {
    warn('CORREO_INFORME vacío — el cron no enviará correos');
} else {
    pass('CORREO_INFORME configurado');
}

// ── 3. Conectividad API ──
$apiPing = ApiRequest::get('raffles', ['startAt' => 0, 'endAt' => 1, 'select' => 'id_raffle']);
assertTest('API responde', ApiRequest::isSuccess($apiPing), stringifyError($apiPing));

// ── 4. Vendedores activos ──
$vendedoresRes = VendedoresController::listar();
assertTest('Listar vendedores', $vendedoresRes['success'] === true, $vendedoresRes['message'] ?? 'Error');
$totalVendedores = count($vendedoresRes['data'] ?? []);
pass("Vendedores activos en sistema: {$totalVendedores}");
if ($totalVendedores < 50) {
    warn("Se esperaban al menos 50 vendedores para la prueba (actual: {$totalVendedores})");
}

// ── 5. Metas válidas ──
foreach ($vendedoresRes['data'] ?? [] as $v) {
    $gt = $v->goal_type_admin ?? '';
    $gv = (int)($v->goal_value_admin ?? 0);
    if (!in_array($gt, ['ventas', 'numeros'], true) || $gv <= 0) {
        fail("Meta inválida en vendedor #{$v->id_admin} ({$v->email_admin})");
    }
}
pass('Metas de vendedores válidas');

// ── 6. Informe cierre — coherencia de totales ──
$datosCierre = InformeGerencialController::construirDatosInforme('cierre');
assertTest(
    'Informe cierre: vendedores en filas',
    count($datosCierre['vendedores']) === $totalVendedores,
    'Filas=' . count($datosCierre['vendedores']) . " vs activos={$totalVendedores}"
);

$sumVentas  = 0;
$sumNumeros = 0;
$sumDinero  = 0.0;
foreach ($datosCierre['vendedores'] as $fila) {
    $sumVentas  += $fila['cantidad_ventas'];
    $sumNumeros += $fila['cantidad_numeros'];
    $sumDinero  += $fila['total_dinero'];

    $goalType  = $fila['goal_type'];
    $goalValue = (int)$fila['goal_value'];
    $progreso  = ($goalType === 'numeros') ? $fila['cantidad_numeros'] : $fila['cantidad_ventas'];
    $esperado  = ($goalValue > 0) ? min(100, round(($progreso / $goalValue) * 100, 1)) : 0;

    if ($fila['porcentaje'] !== $esperado) {
        fail("Porcentaje incorrecto en {$fila['email']}: {$fila['porcentaje']}% vs {$esperado}%");
    }
}
pass('Cálculo de cumplimiento por vendedor correcto');

assertTest(
    'Totales ventas coherentes',
    $datosCierre['totales']['ventas_count'] === $sumVentas,
    "total={$datosCierre['totales']['ventas_count']} suma={$sumVentas}"
);
assertTest(
    'Totales números coherentes',
    $datosCierre['totales']['numeros_count'] === $sumNumeros,
    "total={$datosCierre['totales']['numeros_count']} suma={$sumNumeros}"
);
assertTest(
    'Totales dinero coherentes',
    abs($datosCierre['totales']['dinero'] - $sumDinero) < 0.01,
    'Diferencia en suma de dinero'
);

// ── 7. Informe mediodía ≤ cierre ──
$datosMedio = InformeGerencialController::construirDatosInforme('mediodia');
assertTest(
    'Mediodía ventas ≤ cierre',
    $datosMedio['totales']['ventas_count'] <= $datosCierre['totales']['ventas_count'],
    'Mediodía tiene más ventas que cierre'
);
assertTest(
    'Mediodía números ≤ cierre',
    $datosMedio['totales']['numeros_count'] <= $datosCierre['totales']['numeros_count'],
    'Mediodía tiene más números que cierre'
);

// ── 8. Orden por cumplimiento ──
$ordenOk = true;
$filas = $datosCierre['vendedores'];
for ($i = 1, $n = count($filas); $i < $n; $i++) {
    if ($filas[$i - 1]['porcentaje'] < $filas[$i]['porcentaje']) {
        $ordenOk = false;
        break;
    }
    if ($filas[$i - 1]['porcentaje'] === $filas[$i]['porcentaje']
        && $filas[$i - 1]['total_dinero'] < $filas[$i]['total_dinero']) {
        $ordenOk = false;
        break;
    }
}
assertTest('Vendedores ordenados por cumplimiento', $ordenOk, 'Orden incorrecto');

// ── 9. Generación PDF (sin enviar correo) ──
try {
    $pdfPath = InformeGerencialController::generarPdf($datosCierre);
    $size = file_exists($pdfPath) ? filesize($pdfPath) : 0;
    assertTest('PDF generado', $size > 5000, "Tamaño PDF: {$size} bytes");
    if ($totalVendedores >= 30 && $size < 20000) {
        warn('PDF pequeño para muchos vendedores — revisar paginación');
    }
    if (file_exists($pdfPath)) {
        unlink($pdfPath);
    }
} catch (Throwable $e) {
    assertTest('PDF generado', false, $e->getMessage());
}

// ── 10. Plantilla PDF y pie por canvas ──
$tpl = file_get_contents(ROOT_PATH . '/includes/templates/informe-gerencial-pdf.php');
assertTest(
    'Plantilla sin footer fixed (evita página en blanco)',
    !str_contains($tpl, 'position: fixed') && !str_contains($tpl, 'pdf-footer'),
    'Aún usa footer CSS fixed'
);
$ctrlSrc = file_get_contents(ROOT_PATH . '/controllers/informeGerencial.controller.php');
assertTest(
    'Pie de página por canvas en cada hoja',
    str_contains($ctrlSrc, 'aplicarPiePaginaPdf') && str_contains($ctrlSrc, 'page_script'),
    'Falta page_script del pie'
);
assertTest(
    'Pie incluye crédito desarrollador',
    str_contains($ctrlSrc, 'cristianceballos.com') && str_contains($ctrlSrc, 'Cristian Ceballos'),
    'Falta crédito en pie'
);

// ── 11. Auth — roles y páginas ──
assertTest('Auth ROLE_VENDEDOR definido', Auth::ROLE_VENDEDOR === 'vendedor', '');
assertTest('Vendedor no accede a vendedores.php', !in_array('vendedores.php', Auth::VENDEDOR_PAGES, true), '');
assertTest('Vendedor accede a vender.php', in_array('vender.php', Auth::VENDEDOR_PAGES, true), '');
assertTest('AJAX rifas permitido a vendedor', in_array('rifas.ajax.php', Auth::VENDEDOR_AJAX, true), '');

// ── Resumen ──
echo "\n=== Resultado ===\n";
echo "✓ Pasaron: {$passed}\n";
echo "✗ Fallaron: {$failed}\n";
echo "⚠ Advertencias: {$warnings}\n";

exit($failed > 0 ? 1 : 0);

function syntaxOk(string $path): bool
{
    $output = [];
    $code = 0;
    exec('php -l ' . escapeshellarg($path) . ' 2>&1', $output, $code);
    return $code === 0;
}

function pass(string $msg): void
{
    global $passed;
    $passed++;
    echo "  ✓ {$msg}\n";
}

function fail(string $msg): void
{
    global $failed;
    $failed++;
    echo "  ✗ {$msg}\n";
}

function warn(string $msg): void
{
    global $warnings;
    $warnings++;
    echo "  ⚠ {$msg}\n";
}

function assertTest(string $label, bool $ok, string $detail = ''): void
{
    if ($ok) {
        pass($label);
    } else {
        fail($label . ($detail ? " — {$detail}" : ''));
    }
}

function stringifyError($response): string
{
    $msg = ApiRequest::getErrorMessage($response);
    return is_array($msg) ? json_encode($msg) : (string) $msg;
}
