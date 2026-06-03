<?php
require_once __DIR__ . '/clientes.controller.php';
require_once __DIR__ . '/ventas.controller.php';
require_once __DIR__ . '/mail.controller.php';
require_once __DIR__ . '/apiRequest.controller.php';

class TransfersController
{
    const TABLE = 'transfers';

    /* =====================================================
     * CREAR TRANSFERENCIA
     * ===================================================== */
    public static function crearTransferencia(array $data)
    {
        if (
            empty($data['id_raffle']) ||
            empty($data['quantity']) ||
            empty($data['amount'])
        ) {
            return [
                'success' => false,
                'message' => 'Datos incompletos'
            ];
        }

        $cantidad = (int)$data['quantity'];

        if ($cantidad < 3) {
            return [
                'success' => false,
                'message' => 'La compra mínima es de 3 números'
            ];
        }

        /* ===============================
        VALIDAR DISPONIBILIDAD
        =============================== */

        $res = ApiRequest::get("tickets", [
            'linkTo'  => 'id_raffle_ticket,status_ticket',
            'equalTo' => $data['id_raffle'] . ',0',
            'select'  => 'id_ticket'
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return [
                'success' => false,
                'message' => 'No hay números disponibles'
            ];
        }

        $ticketsDisponibles = is_array($res->results)
            ? $res->results
            : [$res->results];

        if (count($ticketsDisponibles) < $cantidad) {
            return [
                'success' => false,
                'message' => 'No hay suficientes números disponibles'
            ];
        }

        /* ===============================
        GENERAR CÓDIGO
        =============================== */

        $code = str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);

        /* ===============================
        CLIENTE
        =============================== */

        $idCustomer = ClientesController::obtenerOCrearCliente([
            'name_customer' => $data['name_customer'],
            'lastname_customer' => $data['lastname_customer'],
            'phone_customer' => $data['phone_customer'],
            'email_customer' => $data['email_customer'],
            'department_customer' => $data['department_customer'],
            'city_customer' => $data['city_customer'],
        ]);

        if (!$idCustomer) {
            return [
                'success' => false,
                'message' => 'Error con cliente'
            ];
        }

        /* ===============================
        CREAR TRANSFERENCIA
        =============================== */

            $resTransfer = ApiRequest::post(
                self::TABLE . "?token=no&table=transfers&suffix=transfer&except=id_transfer",
                [
                    'code_transfer' => $code,
                    'id_raffle_transfer' => (int)$data['id_raffle'],
                    'id_customer_transfer' => $idCustomer,
                    'quantity_transfer' => $cantidad,
                    'amount_transfer' => $data['amount'],
                    'currency_transfer' => 'COP',
                    'status_transfer' => 1
                ]
            );

            if (!ApiRequest::isSuccess($resTransfer)) {
                return [
                    'success' => false,
                    'message' => 'Error creando transferencia',
                    'debug' => $resTransfer
                ];
            }

        $resData = $resTransfer->results ?? null;

            $idTransfer = null;

            if (is_array($resData)) {
                $idTransfer = $resData[0]->id_transfer ?? null;
            } elseif (is_object($resData)) {
                $idTransfer = $resData->id_transfer ?? ($resData->lastId ?? null);
            }

        return [
            'success' => true,
            'id_transfer' => $idTransfer,
            'code_transfer' => $code
        ];
    }

    /* =====================================================
     * OBTENER POR CODE
     * ===================================================== */
    public static function obtenerPorCode(string $code)
{
    $code = trim($code);

    $res = ApiRequest::get(self::TABLE, [
        "linkTo" => "code_transfer",
        "equalTo" => $code,
        "token" => "no",    // <--- Agregado según página 26 de la doc
        "select" => "*"
    ]);

    if (!ApiRequest::isSuccess($res) || empty($res->results)) {
        return null;
    }

    // Si la API devuelve un array, filtramos estrictamente por el código
    if (is_array($res->results)) {
        foreach ($res->results as $item) {
            if ($item->code_transfer === $code) {
                return (array)$item;
            }
        }
        return null; 
    }

    return (array)$res->results;
}

