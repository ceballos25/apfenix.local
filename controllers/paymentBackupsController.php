<?php
require_once __DIR__ . '/clientes.controller.php';
require_once __DIR__ . '/ventas.controller.php';
require_once __DIR__ . '/mail.controller.php';
require_once __DIR__ . '/../includes/coupon.php';
require_once __DIR__ . '/../includes/promo2x1.php';

/**
 * PaymentBackupsController - VERSIÓN FINAL
 */
class PaymentBackupsController
{
    const TABLE_BACKUP = 'payment_backups';

    /* =====================================================
     * CREAR RESPALDO
     * ===================================================== */
        public static function crearRespaldo(array $data)
        {
            if (
                empty($data['id_raffle']) ||
                empty($data['quantity']) ||
                empty($data['amount'])
            ) {
                return [
                    'success' => false,
                    'message' => 'Datos incompletos para crear respaldo'
                ];
            }

            $cantidad = (int)$data['quantity'];

            if ($cantidad < 3) {
                return [
                    'success' => false,
                    'message' => 'La compra mínima es de 3 números'
                ];
            }

            $orderAmount = CouponHelper::resolveOrderAmount(
                (int) $data['id_raffle'],
                $cantidad,
                $data['coupon_code'] ?? null
            );

            if (!$orderAmount['success']) {
                return [
                    'success' => false,
                    'message' => $orderAmount['message']
                ];
            }

            $amount = (int) $orderAmount['amount'];

            $cantidadEntregada = Promo2x1Helper::quantityDelivered($cantidad);

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

            if (count($ticketsDisponibles) < $cantidadEntregada) {
                return [
                    'success' => false,
                    'message' => 'No hay suficientes números disponibles'
                ];
            }

            /* ===============================
            GENERAR CÓDIGO RESPALDO
            =============================== */

            $code = 'PB-' . date('YmdHis') . rand(100, 999);

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
                    'message' => 'No se pudo crear u obtener el cliente'
                ];
            }

            /* ===============================
            CREAR RESPALDO
            =============================== */

            $resBackup = ApiRequest::post(
                self::TABLE_BACKUP . "?token=no&suffix=payment_backup&except=code_payment_backup",
                [
                    'code_payment_backup' => $code,
                    'id_raffle_payment_backup' => (int)$data['id_raffle'],
                    'id_customer_payment_backup' => $idCustomer,
                    'quantity_payment_backup' => $cantidad,
                    'amount_payment_backup' => $amount,
                    'currency_payment_backup' => 'COP',
                    'status_payment_backup' => 1
                ]
            );

            if (!ApiRequest::isSuccess($resBackup)) {
                return ['success' => false, 'message' => 'Error creando respaldo'];
            }

            $idBackup = $resBackup->results->lastId ?? null;

            if (!$idBackup) {
                return ['success' => false, 'message' => 'ID de respaldo inválido'];
            }

            return [
                'success' => true,
                'id_payment_backup' => $idBackup,
                'code_payment_backup' => $code
            ];
        }

    /* =====================================================
     * UTILIDADES
     * ===================================================== */
    public static function obtenerPorCode(string $code)
    {
        $res = ApiRequest::get(self::TABLE_BACKUP, [
            'linkTo' => 'code_payment_backup',
            'equalTo' => $code
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return null;
        }

        return (array)$res->results[0];
    }

    /* =====================================================
     * HELPER PARA LOGS
     * ===================================================== */
    private static function log(string $message): void
    {
        try {
            $logFile = $_SERVER['DOCUMENT_ROOT'] . '/webhooks/openpay.log';
            
            $dir = dirname($logFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            
            @file_put_contents(
                $logFile,
                '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
                FILE_APPEND
            );
        } catch (Exception $e) {
            // Silenciar errores de log
        }
    }

    /* =====================================================
    * APROBAR PAGO - VALIDACIÓN DOBLE POR STATUS
    * ===================================================== */
    public static function aprobarPago(array $backup, array $tx)
{
    // Normalizar estado que envía OpenPay
    $txStatus = strtolower(trim($tx['status'] ?? ''));

    self::log('========== INICIO APROBACIÓN ==========');
    self::log('Status TX: ' . $txStatus);
    self::log('ID Backup: ' . $backup['id_payment_backup']);

    /* =====================================================
    PROTECCIÓN CONTRA WEBHOOK DUPLICADO
    ===================================================== */
    if ((int)$backup['status_payment_backup'] === 2) {
        self::log('⚠️ Este pago ya fue procesado anteriormente');
        return;
    }

    /* =====================================================
    VALIDAR QUE EL BACKUP ESTÉ PENDIENTE
    ===================================================== */
    if ((int)$backup['status_payment_backup'] !== 1) {
        self::log('⚠️ El backup no está en estado pendiente');
        return;
    }

    /* =====================================================
    VALIDAR STATUS DE OPENPAY
    ===================================================== */
    if (!in_array($txStatus, ['completed','paid','in_progress','charge_pending'], true)) {
        self::log('❌ APROBACIÓN BLOQUEADA - Status no es válido: ' . $txStatus);
        return;
    }

    self::log('✓ Status válido para aprobación');

    /* =====================================================
    ACTUALIZAR RESPALDO COMO APROBADO
    ===================================================== */
    ApiRequest::put(
        self::TABLE_BACKUP . "?id={$backup['id_payment_backup']}&nameId=id_payment_backup&token=no&except=code_payment_backup",
        [
            'status_payment_backup' => 2,
            'openpay_status_payment_backup' => $tx['status'],
            'openpay_response_payment_backup' => json_encode($tx)
        ]
    );

    self::log('✓ Respaldo actualizado a APROBADO');

    /* =====================================================
    OBTENER CANTIDAD COMPRADA
    ===================================================== */
    $cantidad = (int)$backup['quantity_payment_backup'];

    if ($cantidad <= 0) {
        self::log('❌ Cantidad inválida');
        return;
    }

    self::log('Cantidad comprada: ' . $cantidad);

    /* =====================================================
    VALIDAR QUE AÚN EXISTAN TICKETS DISPONIBLES
    (solo verificación de seguridad)
    ===================================================== */
    $res = ApiRequest::get("tickets", [
        'linkTo'  => 'id_raffle_ticket,status_ticket',
        'equalTo' => $backup['id_raffle_payment_backup'] . ',0',
        'select'  => 'id_ticket'
    ]);

    if (!ApiRequest::isSuccess($res) || empty($res->results)) {
        self::log('❌ No hay números disponibles');
        return;
    }

    $ticketsDisponibles = is_array($res->results)
        ? $res->results
        : [$res->results];

    if (count($ticketsDisponibles) < Promo2x1Helper::quantityDelivered($cantidad)) {
        self::log('❌ No hay suficientes números disponibles');
        return;
    }

    self::log('Tickets disponibles encontrados: ' . count($ticketsDisponibles));

    /* =====================================================
    CREAR VENTA
    (Aquí es donde se generan los tickets aleatorios)
    ===================================================== */
    $resVenta = VentasController::crearVenta([
        'id_customer' => $backup['id_customer_payment_backup'],
        'id_raffle' => $backup['id_raffle_payment_backup'],
        'quantity_sale' => $cantidad,
        'total_sale' => $backup['amount_payment_backup'],
        'code_sale' => $backup['code_payment_backup'],
        'payment_method_sale' => 'Página Web'
    ]);

    /* =====================================================
    VALIDAR CREACIÓN DE VENTA
    ===================================================== */
    if (!empty($resVenta['success']) && !empty($resVenta['id_sale'])) {

        self::log('✓ Venta creada correctamente');
        self::log('ID Venta: ' . $resVenta['id_sale']);

        /* =====================================================
        ENVIAR CORREO
        ===================================================== */
        MailController::enviarCorreoVenta((int)$resVenta['id_sale']);
        self::log('✓ Correo enviado al cliente');

        /* =====================================================
        LIMPIAR RESPALDO
        ===================================================== */
        self::limpiarRespaldo((int)$backup['id_payment_backup']);
        self::log('✓ Respaldo eliminado');

    } else {

        self::log('❌ Error creando venta');

        /* =====================================================
        MARCAR RESPALDO COMO ERROR
        ===================================================== */
        ApiRequest::put(
            self::TABLE_BACKUP . "?id={$backup['id_payment_backup']}&nameId=id_payment_backup&token=no&except=code_payment_backup",
            [
                'status_payment_backup' => 4
            ]
        );

        self::log('⚠️ Backup marcado como ERROR');
    }

    self::log('========== FIN APROBACIÓN ==========');
}

    /* =====================================================
     * RECHAZAR / CANCELAR
     * NO BORRA NADA, SOLO ACTUALIZA STATUS
     * ===================================================== */
        public static function rechazarPago(array $backup, array $tx)
        {
            try {

                $idBackup = (int)$backup['id_payment_backup'];

                self::log('========== INICIO RECHAZO ==========');
                self::log('ID Backup: ' . $idBackup);

                ApiRequest::put(
                    self::TABLE_BACKUP . "?id={$idBackup}&nameId=id_payment_backup&token=no&except=code_payment_backup",
                    [
                        'status_payment_backup' => 3,
                        'openpay_status_payment_backup' => $tx['status'] ?? 'failed',
                        'openpay_response_payment_backup' => json_encode($tx)
                    ]
                );

                self::log('✓ Respaldo marcado como RECHAZADO');
                self::log('========== FIN RECHAZO ==========');

            } catch (Exception $e) {

                self::log('❌ ERROR: ' . $e->getMessage());

            }
        }

    /* =====================================================
    * LIMPIAR RESPALDO (solo cuando el pago se aprueba)
    * En el nuevo flujo ya NO se reservan tickets antes del pago,
    * por lo tanto solo eliminamos el registro del backup.
    * ===================================================== */
    private static function limpiarRespaldo(int $idBackup): void
    {
        try {

            self::log('========== LIMPIANDO RESPALDO ==========');
            self::log('ID Backup: ' . $idBackup);

            // Eliminar registro del respaldo
            $res = ApiRequest::delete(
                self::TABLE_BACKUP . "?id={$idBackup}&nameId=id_payment_backup&token=no"
            );

            if (ApiRequest::isSuccess($res)) {
                self::log('✓ Respaldo eliminado correctamente');
            } else {
                self::log('⚠️ No se pudo eliminar el respaldo');
            }

            self::log('========== FIN LIMPIEZA RESPALDO ==========');

        } catch (Exception $e) {

            self::log('❌ ERROR LIMPIANDO RESPALDO: ' . $e->getMessage());

        }
    }


}