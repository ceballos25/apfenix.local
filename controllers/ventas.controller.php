<?php

require_once 'mail.controller.php';

/**
 * VentasController
 * 
 * Controlador para gestión de ventas de rifas
 * Maneja consultas, filtrado y generación de reportes
 * 
 * @author Tu Nombre
 * @version 1.0
 */
class VentasController {
    
    const TABLE = 'sales';
    
    /**
     * Obtiene ventas filtradas según criterios
     * 
     * Acepta filtros por:
     * - Búsqueda universal (nombre, teléfono, email, código)
     * - Rango de fechas o período predefinido
     * - Método de pago
     * - Rifa específica
     * 
     * @return array ['success' => bool, 'data' => array]
     */
    public static function obtenerVentas() {

        // Campos requeridos para la vista
        $select = 'id_sale,code_sale,total_sale,payment_method_sale,status_sale,date_created_sale,quantity_sale,name_customer,lastname_customer,phone_customer,email_customer,city_customer,title_raffle,id_admin_sale';

        // Capturar filtros
        $filtros = self::obtenerFiltros();
        
        // Determinar columnas de búsqueda
        $columnas = self::determinarColumnasBusqueda($filtros['search']);
        
        // Ejecutar búsqueda con filtros
        $ventas = self::ejecutarBusqueda($columnas, $filtros, $select);
        
        // Aplicar filtro de fechas local (API no lo soporta en relaciones)
        if ($filtros['dateFrom'] && $filtros['dateTo']) {
            $ventas = self::filtrarPorFechas($ventas, $filtros['dateFrom'], $filtros['dateTo']);
        }

        // 🔥 AGREGAR EMAIL DEL ADMIN (VENDEDOR)
        foreach ($ventas as &$v) {

            if (!empty($v->id_admin_sale)) {

                $admin = ApiRequest::get("admins", [
                    "linkTo" => "id_admin",
                    "equalTo" => $v->id_admin_sale,
                    "select" => "email_admin"
                ]);

                if (ApiRequest::isSuccess($admin) && !empty($admin->results)) {

                    $a = is_array($admin->results) ? $admin->results[0] : $admin->results;

                    $v->email_admin = $a->email_admin ?? '';
                } else {
                    $v->email_admin = '';
                }

            } else {
                $v->email_admin = '';
            }
        }

        return ['success' => true, 'data' => array_values($ventas)];
    }

    /**
     * Lista todas las rifas activas
     * 
     * @return array ['success' => bool, 'data' => array]
     */
    public static function listarRifas() {
        $res = ApiRequest::get("raffles", ["select" => "id_raffle,title_raffle"]);
        return ApiRequest::isSuccess($res) 
            ? ['success' => true, 'data' => $res->results] 
            : ['success' => false];
    }

    /**
     * Obtiene tickets disponibles de una rifa
     * 
     * @param int $idRaffle ID de la rifa
     * @return array ['success' => bool, 'data' => array]
     */
    public static function obtenerTicketsDisponibles($idRaffle) {
        $params = [
            'linkTo' => 'id_raffle_ticket,status_ticket',
            'equalTo' => $idRaffle . ",0",
            'select' => 'id_ticket,number_ticket'
            //'orderBy' => 'number_ticket',
            //'orderMode' => 'ASC'
        ];
        
        $result = ApiRequest::get('tickets', $params);
        $data = ApiRequest::isSuccess($result) ? ($result->results ?? []) : [];
        
            // mezclar 15-02-2025
            if (!empty($data)) {
                shuffle($data);
            }        
        
        return [
            'success' => true,
            'data' => is_array($data) ? $data : [$data]
        ];
    }

