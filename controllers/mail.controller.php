<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailController {

    private static function configureSmtp(PHPMailer $mail): void
    {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->Port       = SMTP_PORT;

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
            mkdir($dir, 0755, true);
        }
        file_put_contents(
            $dir . '/mail.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND
        );
    }

    public static function enviarCorreoVenta(int $idSale): bool {

        $venta = VentasController::consultarVenta($idSale);
        if (!$venta) return false;

        $tickets = VentasController::consultarTicketsVenta($idSale);
        $html = VentasController::generarRecibo($venta, $tickets);
        if (!$html) return false;

        $mail = new PHPMailer(true);

        try {
            self::configureSmtp($mail);
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($venta->email_customer, trim($venta->name_customer . ' ' . $venta->lastname_customer));

            if (MAIL_BCC) {
                $mail->addBCC(MAIL_BCC);
            }

            $mail->isHTML(true);
            $mail->Subject = '🎟️ Confirmación de compra - ' . SITE_NAME . ' - ' . $idSale;
            $mail->Body    = $html;

            $mail->send();
            return true;

        } catch (Exception $e) {
            self::logMailError($e->getMessage());
            return false;
        }
    }

    /**
     * Envía informe gerencial PDF al destinatario configurado en CORREO_INFORME.
     */
    public static function enviarInformeGerencial(string $pdfPath, array $datos): bool
    {
        if (!file_exists($pdfPath) || empty(CORREO_INFORME)) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            self::configureSmtp($mail);
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress(CORREO_INFORME);

            $subject = 'Informe de Ventas por Vendedor ' . $datos['tipo_label'] . ' — ' . $datos['fecha_formateada'];

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = self::buildInformeEmailBody($datos);
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", self::buildInformeEmailBody($datos)));

            $mail->addAttachment($pdfPath, basename($pdfPath));

            $mail->send();
            return true;

        } catch (Exception $e) {
            self::logMailError('[Informe gerencial] ' . $e->getMessage());
            return false;
        }
    }

    private static function buildInformeEmailBody(array $datos): string
    {
        $ventas = (int)$datos['totales']['ventas_count'];
        $numeros = (int)$datos['totales']['numeros_count'];
        $dinero = number_format($datos['totales']['dinero'], 0, ',', '.');

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
                        <td style="padding:8px;border:1px solid #eee;"><strong>Total</strong><br>$' . $dinero . '</td>
                    </tr>
                </table>
                <p>El detalle completo por vendedor se encuentra adjunto en PDF.</p>
                <p style="color:#6c757d;font-size:12px;">' . htmlspecialchars(SITE_NAME) . ' — envío automático</p>
            </div>';
    }
}
