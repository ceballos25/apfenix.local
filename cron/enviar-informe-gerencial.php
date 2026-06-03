#!/usr/bin/env php
<?php
/**
 * Cron — Informe gerencial automático por correo (PDF)
 *
 * Uso:
 *   php cron/enviar-informe-gerencial.php mediodia
 *   php cron/enviar-informe-gerencial.php cierre
 *
 * Crontab producción (cPanel — ver docs/CRON.md y cron/crontab.produccion.txt):
 *   59 12 * * * /usr/local/bin/php /home/apfenixc/public_html/cron/enviar-informe-gerencial.php mediodia >> /home/apfenixc/public_html/logs/cron-informe.log 2>&1
 *   59 23 * * * /usr/local/bin/php /home/apfenixc/public_html/cron/enviar-informe-gerencial.php cierre >> /home/apfenixc/public_html/logs/cron-informe.log 2>&1
 */

$projectRoot = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$logsDir     = $projectRoot . '/logs';

if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

$cronLogFile = $logsDir . '/cron-informe.log';

/**
 * Siempre escribe en logs/cron-informe.log (no depende del redirect de cPanel).
 */
function cronInformeLog(string $message): void
{
    global $cronLogFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($cronLogFile, $line, FILE_APPEND);
    fwrite(STDERR, $line);
}

$tipo = $argv[1] ?? '';

cronInformeLog('[informe-cron] === Inicio job === tipo=' . ($tipo !== '' ? $tipo : '(vacío)') . ' php=' . PHP_VERSION . ' sapi=' . php_sapi_name() . ' cwd=' . getcwd());

if (!in_array($tipo, ['mediodia', 'cierre'], true)) {
    cronInformeLog('[informe-cron] ERROR: argumento inválido. Uso: mediodia|cierre');
    exit(1);
}

try {
    $configFile = $projectRoot . '/config/config.php';
    if (!is_readable($configFile)) {
        throw new RuntimeException('No existe config.php en: ' . $configFile);
    }

    require_once $configFile;

    if (!defined('ROOT_PATH') || !ROOT_PATH) {
        throw new RuntimeException('ROOT_PATH no definido tras cargar config.php');
    }

    chdir(ROOT_PATH);
    cronInformeLog('[informe-cron] Config OK. ROOT_PATH=' . ROOT_PATH);

    if (defined('ENV_FILE_LOADED')) {
        cronInformeLog('[informe-cron] .env-ap cargado desde: ' . ENV_FILE_LOADED);
    }

    cronInformeLog('[informe-cron] CORREO_INFORME=' . (defined('CORREO_INFORME') && CORREO_INFORME !== '' ? CORREO_INFORME : '(vacío)'));
    cronInformeLog('[informe-cron] INFORME_GERENCIAL_ENABLED=' . (defined('INFORME_GERENCIAL_ENABLED') && INFORME_GERENCIAL_ENABLED ? 'true' : 'false'));

    require_once ROOT_PATH . '/controllers/informeGerencial.controller.php';
} catch (Throwable $e) {
    cronInformeLog('[informe-cron] ERROR al iniciar: ' . $e->getMessage());
    exit(1);
}

try {
    $ok = InformeGerencialController::ejecutarEnvio($tipo);
} catch (Throwable $e) {
    cronInformeLog('[informe-cron] ERROR en ejecutarEnvio: ' . $e->getMessage());
    exit(1);
}

if ($ok) {
    cronInformeLog('[informe-cron] OK: informe enviado (tipo: ' . $tipo . '). Ver también logs/informe-gerencial.log');
    exit(0);
}

cronInformeLog('[informe-cron] FALLO: no se envió el correo (tipo: ' . $tipo . '). Ver logs/informe-gerencial.log y logs/mail.log');
exit(1);
