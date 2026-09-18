<?php
/**
 * Certifica el flujo PSE local (respaldo + webhook) sin cobrar en OpenPay.
 * Uso: php scripts/certificar-pse.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/apiRequest.controller.php';
require_once __DIR__ . '/../includes/pse-preventa-fix.php';

$base = rtrim(BASE_URL, '/');
$phone = '3009990018';
$report = [];
$okAll = true;

function step(string $name, bool $ok, string $detail = ''): void
{
    global $report, $okAll;
    $report[] = ($ok ? 'OK   ' : 'FALLO') . " $name" . ($detail !== '' ? " — $detail" : '');
    if (!$ok) {
        $okAll = false;
    }
}

function httpPost(string $url, array $fields, int $timeout = 45): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'err' => $err];
}

function httpPostJson(string $url, array $payload, int $timeout = 60): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'err' => $err];
}

$need = PsePreventaFix::entregados(3);
step('Preventa 3→4', $need === 4, "entregados=$need");

$ajax = httpPost("$base/front/ajax/web.ajax.php", [
    'action' => 'crear_respaldo',
    'id_raffle' => 1,
    'quantity' => 3,
    'amount' => 27000,
    'name_customer' => 'Cert',
    'lastname_customer' => 'PSE',
    'phone_customer' => $phone,
    'email_customer' => 'cert.pse.prueba@apfenix.local',
    'department_customer' => 'Cundinamarca',
    'city_customer' => 'Bogota',
]);
$json = json_decode((string) $ajax['body'], true);
step('1. crear_respaldo (igual que el checkout)', !empty($json['success']) && !empty($json['code_payment_backup']), $ajax['body']);

$code = (string) ($json['code_payment_backup'] ?? '');
$idBackup = (int) ($json['id_payment_backup'] ?? 0);
$idSale = 0;
$ticketNums = [];

if ($code !== '') {
    $t0 = microtime(true);
    $hook = httpPostJson("$base/openpay/webhook.php", [
        'type' => 'charge.succeeded',
        'transaction' => [
            'id' => 'cert-' . uniqid(),
            'status' => 'completed',
            'order_id' => $code,
            'amount' => 27000.00,
            'method' => 'bank_account',
        ],
    ], 70);
    $elapsed = round(microtime(true) - $t0, 1);
    step('2. webhook charge.succeeded HTTP 200', $hook['code'] === 200, "http={$hook['code']} body=" . substr((string) $hook['body'], 0, 80) . " {$elapsed}s");
    step('2b. webhook respondió OK antes de morir', trim((string) $hook['body']) === 'OK' || str_contains((string) $hook['body'], 'OK'), (string) $hook['body']);

    $saleRes = ApiRequest::get('sales', [
        'linkTo' => 'code_sale',
        'equalTo' => $code,
        'select' => 'id_sale,quantity_sale,total_sale,payment_method_sale,code_sale',
    ]);
    $sale = ApiRequest::resultsList($saleRes)[0] ?? null;
    if ($sale) {
        $idSale = (int) $sale->id_sale;
        $tickets = ApiRequest::resultsList(ApiRequest::get('tickets', [
            'linkTo' => 'id_sale_ticket',
            'equalTo' => $idSale,
            'select' => 'id_ticket,number_ticket',
            'startAt' => 0,
            'endAt' => 50,
        ]));
        foreach ($tickets as $t) {
            $ticketNums[] = (string) ($t->number_ticket ?? '');
        }
        step('3. venta creada por Página Web', ($sale->payment_method_sale ?? '') === 'Página Web', "id=$idSale method={$sale->payment_method_sale}");
        step('4. pagó 3 y quantity_sale=4', (int) $sale->quantity_sale === 4, "qty={$sale->quantity_sale} total={$sale->total_sale}");
        step('5. hay 4 tickets asignados', count($tickets) === 4, 'nums=' . implode(',', $ticketNums));
    } else {
        step('3. venta creada', false, 'no existe sale con ' . $code);
        step('4. pagó 3 y quantity_sale=4', false);
        step('5. hay 4 tickets asignados', false);
    }
}

echo implode(PHP_EOL, $report) . PHP_EOL;
echo $okAll ? "CERTIFICADO: flujo PSE entrega el extra.\n" : "NO CERTIFICADO\n";

if ($idSale > 0) {
    $tickets = ApiRequest::resultsList(ApiRequest::get('tickets', [
        'linkTo' => 'id_sale_ticket',
        'equalTo' => $idSale,
        'select' => 'id_ticket',
        'startAt' => 0,
        'endAt' => 50,
    ]));
    foreach ($tickets as $t) {
        ApiRequest::put(
            "tickets?id={$t->id_ticket}&nameId=id_ticket&token=no&except=number_ticket",
            [
                'status_ticket' => 0,
                'id_customer_ticket' => 'null',
                'id_sale_ticket' => 'null',
            ]
        );
    }
    ApiRequest::delete("sales?id={$idSale}&nameId=id_sale&token=no");
    echo "Limpieza: tickets liberados y venta {$idSale} eliminada.\n";
}

exit($okAll ? 0 : 1);
