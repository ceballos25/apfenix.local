<?php
/**
 * Datos de prueba: vendedores + ventas del día con horarios variados.
 *
 * Uso: php scripts/seed-informe-test.php
 *      php scripts/seed-informe-test.php --dry-run
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/controllers/apiRequest.controller.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$quiet  = in_array('--quiet', $argv ?? [], true);
$onlyVendors = in_array('--vendors-only', $argv ?? [], true);
$targetCount = 50;
$fromIndex = 1;

foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--count=')) {
        $targetCount = max(1, (int) substr($arg, 8));
    }
    if (str_starts_with($arg, '--from=')) {
        $fromIndex = max(1, (int) substr($arg, 7));
    }
}

$fecha  = date('Y-m-d');
$raffleId = 0;
$priceRaffle = 900.0;

$metodosPago = ['Efectivo', 'Nequi', 'Transferencia', 'Daviplata', 'Tarjeta'];

$vendedoresPlan = array_values(array_filter(
    construirPlanVendedores($targetCount),
    fn($p) => (int) preg_replace('/\D/', '', $p['email']) >= $fromIndex
));

$horarios = [
    '07:30:00', '07:55:00', '08:10:00', '08:25:00', '08:40:00',
    '09:05:00', '09:20:00', '09:45:00', '10:00:00', '10:18:00',
    '10:35:00', '10:50:00', '11:05:00', '11:22:00', '11:40:00',
    '12:00:00', '12:15:00', '12:30:00', '12:45:00', '12:58:00',
    '13:20:00', '14:10:00', '15:00:00', '16:30:00', '17:45:00',
    '18:20:00', '19:10:00', '20:00:00', '21:15:00', '22:30:00',
];

echo "=== Seed informe gerencial — {$fecha} ===\n";
if ($dryRun) {
    echo "(modo dry-run: no se escribirá en la API)\n";
}

$raffle = obtenerRifaActiva();
if (!$raffle) {
    fwrite(STDERR, "Error: no hay rifa activa.\n");
    exit(1);
}

$raffleId    = (int) $raffle->id_raffle;
$priceRaffle = (float) $raffle->price_raffle;
echo "Rifa: #{$raffleId} {$raffle->title_raffle} — \${$priceRaffle}\n\n";

$stats = ['vendedores' => 0, 'ventas' => 0, 'numeros' => 0, 'errores' => 0];
$horaIdx = 0;
$clienteSeq = 3109000000 + ((int) date('His')) % 100000;

foreach ($vendedoresPlan as $plan) {
    $idAdmin = asegurarVendedor($plan, $dryRun);
    if ($idAdmin <= 0) {
        $stats['errores']++;
        echo "  ✗ No se pudo crear/obtener {$plan['email']}\n";
        continue;
    }

    $stats['vendedores']++;
    if (!$quiet) {
        echo "Vendedor {$plan['name']} ({$plan['email']}) — meta {$plan['goal']} {$plan['goal_type']}\n";
    }

    if ($onlyVendors) {
        continue;
    }

    $ventasCreadasVendedor = 0;
    for ($i = 0; $i < $plan['ventas']; $i++) {
        $qty    = random_int($plan['qty_min'], $plan['qty_max']);
        $hora   = $horarios[$horaIdx % count($horarios)];
        $horaIdx++;
        $metodo = $metodosPago[array_rand($metodosPago)];
        $total  = round($qty * $priceRaffle, 0);
        $phone  = (string) (++$clienteSeq);

        if ($dryRun) {
            if (!$quiet) {
                echo "  · venta simulada {$hora} — {$qty} nums — \${$total}\n";
            }
            $stats['ventas']++;
            $stats['numeros'] += $qty;
            continue;
        }

        $result = crearVentaDirecta([
            'id_admin'            => $idAdmin,
            'id_raffle'           => $raffleId,
            'quantity'            => $qty,
            'total'               => $total,
            'payment_method_sale' => $metodo,
            'fecha_hora'          => "{$fecha} {$hora}",
            'phone'               => $phone,
            'name'                => "Cliente {$phone}",
            'lastname'            => 'Prueba',
            'email'               => "test{$phone}@seed.local",
        ]);

        if ($result['success']) {
            $stats['ventas']++;
            $stats['numeros'] += $qty;
            $ventasCreadasVendedor++;
            if (!$quiet) {
                echo "  ✓ venta #{$result['id_sale']} {$hora} — {$qty} nums — \${$total} — {$metodo}\n";
            }
        } else {
            $stats['errores']++;
            echo "  ✗ {$plan['email']}: {$result['message']}\n";
            if (str_contains($result['message'], 'disponibles')) {
                echo "    (sin tickets suficientes, se detiene este vendedor)\n";
                break;
            }
        }
    }

    if ($quiet && !$onlyVendors && $ventasCreadasVendedor > 0) {
        echo "  ✓ {$plan['email']}: {$ventasCreadasVendedor} venta(s)\n";
    }

    if (!$quiet) {
        echo "\n";
    }
}

echo "=== Resumen ===\n";
echo "Vendedores: {$stats['vendedores']}\n";
echo "Ventas creadas: {$stats['ventas']}\n";
echo "Números vendidos: {$stats['numeros']}\n";
echo "Errores: {$stats['errores']}\n";
echo "\nProbar informe: php cron/enviar-informe-gerencial.php mediodia\n";
echo "                php cron/enviar-informe-gerencial.php cierre\n";

function construirPlanVendedores(int $total): array
{
    $base = [
        ['name' => 'Ana García',       'email' => 'seed.vendedor1',  'goal_type' => 'ventas',  'goal' => 15,  'ventas' => 18, 'qty_min' => 1, 'qty_max' => 3],
        ['name' => 'Carlos Méndez',    'email' => 'seed.vendedor2',  'goal_type' => 'ventas',  'goal' => 20,  'ventas' => 22, 'qty_min' => 1, 'qty_max' => 4],
        ['name' => 'Diana López',      'email' => 'seed.vendedor3',  'goal_type' => 'ventas',  'goal' => 10,  'ventas' => 4,  'qty_min' => 1, 'qty_max' => 2],
        ['name' => 'Esteban Ruiz',     'email' => 'seed.vendedor4',  'goal_type' => 'numeros', 'goal' => 50,  'ventas' => 14, 'qty_min' => 2, 'qty_max' => 6],
        ['name' => 'Fabiana Torres',   'email' => 'seed.vendedor5',  'goal_type' => 'numeros', 'goal' => 80,  'ventas' => 8,  'qty_min' => 2, 'qty_max' => 5],
        ['name' => 'Gabriel Soto',     'email' => 'seed.vendedor6',  'goal_type' => 'ventas',  'goal' => 25,  'ventas' => 28, 'qty_min' => 1, 'qty_max' => 3],
        ['name' => 'Helena Vargas',    'email' => 'seed.vendedor7',  'goal_type' => 'ventas',  'goal' => 8,   'ventas' => 2,  'qty_min' => 1, 'qty_max' => 2],
        ['name' => 'Iván Castro',      'email' => 'seed.vendedor8',  'goal_type' => 'numeros', 'goal' => 100, 'ventas' => 20, 'qty_min' => 3, 'qty_max' => 7],
        ['name' => 'Juliana Pérez',    'email' => 'seed.vendedor9',  'goal_type' => 'ventas',  'goal' => 12,  'ventas' => 12, 'qty_min' => 1, 'qty_max' => 3],
        ['name' => 'Kevin Morales',    'email' => 'seed.vendedor10', 'goal_type' => 'numeros', 'goal' => 60,  'ventas' => 15, 'qty_min' => 2, 'qty_max' => 6],
    ];

    if ($total <= count($base)) {
        return array_slice($base, 0, $total);
    }

    $nombres = [
        'Laura', 'Mario', 'Natalia', 'Óscar', 'Patricia', 'Ricardo', 'Sandra', 'Tomás',
        'Valentina', 'Wilmer', 'Ximena', 'Yolanda', 'Zacarías', 'Adrián', 'Beatriz',
        'Camilo', 'Daniela', 'Edwin', 'Felipe', 'Gloria', 'Héctor', 'Ingrid', 'Jorge',
        'Karen', 'Leonardo', 'Martha', 'Nicolás', 'Olga', 'Pablo', 'Raquel', 'Sergio',
        'Tatiana', 'Ulises', 'Verónica', 'Walter', 'Xavier', 'Yenny', 'Alonso', 'Brenda', 'César',
    ];
    $apellidos = [
        'Rojas', 'Silva', 'Mora', 'Duarte', 'Salazar', 'Herrera', 'Ortiz', 'Parra',
        'Quintero', 'Restrepo', 'Suárez', 'Trujillo', 'Uribe', 'Vega', 'Zapata',
        'Acosta', 'Bravo', 'Cárdenas', 'Delgado', 'Escobar', 'Franco', 'Giraldo',
        'Henao', 'Ibarra', 'Jiménez', 'Londoño', 'Mejía', 'Nieto', 'Osorio', 'Pineda',
    ];
    $metasVentas  = [5, 8, 10, 12, 15, 18, 20, 25, 30];
    $metasNumeros = [30, 40, 50, 60, 80, 100, 120, 150];

    for ($i = 11; $i <= $total; $i++) {
        $goalType = ($i % 3 === 0) ? 'numeros' : 'ventas';
        $goal = $goalType === 'numeros'
            ? $metasNumeros[$i % count($metasNumeros)]
            : $metasVentas[$i % count($metasVentas)];

        $ventasCount = ($i % 7 === 0) ? 0 : (($i % 5) + 2);
        $qtyMin = $goalType === 'numeros' ? 2 : 1;
        $qtyMax = $goalType === 'numeros' ? 5 : 3;

        $base[] = [
            'name'      => $nombres[($i - 11) % count($nombres)] . ' ' . $apellidos[($i - 11) % count($apellidos)],
            'email'     => "seed.vendedor{$i}",
            'goal_type' => $goalType,
            'goal'      => $goal,
            'ventas'    => $ventasCount,
            'qty_min'   => $qtyMin,
            'qty_max'   => $qtyMax,
        ];
    }

    return $base;
}

function obtenerRifaActiva(): ?object
{
    $res = ApiRequest::get('raffles', [
        'linkTo'    => 'status_raffle',
        'equalTo'   => '1',
        'select'    => 'id_raffle,title_raffle,price_raffle',
        'orderBy'   => 'id_raffle',
        'orderMode' => 'DESC',
        'startAt'   => 0,
        'endAt'     => 1,
    ]);

    if (!ApiRequest::isSuccess($res) || empty($res->results)) {
        return null;
    }

    return is_array($res->results) ? $res->results[0] : $res->results;
}

function asegurarVendedor(array $plan, bool $dryRun): int
{
    $existente = ApiRequest::get('admins', [
        'linkTo'  => 'email_admin',
        'equalTo' => $plan['email'],
        'select'  => 'id_admin,email_admin',
    ]);

    if (ApiRequest::isSuccess($existente) && !empty($existente->results)) {
        $v = is_array($existente->results) ? $existente->results[0] : $existente->results;
        return (int) $v->id_admin;
    }

    if ($dryRun) {
        return 999;
    }

    $res = ApiRequest::post('admins?register=true&suffix=admin', [
        'name_admin'       => $plan['name'],
        'email_admin'      => $plan['email'],
        'password_admin'   => 'Vendedor123',
        'rol_admin'        => 'vendedor',
        'status_admin'     => 1,
        'goal_type_admin'  => $plan['goal_type'],
        'goal_value_admin' => $plan['goal'],
    ]);

    if (!ApiRequest::isSuccess($res)) {
        return 0;
    }

    return (int) ($res->results->lastId ?? 0);
}

function crearVentaDirecta(array $data): array
{
    $cantidad = (int) $data['quantity'];
    $idRaffle = (int) $data['id_raffle'];

    $res = ApiRequest::get('tickets', [
        'linkTo'  => 'id_raffle_ticket,status_ticket',
        'equalTo' => "{$idRaffle},0",
        'select'  => 'id_ticket',
        'startAt' => 0,
        'endAt'   => max(500, $cantidad * 3),
    ]);

    if (!ApiRequest::isSuccess($res) || empty($res->results)) {
        return ['success' => false, 'message' => 'No hay números disponibles'];
    }

    $tickets = is_array($res->results) ? $res->results : [$res->results];
    if (count($tickets) < $cantidad) {
        return ['success' => false, 'message' => 'No hay suficientes números disponibles'];
    }

    shuffle($tickets);
    $seleccionados = array_slice($tickets, 0, $cantidad);
    $ticketIds = array_map(fn($t) => (int) $t->id_ticket, $seleccionados);

    $idCliente = crearClienteSeed($data);
    if (!$idCliente) {
        return ['success' => false, 'message' => 'Error al crear cliente'];
    }

    $code = 'SEED' . time() . random_int(100, 999);

    $resVenta = ApiRequest::post('sales?token=no&suffix=sale&except=code_sale', [
        'id_customer_sale'    => $idCliente,
        'id_raffle_sale'      => $idRaffle,
        'code_sale'           => $code,
        'quantity_sale'       => $cantidad,
        'total_sale'          => $data['total'],
        'payment_method_sale' => $data['payment_method_sale'],
        'status_sale'         => 1,
        'id_admin_sale'       => (int) $data['id_admin'],
    ]);

    if (!ApiRequest::isSuccess($resVenta)) {
        return ['success' => false, 'message' => 'Error al crear venta: ' . ApiRequest::getErrorMessage($resVenta)];
    }

    $idVenta = (int) ($resVenta->results->lastId ?? 0);

    foreach ($ticketIds as $idTicket) {
        ApiRequest::put(
            "tickets?id={$idTicket}&nameId=id_ticket&token=no&except=number_ticket",
            [
                'status_ticket'      => 1,
                'id_customer_ticket' => $idCliente,
                'id_sale_ticket'     => $idVenta,
            ]
        );
    }

    ApiRequest::put(
        "sales?id={$idVenta}&nameId=id_sale&token=no&except=code_sale",
        ['date_created_sale' => $data['fecha_hora']]
    );

    return ['success' => true, 'id_sale' => $idVenta];
}

function crearClienteSeed(array $data): ?int
{
    $phone = preg_replace('/[^0-9]/', '', $data['phone']);

    $existente = ApiRequest::get('customers', [
        'linkTo'  => 'phone_customer',
        'equalTo' => $phone,
        'select'  => 'id_customer',
    ]);

    if (ApiRequest::isSuccess($existente) && !empty($existente->results)) {
        $c = is_array($existente->results) ? $existente->results[0] : $existente->results;
        return (int) $c->id_customer;
    }

    $res = ApiRequest::post('customers?token=no&suffix=customer&except=name_customer', [
        'name_customer'       => $data['name'],
        'lastname_customer'   => $data['lastname'],
        'phone_customer'      => $phone,
        'email_customer'      => $data['email'],
        'department_customer' => 'Antioquia',
        'city_customer'       => 'Medellín',
        'status_customer'     => 1,
    ]);

    if (!ApiRequest::isSuccess($res)) {
        return null;
    }

    return (int) ($res->results->lastId ?? 0);
}