    /* =====================================================
     * APROBAR TRANSFERENCIA
     * ===================================================== */
    public static function aprobarTransferencia(array $transfer)
    {
        if ((int)$transfer['status_transfer'] !== 1) {
            return ['success' => false, 'message' => 'Ya procesado'];
        }

        // 🔥 MARCAR APROBADO
        $update = ApiRequest::put(
            self::TABLE . "?id={$transfer['id_transfer']}&nameId=id_transfer&token=no&except=code_transfer",
            [
                'status_transfer' => 2
            ]
        );

        if (!ApiRequest::isSuccess($update)) {
            return ['success' => false, 'message' => 'Error actualizando estado'];
        }

        $cantidad = (int)$transfer['quantity_transfer'];

        if ($cantidad <= 0) {
            return ['success' => false, 'message' => 'Cantidad inválida'];
        }

        // 🔥 VALIDAR DISPONIBILIDAD
        $res = ApiRequest::get("tickets", [
            'linkTo'  => 'id_raffle_ticket,status_ticket',
            'equalTo' => $transfer['id_raffle_transfer'] . ',0',
            'select'  => 'id_ticket'
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return ['success' => false, 'message' => 'Sin números disponibles'];
        }

        $ticketsDisponibles = is_array($res->results)
            ? $res->results
            : [$res->results];

        if (count($ticketsDisponibles) < $cantidad) {
            return ['success' => false, 'message' => 'No hay suficientes números'];
        }

        // 🔥 CREAR VENTA
        $resVenta = VentasController::crearVenta([
            'id_customer' => $transfer['id_customer_transfer'],
            'id_raffle' => $transfer['id_raffle_transfer'],
            'quantity_sale' => $cantidad,
            'total_sale' => $transfer['amount_transfer'],
            'code_sale' => $transfer['code_transfer'],
            'payment_method_sale' => 'Transferencia',
            'id_admin' => $_SESSION['user_id'] ?? null // 🔥 AQUÍ
        ]);

    if (!empty($resVenta['success']) && !empty($resVenta['id_sale'])) {

        MailController::enviarCorreoVenta((int)$resVenta['id_sale']);

        return [
            'success' => true,
            'id_sale' => (int)$resVenta['id_sale'],
            'message' => 'Venta creada correctamente'
        ];
    }

        // 🔥 FALLÓ → MARCAR ERROR
        ApiRequest::put(
            self::TABLE . "?id={$transfer['id_transfer']}&nameId=id_transfer&token=no&except=code_transfer",
            ['status_transfer' => 4]
        );

        return ['success' => false, 'message' => 'Error creando la venta'];
    }

    /* =====================================================
     * RECHAZAR
     * ===================================================== */
    public static function rechazarTransferencia(array $transfer)
    {
        $update = ApiRequest::put(
            self::TABLE . "?id={$transfer['id_transfer']}&nameId=id_transfer&token=no&except=code_transfer",
            [
                'status_transfer' => 3
            ]
        );

        if (!ApiRequest::isSuccess($update)) {
            return ['success' => false, 'message' => 'Error al rechazar'];
        }

        return [
            'success' => true,
            'message' => 'Transferencia rechazada'
        ];
    }

    public static function obtenerTransferencias()
    {
        $res = ApiRequest::get(self::TABLE, [
            "select" => "*",
            "linkTo" => "status_transfer",
            "equalTo" => "1",
            "orderBy" => "id_transfer",
            "orderMode" => "DESC"
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return ['success' => true, 'data' => []];
        }

        $lista = is_array($res->results) ? $res->results : [$res->results];

        foreach ($lista as &$t) {

            // 🔥 TRAER CLIENTE
            $cliente = ApiRequest::get("customers", [
                "linkTo" => "id_customer",
                "equalTo" => $t->id_customer_transfer,
                "select" => "*"
            ]);

            if (ApiRequest::isSuccess($cliente) && !empty($cliente->results)) {
                $c = is_array($cliente->results) ? $cliente->results[0] : $cliente->results;

                $t->name_customer = $c->name_customer ?? '';
                $t->lastname_customer = $c->lastname_customer ?? '';
                $t->phone_customer = $c->phone_customer ?? '';
                $t->email_customer = $c->email_customer ?? '';
                $t->city_customer = $c->city_customer ?? '';
            }
        }

        return ['success' => true, 'data' => $lista];
    }   
}