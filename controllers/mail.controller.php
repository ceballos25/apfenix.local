<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class MailController {

    /** @var array<int, bool> */
    private static $enviados = [];

    /**
     * Carga Composer solo al enviar correo. Retorna false si vendor/ no existe.
     */
    private static function boot(): bool
    {
        static $loaded = false;

        if ($loaded) {
            return true;
        }

        $autoload = ROOT_PATH . '/vendor/autoload.php';
        if (!is_readable($autoload)) {
            self::logMailError('vendor/autoload.php no encontrado en el servidor.');
            return false;
        }

        require_once $autoload;
        $loaded = true;
        return true;
    }

    private static function configureSmtp(PHPMailer $mail): void
    {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 20;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        if (SMTP_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
    }

    private static function logMailError(string $message): void
    {
        $dir = ROOT_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(
            $dir . '/mail.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND
        );
    }

    public static function enviarCorreoVenta(int $idSale): bool
    {
        if ($idSale <= 0) {
            return false;
        }
        if (array_key_exists($idSale, self::$enviados)) {
            return self::$enviados[$idSale];
        }

        if (!self::boot()) {
            self::logMailError("Venta {$idSale}: no se pudo cargar PHPMailer (vendor).");
            return self::$enviados[$idSale] = false;
        }

        $venta = VentasController::consultarVenta($idSale);
        if (!$venta) {
            self::logMailError("Venta {$idSale}: no se encontró la venta para armar el correo.");
            return false;
        }

        $email = trim((string) ($venta->email_customer ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::logMailError("Venta {$idSale}: correo del cliente inválido ({$email}).");
            return false;
        }

        $tickets = VentasController::consultarTicketsVenta($idSale);
        $html = VentasController::generarRecibo($venta, $tickets);
        if (!$html) {
            self::logMailError("Venta {$idSale}: no se pudo generar el HTML del recibo.");
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $previousTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', '30');

            self::configureSmtp($mail);
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($email, trim($venta->name_customer . ' ' . $venta->lastname_customer));

            if (MAIL_BCC) {
                $mail->addBCC(MAIL_BCC);
            }

            $mail->isHTML(true);
            $mail->Subject = '🎟️ Confirmación de compra - ' . SITE_NAME . ' - ' . $idSale;
            $mail->Body    = $html;
            $mail->AltBody = 'Gracias por tu compra. Revisa el comprobante en este correo.';

            $mail->send();
            if ($previousTimeout !== false) {
                ini_set('default_socket_timeout', (string) $previousTimeout);
            }
            self::logMailError("Venta {$idSale}: enviado a {$email}");
            return self::$enviados[$idSale] = true;

        } catch (Throwable $e) {
            self::logMailError("Venta {$idSale} via " . SMTP_HOST . ': ' . $e->getMessage());
            $retryHost = strtolower((string) SMTP_HOST);
            if ($retryHost !== 'localhost' && $retryHost !== '127.0.0.1') {
                try {
                    $mail = new PHPMailer(true);
                    self::configureSmtp($mail);
                    $mail->Host = 'localhost';
                    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                    $mail->addAddress($email, trim($venta->name_customer . ' ' . $venta->lastname_customer));
                    if (MAIL_BCC) {
                        $mail->addBCC(MAIL_BCC);
                    }
                    $mail->isHTML(true);
                    $mail->Subject = 'Confirmación de compra - ' . SITE_NAME . ' - ' . $idSale;
                    $mail->Body    = $html;
                    $mail->AltBody = 'Gracias por tu compra. Revisa el comprobante en este correo.';
                    $mail->send();
                    self::logMailError("Venta {$idSale}: enviado a {$email} vía localhost");
                    return self::$enviados[$idSale] = true;
                } catch (Throwable $e2) {
                    self::logMailError("Venta {$idSale} via localhost: " . $e2->getMessage());
                }
            }
            return self::$enviados[$idSale] = false;
        }
    }

    public static function enviarInformeGerencial(string $pdfPath, array $datos): bool
    {
        if (!self::boot()) {
            return false;
        }

        if (!file_exists($pdfPath) || empty(CORREO_INFORME)) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            self::configureSmtp($mail);
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress(CORREO_INFORME);
            self::addInformeCopiasOcultas($mail);

            $subject = 'Informe de Ventas por Vendedor ' . $datos['tipo_label'] . ' — ' . $datos['fecha_formateada'];

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = self::buildInformeEmailBody($datos);
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", self::buildInformeEmailBody($datos)));

            $mail->addAttachment($pdfPath, basename($pdfPath));

            $mail->send();
            return true;

        } catch (MailException $e) {
            self::logMailError('[Informe gerencial] ' . $e->getMessage());
            return false;
        }
    }

    private static function addInformeCopiasOcultas(PHPMailer $mail): void
    {
        if (empty(CORREO_INFORME_BCC)) {
            return;
        }

        $principal = strtolower(trim(CORREO_INFORME));
        foreach (explode(',', CORREO_INFORME_BCC) as $bcc) {
            $bcc = trim($bcc);
            if ($bcc === '' || strtolower($bcc) === $principal) {
                continue;
            }
            $mail->addBCC($bcc);
        }
    }

    private static function buildInformeEmailBody(array $datos): string
    {
        $ventas = (int)$datos['totales']['ventas_count'];
        $numeros = (int)$datos['totales']['numeros_count'];
        $recaudo = $datos['totales']['recaudo'] ?? ['total' => $datos['totales']['dinero'] ?? 0, 'transferencia' => 0, 'efectivo' => 0];
        $dinero = number_format($recaudo['total'], 0, ',', '.');
        $transfer = number_format($recaudo['transferencia'], 0, ',', '.');
        $efectivo = number_format($recaudo['efectivo'], 0, ',', '.');

        return '
            <div style="font-family:Arial,sans-serif;color:#1a1a1a;max-width:600px;">
                <h2 style="color:#d4af37;margin-bottom:8px;">Informe de ventas por vendedor</h2>
                <p><strong>' . htmlspecialchars($datos['tipo_label']) . '</strong></p>
                <p>Fecha: <strong>' . htmlspecialchars($datos['fecha_formateada']) . '</strong><br>
                Corte: <strong>' . htmlspecialchars($datos['hora_corte']) . '</strong></p>
                <table style="width:100%;border-collapse:collapse;margin:16px 0;">
                    <tr>
                        <td style="padding:8px;border:1px solid #eee;"><strong>Ventas</strong><br>' . $ventas . '</td>
                        <td style="padding:8px;border:1px solid #eee;"><strong>Números</strong><br>' . $numeros . '</td>
                        <td style="padding:8px;border:1px solid #eee;"><strong>Total recaudado</strong><br>$' . $dinero . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px;border:1px solid #eee;"><strong>Transferencia</strong><br>$' . $transfer . '</td>
                        <td style="padding:8px;border:1px solid #eee;"><strong>Efectivo</strong><br>$' . $efectivo . '</td>
                        <td style="padding:8px;border:1px solid #eee;"><strong>Consolidado</strong><br>$' . $dinero . '</td>
                    </tr>
                </table>
                <p>El detalle completo por vendedor se encuentra adjunto en PDF.</p>
                <p style="color:#6c757d;font-size:12px;">' . htmlspecialchars(SITE_NAME) . ' — envío automático</p>
            </div>';
    }
}
