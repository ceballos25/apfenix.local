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

$tipo = $argv[1] ?? '';

if (!in_array($tipo, ['mediodia', 'cierre'], true)) {
    fwrite(STDERR, "Uso: php cron/enviar-informe-gerencial.php [mediodia|cierre]\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/controllers/informeGerencial.controller.php';

$ok = InformeGerencialController::ejecutarEnvio($tipo);

exit($ok ? 0 : 1);
