<?php
require_once __DIR__ . '/ventas.controller.php';
require_once __DIR__ . '/mail.controller.php';
require_once __DIR__ . '/meteorWebhook.controller.php';
require_once __DIR__ . '/apiRequest.controller.php';

class AgentsController
{
    const TABLE = 'agents';

    /** 0 = pendiente, 1 = validado, 2 = rechazado, 3 = error */
    const STATUS_PENDIENTE = 0;
    const STATUS_VALIDADO  = 1;
    const STATUS_RECHAZADO = 2;
    const STATUS_ERROR     = 3;

    /* =====================================================
     * LISTAR PENDIENTES
     * ===================================================== */
    public static function obtenerAgentes()
    {
        $res = ApiRequest::get(self::TABLE, [
            'select'    => '*',
            'linkTo'    => 'status_agent',
            'equalTo'   => (string) self::STATUS_PENDIENTE,
            'orderBy'   => 'id_agent',
            'orderMode' => 'DESC',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return ['success' => true, 'data' => []];
        }

        $lista = is_array($res->results) ? $res->results : [$res->results];

        foreach ($lista as &$item) {
            $cliente = ApiRequest::get('customers', [
                'linkTo'  => 'id_customer',
                'equalTo' => $item->id_customer_agent,
                'select'  => '*',
            ]);

            if (ApiRequest::isSuccess($cliente) && !empty($cliente->results)) {
                $c = is_array($cliente->results) ? $cliente->results[0] : $cliente->results;

                $item->name_customer     = $c->name_customer ?? '';
                $item->lastname_customer = $c->lastname_customer ?? '';
                $item->phone_customer    = $c->phone_customer ?? '';
                $item->email_customer    = $c->email_customer ?? '';
                $item->city_customer     = $c->city_customer ?? '';
            }
        }

        return ['success' => true, 'data' => $lista];
    }

    /* =====================================================
     * APROBAR (validar venta chatbot)
     * ===================================================== */
    public static function aprobarAgente(array $agent)
    {
        if ((int) ($agent['status_agent'] ?? -1) !== self::STATUS_PENDIENTE) {
            return ['success' => false, 'message' => 'Esta venta ya fue procesada'];
        }

        $cantidad = (int) ($agent['quantity_agent'] ?? 0);
        $idRaffle = (int) ($agent['id_raffle_agent'] ?? 0);

        if ($cantidad <= 0 || $idRaffle <= 0) {
            return ['success' => false, 'message' => 'Datos de la venta inválidos'];
        }

        $res = ApiRequest::get('tickets', [
            'linkTo'  => 'id_raffle_ticket,status_ticket',
            'equalTo' => $idRaffle . ',0',
            'select'  => 'id_ticket',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return ['success' => false, 'message' => 'Sin números disponibles'];
        }

        $ticketsDisponibles = is_array($res->results) ? $res->results : [$res->results];

        if (count($ticketsDisponibles) < $cantidad) {
            return ['success' => false, 'message' => 'No hay suficientes números'];
        }

        $resVenta = VentasController::crearVenta([
            'id_customer'         => (int) $agent['id_customer_agent'],
            'id_raffle'           => $idRaffle,
            'quantity_sale'       => $cantidad,
            'total_sale'          => $agent['amount_agent'],
            'code_sale'           => $agent['code_agent'],
            'payment_method_sale' => 'Agente IA',
            'id_admin'            => $_SESSION['user_id'] ?? null,
        ]);

        if (empty($resVenta['success']) || empty($resVenta['id_sale'])) {
            ApiRequest::put(
                self::TABLE . "?id={$agent['id_agent']}&nameId=id_agent&token=no&except=code_agent",
                ['status_agent' => self::STATUS_ERROR]
            );

            return ['success' => false, 'message' => $resVenta['message'] ?? 'Error creando la venta'];
        }

        $update = ApiRequest::put(
            self::TABLE . "?id={$agent['id_agent']}&nameId=id_agent&token=no&except=code_agent",
            ['status_agent' => self::STATUS_VALIDADO]
        );

        if (!ApiRequest::isSuccess($update)) {
            return ['success' => false, 'message' => 'Venta creada pero no se pudo actualizar el estado del agente'];
        }

        $numbersUrl = trim((string) ($agent['url_agent'] ?? ''));
        if ($numbersUrl === '') {
            $numbersUrl = rtrim(BASE_URL, '/') . '/agents/confirm?code=' . rawurlencode($agent['code_agent']);
        }

        $webhook = MeteorWebhookController::notificarVentaValidada([
            'phone_customer' => $agent['phone_customer'] ?? '',
            'id_customer'    => (int) $agent['id_customer_agent'],
            'code_agent'     => $agent['code_agent'],
            'numbers'        => $numbersUrl,
        ]);

        MailController::enviarCorreoVenta((int) $resVenta['id_sale']);

        return [
            'success'        => true,
            'id_sale'        => (int) $resVenta['id_sale'],
            'message'        => 'Venta validada correctamente',
            'webhook'        => $webhook,
            'webhook_ok'     => !empty($webhook['success']),
        ];
    }

    /* =====================================================
     * OBTENER POR CODE (página pública)
     * ===================================================== */
    public static function obtenerPorCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $res = ApiRequest::get(self::TABLE, [
            'linkTo'  => 'code_agent',
            'equalTo' => $code,
            'token'   => 'no',
            'select'  => '*',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return null;
        }

        if (is_array($res->results)) {
            foreach ($res->results as $item) {
                if (($item->code_agent ?? '') === $code) {
                    return (array) $item;
                }
            }
            return null;
        }

        return (array) $res->results;
    }

    /**
     * Recibo público para el cliente (misma plantilla que ventas POS).
     */
    public static function obtenerReciboPublicoPorCodigo(string $code): array
    {
        $agent = self::obtenerPorCode($code);
        if (!$agent) {
            return ['success' => false, 'message' => 'Código no encontrado'];
        }

        if ((int) ($agent['status_agent'] ?? -1) !== self::STATUS_VALIDADO) {
            return ['success' => false, 'message' => 'La venta aún no está validada'];
        }

        $res = ApiRequest::get('sales', [
            'linkTo'  => 'code_sale',
            'equalTo' => $code,
            'select'  => 'id_sale',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return ['success' => false, 'message' => 'Venta no encontrada'];
        }

        $ventaRef = is_array($res->results) ? $res->results[0] : $res->results;
        $idSale = (int) ($ventaRef->id_sale ?? 0);

        if ($idSale <= 0) {
            return ['success' => false, 'message' => 'Venta no encontrada'];
        }

        $venta = VentasController::consultarVenta($idSale);
        if (!$venta) {
            return ['success' => false, 'message' => 'No se pudo cargar la venta'];
        }

        $tickets = VentasController::consultarTicketsVenta($idSale);
        $htmlRecibo = VentasController::generarRecibo($venta, $tickets);

        if (!$htmlRecibo) {
            return ['success' => false, 'message' => 'Error al generar comprobante'];
        }

        return ['success' => true, 'html_recibo' => $htmlRecibo, 'id_sale' => $idSale];
    }

    /* =====================================================
     * RECHAZAR
     * ===================================================== */
    public static function rechazarAgente(array $agent)
    {
        if ((int) ($agent['status_agent'] ?? -1) !== self::STATUS_PENDIENTE) {
            return ['success' => false, 'message' => 'Esta venta ya fue procesada'];
        }

        $update = ApiRequest::put(
            self::TABLE . "?id={$agent['id_agent']}&nameId=id_agent&token=no&except=code_agent",
            ['status_agent' => self::STATUS_RECHAZADO]
        );

        if (!ApiRequest::isSuccess($update)) {
            return ['success' => false, 'message' => 'Error al rechazar'];
        }

        return ['success' => true, 'message' => 'Venta del Agente IA rechazada'];
    }
}
