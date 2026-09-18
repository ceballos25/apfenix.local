<?php

/**
 * Red de seguridad 2×1: si la venta quedó corta, completa tickets y quantity_sale.
 * Archivo nuevo a propósito (evita OPcache con código viejo).
 */
class Promo2x1Garantia
{
    /**
     * Tras aprobar pago web: garantiza números según promo.
     * @return array{fixed:bool, id_sale:?int, paid:int, had:int, need:int, message:string}
     */
    public static function asegurarPorCodigoVenta(string $codeSale): array
    {
        require_once __DIR__ . '/promo2x1.php';
        require_once __DIR__ . '/../controllers/apiRequest.controller.php';

        $empty = [
            'fixed' => false,
            'id_sale' => null,
            'paid' => 0,
            'had' => 0,
            'need' => 0,
            'message' => '',
        ];

        if ($codeSale === '') {
            $empty['message'] = 'Sin código de venta';
            return $empty;
        }

        $res = ApiRequest::get('sales', [
            'linkTo' => 'code_sale',
            'equalTo' => $codeSale,
            'select' => 'id_sale,quantity_sale,total_sale,id_customer_sale,id_raffle_sale,status_sale',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            $empty['message'] = 'Venta no encontrada aún';
            return $empty;
        }

        $venta = is_array($res->results) ? $res->results[0] : $res->results;
        $idSale = (int) $venta->id_sale;
        $idCustomer = (int) $venta->id_customer_sale;
        $idRaffle = (int) $venta->id_raffle_sale;
        require_once __DIR__ . '/dinamica.php';
        $paid = DinamicaHelper::inferPaidFromTotal((float) $venta->total_sale);

        // Preferir quantity del backup si existe
        $backup = ApiRequest::get('payment_backups', [
            'linkTo' => 'code_payment_backup',
            'equalTo' => $codeSale,
            'select' => 'quantity_payment_backup',
        ]);
        if (ApiRequest::isSuccess($backup) && !empty($backup->results)) {
            $b = is_array($backup->results) ? $backup->results[0] : $backup->results;
            if (!empty($b->quantity_payment_backup)) {
                $paid = (int) $b->quantity_payment_backup;
            }
        }

        $need = Promo2x1Helper::quantityDelivered($paid);
        require_once __DIR__ . '/preventa-qty.php';
        $need = max($need, apfenix_cantidad_entregada($paid));

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

        if ($need <= $had && (int) $venta->quantity_sale === $need) {
            $result['message'] = 'OK sin cambios';
            return $result;
        }

        if ($need <= $had && (int) $venta->quantity_sale !== $need) {
            ApiRequest::put(
                "sales?id={$idSale}&nameId=id_sale&token=no&except=code_sale",
                ['quantity_sale' => $need]
            );
            $result['fixed'] = true;
            $result['message'] = "Actualizado quantity_sale a {$need}";
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
            $result['message'] = 'No hay tickets suficientes para completar promo';
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
        $result['message'] = "Completados {$ok} tickets (había {$had}, necesitaba {$need})";

        return $result;
    }

    /**
     * Recorre ventas de preventa y completa extras faltantes.
     */
    public static function completarVentasPreventa(): array
    {
        require_once __DIR__ . '/preventa-qty.php';
        require_once __DIR__ . '/../controllers/apiRequest.controller.php';

        $res = ApiRequest::get('sales', [
            'select' => 'id_sale,code_sale,quantity_sale,total_sale,date_created_sale,payment_method_sale,id_customer_sale,id_raffle_sale,status_sale',
            'orderBy' => 'id_sale',
            'orderMode' => 'DESC',
            'startAt' => 0,
            'endAt' => 100000,
        ]);

        $ventas = ApiRequest::resultsList($res);
        $desde = strtotime('2026-09-16 00:00:00');
        $report = [
            'revisadas' => 0,
            'completas' => 0,
            'corregidas' => [],
            'errores' => [],
        ];

        foreach ($ventas as $v) {
            $fecha = strtotime((string) ($v->date_created_sale ?? ''));
            if ($fecha && $fecha < $desde) {
                continue;
            }
            if ((int) ($v->status_sale ?? 1) === 0) {
                continue;
            }
            $code = trim((string) ($v->code_sale ?? ''));
            if ($code === '') {
                continue;
            }

            $report['revisadas']++;
            $out = self::asegurarPorCodigoVenta($code);
            $fila = [
                'code' => $code,
                'nombre' => 'id_customer ' . (int) ($v->id_customer_sale ?? 0),
                'telefono' => '',
                'pagados' => $out['paid'],
                'tenia' => $out['had'],
                'necesita' => $out['need'],
                'message' => $out['message'],
            ];

            if (!empty($out['fixed'])) {
                $report['corregidas'][] = $fila;
            } elseif (($out['need'] ?? 0) > ($out['had'] ?? 0)) {
                $report['errores'][] = $fila;
            } else {
                $report['completas']++;
            }
        }

        return $report;
    }
}
