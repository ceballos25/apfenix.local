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
        $paid = (int) round(((float) $venta->total_sale) / 900);

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

        $ticketsRes = ApiRequest::get('tickets', [
            'linkTo' => 'id_sale_ticket',
            'equalTo' => $idSale,
            'select' => 'id_ticket',
            'startAt' => 0,
            'endAt' => 500,
        ]);
        $had = is_array($ticketsRes->results ?? null) ? count($ticketsRes->results) : 0;

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

        $available = is_array($availRes->results ?? null) ? $availRes->results : [];
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

        if (class_exists('MailController') || is_file(__DIR__ . '/../controllers/mail.controller.php')) {
            require_once __DIR__ . '/../controllers/mail.controller.php';
            require_once __DIR__ . '/../controllers/ventas.controller.php';
            MailController::enviarCorreoVenta($idSale);
        }

        $result['fixed'] = $ok > 0;
        $result['had'] = $had + $ok;
        $result['message'] = "Completados {$ok} tickets (había {$had}, necesitaba {$need})";

        return $result;
    }
}