    /**
     * Crea una nueva venta
     * 
     * Maneja:
     * - Creación o búsqueda de cliente
     * - Registro de venta
     * - Asignación de tickets al cliente
     * 
     * @param array $data Datos de la venta
     * @return array ['success' => bool, 'id_sale' => int|null, 'message' => string|null]
    *se debe corregir para evitar errores antes de la compra, 
    */
    public static function crearVenta($data)
    {
        $cantidad = (int)($data['quantity_sale'] ?? 0);
        $idRaffle = (int)($data['id_raffle'] ?? 0);

        if ($cantidad <= 0 || $idRaffle <= 0) {
            return [
                'success' => false,
                'message' => 'Datos inválidos para crear venta'
            ];
        }

        /* ===============================
        BUSCAR TICKETS DISPONIBLES
        =============================== */

        $res = ApiRequest::get("tickets", [
            'linkTo'  => 'id_raffle_ticket,status_ticket',
            'equalTo' => $idRaffle . ',0',
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
        SELECCIÓN ALEATORIA
        =============================== */

        shuffle($ticketsDisponibles);

        $ticketsSeleccionados = array_slice($ticketsDisponibles, 0, $cantidad);

        $ticketIds = array_map(function($t){
            return $t->id_ticket;
        }, $ticketsSeleccionados);

        /* ===============================
        OBTENER O CREAR CLIENTE
        =============================== */

        $idCliente = self::obtenerOCrearCliente($data);

        if (!$idCliente) {
            return [
                'success' => false,
                'message' => 'Error al procesar cliente'
            ];
        }

        /* ===============================
        CREAR VENTA
        =============================== */

        $datosVenta = [
            'id_customer_sale'    => (int)$idCliente,
            'id_raffle_sale'      => $idRaffle,
            'code_sale'           => $data['code_sale'],
            'quantity_sale'       => $cantidad,
            'total_sale'          => $data['total_sale'],
            'payment_method_sale' => $data['payment_method_sale'],
            'status_sale'         => 1,
            'id_admin_sale' => $data['id_admin'] ?? $_SESSION['user_id'] ?? null
        ];

        $resVenta = ApiRequest::post(
            self::TABLE . "?token=no&suffix=sale&except=code_sale",
            $datosVenta
        );

        if (!ApiRequest::isSuccess($resVenta)) {
            return [
                'success' => false,
                'message' => 'Error al crear venta'
            ];
        }

        $idVenta = $resVenta->results->lastId ?? $resVenta->results;

        /* ===============================
        MARCAR TICKETS COMO VENDIDOS
        =============================== */

        foreach ($ticketIds as $idTicket) {

            ApiRequest::put(
                "tickets?id=$idTicket&nameId=id_ticket&token=no&except=number_ticket",
                [
                    'status_ticket'      => 1,
                    'id_customer_ticket' => (int)$idCliente,
                    'id_sale_ticket'     => (int)$idVenta
                ]
            );
        }

        /* ===============================
        ENVIAR CORREO
        =============================== */

        MailController::enviarCorreoVenta((int)$idVenta);

        return [
            'success' => true,
            'id_sale' => $idVenta
        ];
    }
            

    /**
     * Obtiene el detalle completo de una venta
     * Incluye información del cliente, tickets comprados y plantilla de recibo
     * 
     * @param int $idVenta ID de la venta
     * @return array ['success' => bool, 'html_recibo' => string|null]
     */
    public static function obtenerDetalleVenta($idVenta) {
        // Obtener venta
        $venta = self::consultarVenta($idVenta);
        if (!$venta) {
            return ['success' => false, 'message' => 'Venta no encontrada'];
        }
        
        // Obtener tickets
        $tickets = self::consultarTicketsVenta($idVenta);
        
        // Generar recibo
        $htmlRecibo = self::generarRecibo($venta, $tickets);
        
        if (!$htmlRecibo) {
            return ['success' => false, 'message' => 'Error al generar recibo'];
        }
        
        return ['success' => true, 'html_recibo' => $htmlRecibo];
    }

    /* ==========================================
     * MÉTODOS PRIVADOS
     * ========================================== */

    /**
     * Obtiene y normaliza filtros desde $_POST
     */
    public static function obtenerFiltros() {
        $search = trim($_POST['search'] ?? '');
        $idRaffle = $_POST['id_raffle'] ?? '';
        $fechaInicio = $_POST['fecha_inicio'] ?? '';
        $fechaFin = $_POST['fecha_fin'] ?? '';
        $periodo = $_POST['periodo'] ?? '';
        $metodoPago = $_POST['payment_method'] ?? '';
        $idAdmin = $_POST['id_admin'] ?? '';

        // Calcular fechas
        [$dateFrom, $dateTo] = self::calcularRangoFechas($fechaInicio, $fechaFin, $periodo);

        return compact('search', 'idRaffle', 'metodoPago', 'dateFrom', 'dateTo', 'idAdmin');
    }

public static function obtenerAdmins() {

    $res = ApiRequest::get("admins", [
        "select" => "id_admin,email_admin"
    ]);

    if (!ApiRequest::isSuccess($res) || empty($res->results)) {
        return ['success' => true, 'data' => []];
    }

    return [
        'success' => true,
        'data' => is_array($res->results) ? $res->results : [$res->results]
    ];
}    

    /**
     * Calcula rango de fechas según entrada manual o período
     */
    public static function calcularRangoFechas($fechaInicio, $fechaFin, $periodo) {
        if ($fechaInicio && $fechaFin) {
            return [$fechaInicio, $fechaFin];
        }
        
        if (!$periodo) {
            return [null, null];
        }

        $hoy = date('Y-m-d');
        
        $rangos = [
            'today' => [$hoy, $hoy],
            'yesterday' => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
            'week' => [date('Y-m-d', strtotime('monday this week')), $hoy],
            'month' => [date('Y-m-01'), date('Y-m-t')],
            'year' => [date('Y-01-01'), date('Y-12-31')]
        ];

        return $rangos[$periodo] ?? [null, null];
    }

    /**
     * Determina columnas de búsqueda según el texto ingresado
     */
    public static function determinarColumnasBusqueda($search) {

        if (empty($search)) {
            return ['id_sale'];
        }

        // 🔥 BUSCAR EN TODO (INCLUYE CÓDIGO SIEMPRE)
        if (strpos($search, '@') !== false) {
            return ['email_customer', 'code_sale'];
        }

        if (is_numeric($search)) {
            return ['phone_customer', 'id_sale', 'code_sale'];
        }

        return ['name_customer', 'lastname_customer', 'code_sale'];
    }

    /**
     * Ejecuta búsqueda con múltiples columnas (OR)
     */
    public static function ejecutarBusqueda($columnas, $filtros, $select) {
        $acumulado = [];

        foreach ($columnas as $columna) {
            $params = [
                'rel' => 'sales,customers,raffles,admins',
                'type' => 'sale,customer,raffle,admin',
                'select' => $select,
                'orderBy' => 'id_sale',
                'orderMode' => 'DESC'
            ];

            $linkTo = [];
            $searchTo = [];

            // 🔥 FILTRO POR VENDEDOR (ADMIN)
            if (!empty($filtros['idAdmin'])) {
                $linkTo[] = 'id_admin_sale';
                $searchTo[] = $filtros['idAdmin'];
            }

            // Búsqueda de texto
            if (!empty($filtros['search'])) {
                $linkTo[] = $columna;
                $searchTo[] = $filtros['search'];
            }

            // Filtro por rifa
            if (!empty($filtros['idRaffle'])) {
                $linkTo[] = 'id_raffle_sale';
                $searchTo[] = $filtros['idRaffle'];
            }

            // Filtro por método de pago
            if (!empty($filtros['metodoPago'])) {
                $linkTo[] = 'payment_method_sale';
                $searchTo[] = $filtros['metodoPago'];
            }

            if (!empty($linkTo)) {
                $params['linkTo'] = implode(',', $linkTo);
                $params['search'] = implode(',', $searchTo);
            }

            $res = ApiRequest::get("relations", $params);

            if (ApiRequest::isSuccess($res) && !empty($res->results)) {
                $resultados = is_array($res->results) ? $res->results : [$res->results];
                foreach ($resultados as $r) {
                    $acumulado[$r->id_sale] = $r;
                }
            }
        }

        return $acumulado;
    }

    /**
     * Filtra ventas por rango de fechas (filtrado local)
     */
    public static function filtrarPorFechas($ventas, $dateFrom, $dateTo) {
        return array_filter($ventas, function($venta) use ($dateFrom, $dateTo) {
            $fechaVenta = substr($venta->date_created_sale, 0, 10);
            return $fechaVenta >= $dateFrom && $fechaVenta <= $dateTo;
        });
    }

    /**
     * Obtiene ID de cliente existente o crea uno nuevo
     */
    public static function obtenerOCrearCliente($data) {
        $idCliente = !empty($data['id_customer']) ? $data['id_customer'] : null;
        
        if ($idCliente) {
            return $idCliente;
        }

        // Normalizar teléfono
        $phone = preg_replace('/[^0-9]/', '', $data['phone_customer']);
        if (strpos($phone, '57') === 0 && strlen($phone) == 12) {
            $phone = substr($phone, 2);
        }

        // Buscar cliente por teléfono
        $searchC = ApiRequest::get("customers", [
            "linkTo" => "phone_customer",
            "equalTo" => $phone,
            "select" => "id_customer"
        ]);

        if (ApiRequest::isSuccess($searchC) && !empty($searchC->results)) {
            return $searchC->results[0]->id_customer;
        }

        // Crear nuevo cliente
        $datosCliente = [
            'name_customer' => ucwords(strtolower($data['name_customer'])),
            'lastname_customer' => ucwords(strtolower($data['lastname_customer'])),
            'phone_customer' => $phone,
            'email_customer' => $data['email_customer'],
            'department_customer' => $data['department_customer'],
            'city_customer' => $data['city_customer'],
            'status_customer' => 1
        ];

        $resC = ApiRequest::post("customers?token=no&suffix=customer&except=name_customer", $datosCliente);
        
        return ApiRequest::isSuccess($resC) ? $resC->results->lastId : null;
    }


    /**
     * Consulta información de una venta
     */
    public static function consultarVenta($idVenta) {
        $params = [
            'rel' => 'sales,customers,raffles',
            'type' => 'sale,customer,raffle',
            'select' => 'id_sale,date_created_sale,total_sale,name_customer,lastname_customer,code_sale,quantity_sale,title_raffle,email_customer',
            'linkTo' => 'id_sale',
            'equalTo' => $idVenta
        ];

        $res = ApiRequest::get("relations", $params);
        
        return (ApiRequest::isSuccess($res) && !empty($res->results)) 
            ? $res->results[0] 
            : null;
    }

    /**
     * Consulta tickets de una venta
     */
    public static function consultarTicketsVenta($idVenta) {
        $res = ApiRequest::get('tickets', [
            'linkTo' => 'id_sale_ticket',
            'equalTo' => $idVenta,
            'select' => 'number_ticket'
        ]);

        return is_array($res->results) ? $res->results : [$res->results];
    }

    /**
     * Genera HTML del recibo usando plantilla externa
     */
    public static function generarRecibo($venta, $tickets) {
        $rutaPlantilla = dirname(__DIR__) . "/includes/templeate-ticket.php";
        
        if (!file_exists($rutaPlantilla)) {
            return null;
        }

        // Formatear fecha
        $fecha = new DateTime($venta->date_created_sale);
        $fecha->setTimezone(new DateTimeZone('America/Bogota'));
        $fechaFormateada = $fecha->format('d/m/Y h:i A');

        $numerosGanadores = [
            '30405' => true,
            '00007' => true,
            '30068' => true,
            '26034' => true,
            '77777' => true,
            '82041' => true,
            '12998' => true,
            '95585' => true,
            '57001' => true,
            '53760' => true
        ];

        $htmlTickets = '';

        shuffle($tickets);

        foreach ($tickets as $t) {

            $numero = $t->number_ticket; // 👈 ESTE ES EL FIX

            $esGanador = isset($numerosGanadores[$numero]);

            $bg     = $esGanador ? '#198754' : '#f5f5f5';
            $color  = $esGanador ? '#ffffff' : '#000000';
            $border = $esGanador ? '#198754' : '#ddd';

            $htmlTickets .= '<span style="
                display:inline-block;
                margin:3px;
                padding:6px 11px;
                background:' . $bg . ';
                color:' . $color . ';
                border:1px solid ' . $border . ';
                border-radius:6px;
                font-weight:bold;
            ">' . $numero . '</span>';
        }

        // Cargar plantilla
        $template = file_get_contents($rutaPlantilla);

        // Reemplazar variables
        $reemplazos = [
            '{Nombre Cliente}' => trim($venta->name_customer . " " . $venta->lastname_customer),
            '{ID}' => $venta->id_sale,
            '{Fecha}' => $fechaFormateada,
            '{Cantidad}' => $venta->quantity_sale,
            '{Codigo}' => $venta->code_sale,
            '{NumerosHTML}' => $htmlTickets,
            '{Total}' => '$' . number_format($venta->total_sale, 0, ',', '.')
        ];

        return str_replace(array_keys($reemplazos), array_values($reemplazos), $template);
    }

    public static function obtenerNumerosVendidos() {

            $search = trim($_POST['search'] ?? '');
            $idRaffle = $_POST['id_raffle'] ?? '';
            $fechaInicio = $_POST['fecha_inicio'] ?? '';
            $fechaFin = $_POST['fecha_fin'] ?? '';
            $periodo = $_POST['periodo'] ?? '';

            [$dateFrom, $dateTo] = self::calcularRangoFechas($fechaInicio, $fechaFin, $periodo);

            // AGREGADO: city_customer
            $select = '
                id_ticket,
                number_ticket,
                id_sale_ticket,
                date_created_sale,
                name_customer,
                lastname_customer,
                phone_customer,
                email_customer,
                city_customer, 
                title_raffle,
                code_sale
            ';

            // Búsqueda inteligente (Igual que antes)
            if ($search !== '') {
                if (is_numeric($search)) {
                    $columnas = ['number_ticket'];
                } elseif (strpos($search, '@') !== false) {
                    $columnas = ['email_customer'];
                } else {
                    $columnas = ['name_customer', 'lastname_customer', 'code_sale'];
                }
            } else {
                $columnas = ['number_ticket'];
            }

            $acumulado = [];

            foreach ($columnas as $col) {
                $params = [
                    'rel' => 'tickets,sales,customers,raffles',
                    'type' => 'ticket,sale,customer,raffle',
                    'select' => $select,
                    'orderBy' => 'number_ticket',
                    'orderMode' => 'ASC',
                    'linkTo' => 'status_ticket',
                    'equalTo' => '1'
                ];

                // Búsqueda
                if ($search !== '') {
                    $params['linkTo'] .= ',' . $col;
                    $params['equalTo'] .= ',' . $search;
                }

                // Filtro por rifa
                if ($idRaffle) {
                    $params['linkTo'] .= ',id_raffle_ticket';
                    $params['equalTo'] .= ',' . $idRaffle;
                }

                $res = ApiRequest::get("relations", $params);

                if (ApiRequest::isSuccess($res) && !empty($res->results)) {
                    $rows = is_array($res->results) ? $res->results : [$res->results];
                    foreach ($rows as $r) {
                        $acumulado[$r->id_ticket] = $r;
                    }
                }
            }

            // Filtro por fechas (local)
            if ($dateFrom && $dateTo) {
                $acumulado = array_filter($acumulado, function($t) use ($dateFrom, $dateTo) {
                    $fecha = substr($t->date_created_sale, 0, 10);
                    return $fecha >= $dateFrom && $fecha <= $dateTo;
                });
            }

            return ['success' => true, 'data' => array_values($acumulado)];
        }



            public static function obtenerVentaPorCodigo(string $codeSale)
            {
                $params = [
                    'rel'   => 'sales,customers,raffles',
                    'type'  => 'sale,customer,raffle',
                    'select'=> '
                        id_sale,
                        code_sale,
                        total_sale,
                        quantity_sale,
                        date_created_sale,
                        payment_method_sale,
                        name_customer,
                        lastname_customer,
                        email_customer,
                        phone_customer,
                        city_customer,
                        title_raffle
                    ',
                    'linkTo'=> 'code_sale',
                    'equalTo'=> $codeSale
                ];

                $res = ApiRequest::get('relations', $params);

                if (!ApiRequest::isSuccess($res) || empty($res->results)) {
                    return ['success' => false, 'message' => 'Venta no encontrada'];
                }

                $venta = $res->results[0];

                // Traer tickets
                $tickets = ApiRequest::get('tickets', [
                    'linkTo'  => 'id_sale_ticket',
                    'equalTo' => $venta->id_sale,
                    'select'  => 'number_ticket'
                ]);

                return [
                    'success' => true,
                    'venta'   => $venta,
                    'tickets' => is_array($tickets->results)
                        ? $tickets->results
                        : [$tickets->results]
                ];
            }


    public static function buscarTicketsPorCelular($phoneCustomer)
    {
        $phone = preg_replace('/\D/', '', (string)$phoneCustomer);

        if (!preg_match('/^\d{10}$/', $phone)) {
            return [
                'success' => false,
                'message' => 'El celular debe contener exactamente 10 dígitos'
            ];
        }

        $params = [
            'rel' => 'sales,customers,raffles',
            'type' => 'sale,customer,raffle',
            'select' => '
                id_sale,
                code_sale,
                total_sale,
                quantity_sale,
                date_created_sale,
                payment_method_sale,
                name_customer,
                lastname_customer,
                email_customer,
                phone_customer,
                city_customer,
                title_raffle
            ',
            'linkTo' => 'phone_customer',
            'equalTo' => $phone,
            'orderBy' => 'id_sale'
        
        ];

        $res = ApiRequest::get('relations', $params);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return [
                'success' => false,
                'message' => 'No encontrado'
            ];
        }

        $ventas = is_array($res->results) ? $res->results : [$res->results];

        foreach ($ventas as $venta) {
            $ticketsRes = ApiRequest::get('tickets', [
                'linkTo' => 'id_sale_ticket',
                'equalTo' => $venta->id_sale,
                'select' => 'number_ticket'
            ]);

            $tickets = ApiRequest::isSuccess($ticketsRes) && !empty($ticketsRes->results)
                ? (is_array($ticketsRes->results) ? $ticketsRes->results : [$ticketsRes->results])
                : [];

            $venta->tickets = $tickets;
        }

        $html = '';
        foreach ($ventas as $venta) {
            $html .= self::generarRecibo($venta, $venta->tickets);
        }

        return [
            'success' => true,
            'html' => $html
        ];
    }
    public static function anularVenta($id_sale)
    {
        if (empty($id_sale)) {
            return ['success' => false, 'message' => 'ID inválido'];
        }

        // 🔹 1. LIBERAR TICKETS
        $resTickets = ApiRequest::get("tickets", [
            'linkTo'  => 'id_sale_ticket',
            'equalTo' => $id_sale,
            'select'  => 'id_ticket'
        ]);

        if (ApiRequest::isSuccess($resTickets) && !empty($resTickets->results)) {
            $tickets = is_array($resTickets->results) ? $resTickets->results : [$resTickets->results];

            foreach ($tickets as $t) {
                // Importante: asegurar que el PUT sea exitoso antes de seguir
                ApiRequest::put(
                    "tickets?id={$t->id_ticket}&nameId=id_ticket&token=no&except=id_ticket",
                    [
                        'status_ticket'      => 0,
                        'id_customer_ticket' => "null",
                        'id_sale_ticket'     => "null"
                    ]
                );
            }
        }

        // 🔹 2. ELIMINAR VENTA
        // Verifica que $_SESSION['token_admin'] tenga valor real
        $token = $_SESSION['token_admin'] ?? null;
        
        if(!$token){
            return ['success' => false, 'message' => 'Sesión expirada o sin token'];
        }

        $urlDelete = "sales?id={$id_sale}"
                    . "&nameId=id_sale"
                    . "&token={$token}"
                    . "&table=admins"
                    . "&suffix=admin";

        $delete = ApiRequest::delete($urlDelete);

        if (!ApiRequest::isSuccess($delete)) {
            return [
                'success' => false,
                'message' => 'La API rechazó la eliminación',
                'error_api' => $delete->results ?? 'Error desconocido', // Mira qué error devuelve MySQL
                'url_enviada' => $urlDelete
            ];
        }

        return ['success' => true];
    }

    }

 

    

