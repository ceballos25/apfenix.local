<?php
/**
 * Reenvía el correo de una venta.
 * Uso: php scripts/reenviar-correo.php 15
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/apiRequest.controller.php';
require_once __DIR__ . '/../controllers/ventas.controller.php';
require_once __DIR__ . '/../controllers/mail.controller.php';

$idSale = (int) ($argv[1] ?? 0);
if ($idSale <= 0) {
    fwrite(STDERR, "Uso: php scripts/reenviar-correo.php ID_VENTA\n");
    exit(1);
}

$ok = MailController::enviarCorreoVenta($idSale);
echo $ok ? "Correo enviado para venta {$idSale}\n" : "FALLÓ el correo de la venta {$idSale}. Revisa logs/mail.log\n";
exit($ok ? 0 : 1);
