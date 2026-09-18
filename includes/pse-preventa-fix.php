<?php

/**
 * Extra de preventa para PSE. Archivo nuevo a propósito (OPcache no puede servir una versión vieja).
 * 3→4, 5→7, 8→10, 10→13, 20→26, 25→33, 50→65
 */
class PsePreventaFix
{
    public static function entregados(int $pagados): int
    {
        if ($pagados < 3) {
            return $pagados;
        }

        $tz = new DateTimeZone('America/Bogota');
        $now = new DateTime('now', $tz);
        $start = new DateTime('2026-09-16 00:00:00', $tz);
        $end = new DateTime('2026-09-25 23:59:59', $tz);

        if ($now < $start || $now > $end) {
            return $pagados;
        }

        return $pagados + max(1, (int) round($pagados * 0.30));
    }

    /**
     * Cobro = monto / 9000. El extra de preventa no se cobra.
     * $qtyHint es lo que ya se debe entregar (respaldo), nunca lo pagado.
     */
    public static function cantidades(float $amount, int $qtyHint = 0): array
    {
        require_once __DIR__ . '/dinamica.php';
        require_once __DIR__ . '/promo2x1.php';
        $preventaQty = __DIR__ . '/preventa-qty.php';
        if (is_file($preventaQty)) {
            require_once $preventaQty;
        }

        $paid = DinamicaHelper::inferPaidFromTotal($amount);
        if ($paid <= 0) {
            $paid = max(0, $qtyHint);
        }

        $need = max(
            self::entregados($paid),
            Promo2x1Helper::quantityDelivered($paid),
            DinamicaHelper::quantityDelivered($paid),
            function_exists('apfenix_cantidad_entregada') ? apfenix_cantidad_entregada($paid) : $paid,
            $qtyHint
        );

        return [
            'paid' => $paid,
            'need' => $need,
        ];
    }

    public static function completarPorCodigo(string $code): array
    {
        $empty = [
            'fixed' => false,
            'id_sale' => null,
            'paid' => 0,
            'had' => 0,
            'need' => 0,
            'message' => '',
        ];

        if ($code === '') {
            $empty['message'] = 'Sin código';
            return $empty;
        }

        $res = ApiRequest::get('sales', [
            'linkTo' => 'code_sale',
            'equalTo' => $code,
            'select' => 'id_sale,quantity_sale,total_sale,id_customer_sale,id_raffle_sale,status_sale',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            $empty['message'] = 'Venta no encontrada';
            return $empty;
        }

        $venta = is_array($res->results) ? $res->results[0] : $res->results;

        require_once __DIR__ . '/dinamica.php';

        return self::completarVenta(
            (int) $venta->id_sale,
            (int) $venta->id_customer_sale,
            (int) $venta->id_raffle_sale,
            DinamicaHelper::inferPaidFromTotal((float) $venta->total_sale),
            (int) $venta->quantity_sale,
            $code
        );
    }

    public static function completarVenta(
        int $idSale,
        int $idCustomer,
        int $idRaffle,
        int $paidGuess,
        int $quantitySale,
        string $code = ''
    ): array {
        $paid = $paidGuess;
        $qtyBackup = 0;
        $amountBackup = 0.0;

        if ($code !== '') {
            $backup = ApiRequest::get('payment_backups', [
                'linkTo' => 'code_payment_backup',
                'equalTo' => $code,
                'select' => 'quantity_payment_backup,amount_payment_backup',
            ]);
            if (ApiRequest::isSuccess($backup) && !empty($backup->results)) {
                $b = is_array($backup->results) ? $backup->results[0] : $backup->results;
                $qtyBackup = (int) ($b->quantity_payment_backup ?? 0);
                $amountBackup = (float) ($b->amount_payment_backup ?? 0);
            }
        }

        $amount = $amountBackup > 0 ? $amountBackup : ($paid > 0 ? $paid * 9000 : 0);
        $cant = self::cantidades($amount, $qtyBackup);
        $paid = $cant['paid'] > 0 ? $cant['paid'] : $paid;
        if ($paid <= 0) {
            $paid = max(3, $quantitySale);
            $cant = self::cantidades($paid * 9000, $qtyBackup);
            $paid = $cant['paid'];
        }

        $need = max($cant['need'], self::entregados($paid));
        $ticketsRes = ApiRequest::get('tickets', [
            'linkTo' => 'id_sale_ticket',
            'equalTo' => $idSale,
            'select' => 'id_ticket',
            'startAt' => 0,
            'endAt' => 500,
        ]);
        $had = count(ApiRequest::resultsList($ticketsRes));

        $result = [
            'fixed' => false,
            'id_sale' => $idSale,
            'paid' => $paid,
            'had' => $had,
            'need' => $need,
            'message' => '',
        ];

        if ($need <= $had && $quantitySale === $need) {
            $result['message'] = 'OK';
            return $result;
        }

        if ($need <= $had) {
            ApiRequest::put(
                "sales?id={$idSale}&nameId=id_sale&token=no&except=code_sale",
                ['quantity_sale' => $need]
            );
            $result['fixed'] = true;
            $result['message'] = "quantity_sale {$quantitySale} → {$need}";
            return $result;
        }

        $bonus = $need - $had;
        $availRes = ApiRequest::get('tickets', [
            'linkTo' => 'id_raffle_ticket,status_ticket',
            'equalTo' => $idRaffle . ',0',
            'select' => 'id_ticket',
            'startAt' => 0,
            'endAt' => 100000,
        ]);
        $available = ApiRequest::resultsList($availRes);

        if (count($available) < $bonus) {
            $result['message'] = 'Sin tickets para completar extra';
            return $result;
        }

        shuffle($available);
        $ok = 0;
        foreach (array_slice($available, 0, $bonus) as $t) {
            $put = ApiRequest::put(
                "tickets?id={$t->id_ticket}&nameId=id_ticket&token=no&except=number_ticket",
                [
                    'status_ticket' => 1,
                    'id_customer_ticket' => $idCustomer,
                    'id_sale_ticket' => $idSale,
                ]
            );
            if (ApiRequest::isSuccess($put)) {
                $ok++;
            }
        }

        ApiRequest::put(
            "sales?id={$idSale}&nameId=id_sale&token=no&except=code_sale",
            ['quantity_sale' => $need]
        );

        $result['fixed'] = $ok > 0;
        $result['had'] = $had + $ok;
        $result['message'] = "Completados {$ok} extras (había {$had}, necesita {$need})";

        return $result;
    }
}
