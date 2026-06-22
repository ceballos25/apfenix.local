<?php

/**
 * Webhook Meteor — notifica al Agente IA cuando se valida una venta agent.
 */
class MeteorWebhookController
{
    private static function formatPhoneWithCountryCode(string $phone, string $countryCode = '57'): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return '';
        }

        $digits = ltrim($digits, '0');

        if (str_starts_with($digits, $countryCode)) {
            return '+' . $digits;
        }

        return '+' . $countryCode . $digits;
    }

    private static function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

        $logFile = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__)) . '/logs/meteor-webhook.log';
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        error_log('[MeteorWebhook] ' . $message);
    }

    /**
     * Envía POST con firma HMAC-SHA256 del body JSON.
     *
     * @return array{success: bool, message: string, http_code?: int, response?: string}
     */
    public static function notificarVentaValidada(array $data): array
    {
        $url = defined('METEOR_WEBHOOK_URL') ? METEOR_WEBHOOK_URL : env('METEOR_WEBHOOK_URL', '');
        $secret = defined('METEOR_WEBHOOK_SECRET') ? METEOR_WEBHOOK_SECRET : env('METEOR_WEBHOOK_SECRET', '');

        $urlConfigured = $url !== '';
        $secretConfigured = $secret !== '';

        self::log(sprintf(
            'Intento webhook code_agent=%s | url_config=%s | secret_config=%s',
            $data['code_agent'] ?? '-',
            $urlConfigured ? 'si' : 'NO',
            $secretConfigured ? 'si' : 'NO'
        ));

        if (!$urlConfigured || !$secretConfigured) {
            $msg = 'Webhook no configurado: agregue METEOR_WEBHOOK_URL y METEOR_WEBHOOK_SECRET en .env-ap del servidor';
            self::log('ERROR: ' . $msg);
            return ['success' => false, 'message' => $msg];
        }

        $numbersUrl = $data['numbers'] ?? '';
        if ($numbersUrl === '' && !empty($data['code_agent'])) {
            $numbersUrl = 'https://apfenix.com/agents/confirm?code=' . rawurlencode($data['code_agent']);
        }

        $phoneCustomer = (string) ($data['phone_customer'] ?? '');
        $phoneIntl = self::formatPhoneWithCountryCode($phoneCustomer);
        $userNs = (string) ($data['user_ns_agent'] ?? '');

        $payload = [
            'phone_customer'      => $phoneCustomer,
            'phone_code_customer' => $phoneIntl,
            'phone'               => $phoneIntl,
            'id_customer'         => (int) ($data['id_customer'] ?? 0),
            'code_agent'          => (string) ($data['code_agent'] ?? ''),
            'user_ns_agent'       => $userNs,
            'user_ns'             => $userNs,
            'numbers'             => $numbersUrl,
            'status'              => 'validado',
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            self::log('ERROR: no se pudo codificar JSON');
            return ['success' => false, 'message' => 'Error codificando payload del webhook'];
        }

        $signature = hash_hmac('sha256', $body, $secret);

        self::log('POST ' . $url . ' | body=' . $body);

        if (!function_exists('curl_init')) {
            $msg = 'cURL no está disponible en el servidor';
            self::log('ERROR: ' . $msg);
            return ['success' => false, 'message' => $msg];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-Signature: ' . $signature,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $msg = 'Error de conexión al webhook: ' . ($curlError ?: 'desconocido');
            self::log('ERROR cURL: ' . $msg);
            return ['success' => false, 'message' => $msg];
        }

        self::log('Respuesta HTTP ' . $httpCode . ' | ' . substr((string) $response, 0, 1000));

        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = 'Webhook respondió HTTP ' . $httpCode;
            if ($response !== '') {
                $msg .= ': ' . substr((string) $response, 0, 200);
            }
            return [
                'success'   => false,
                'message'   => $msg,
                'http_code' => $httpCode,
                'response'  => (string) $response,
            ];
        }

        self::log('OK webhook enviado para code_agent=' . ($data['code_agent'] ?? '-'));

        return [
            'success'   => true,
            'message'   => 'Webhook enviado correctamente',
            'http_code' => $httpCode,
            'response'  => (string) $response,
        ];
    }
}
