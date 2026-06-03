#!/usr/bin/env php
<?php
/**
 * Cron — Informe gerencial automático por correo (PDF)
 *
 * Uso:
 *   php cron/enviar-informe-gerencial.php mediodia
 *   php cron/enviar-informe-gerencial.php cierre
 *
 * Crontab (America/Bogota):
 *   59 12 * * * /usr/bin/php /ruta/apfenix.local/cron/enviar-informe-gerencial.php mediodia >> /ruta/apfenix.local/logs/cron-informe.log 2>&1
 *   59 23 * * * /usr/bin/php /ruta/apfenix.local/cron/enviar-informe-gerencial.php cierre >> /ruta/apfenix.local/logs/cron-informe.log 2>&1
 */

$tipo = $argv[1] ?? '';

if (!in_array($tipo, ['mediodia', 'cierre'], true)) {
    fwrite(STDERR, "Uso: php cron/enviar-informe-gerencial.php [mediodia|cierre]\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/controllers/informeGerencial.controller.php';

$ok = InformeGerencialController::ejecutarEnvio($tipo);

exit($ok ? 0 : 1);
