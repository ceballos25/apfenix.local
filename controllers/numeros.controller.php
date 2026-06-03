<?php

class NumerosController {

    const TABLE = 'tickets';

    /* =============================================================
     * MÉTODOS DE GESTIÓN (Vista Nueva: numeros.php)
     * ============================================================= */

    public static function obtenerInventario() {
        // ... (El código de inventario que ya te di y funciona bien) ...
        $idRaffle = $_POST['id_raffle'] ?? '';
        $search   = trim($_POST['search'] ?? '');
        $estado   = $_POST['status'] ?? ''; 

        if (empty($idRaffle)) return ['success' => true, 'data' => []];

        $params = [
            'linkTo'    => 'id_raffle_ticket',
            'equalTo'   => $idRaffle,
            'select'    => 'id_ticket,number_ticket,status_ticket,id_raffle_ticket',
            'orderBy'   => 'number_ticket',
            'orderMode' => 'ASC',
            'startAt'   => 0,
            'endAt'     => 100000 // Traer todos
        ];

        if ($estado !== '') {
            $params['linkTo']  .= ',status_ticket';
            $params['equalTo'] .= ',' . $estado;
        }

        if ($search !== '') {
            $params['linkTo']  .= ',number_ticket';
            $params['equalTo'] .= ',' . $search;
        }

        $res = ApiRequest::get(self::TABLE, $params);
        $data = (ApiRequest::isSuccess($res) && !empty($res->results)) ? (is_array($res->results) ? $res->results : [$res->results]) : [];
        
        // nuevo reordenamiento 15-02-2025
        if (!empty($data)) {
            shuffle($data);
        }

        return ['success' => true, 'data' => $data];
    }

    public static function cambiarEstado() {
        // ... (Tu código de cambiar estado) ...
        $idTicket = $_POST['id_ticket'];
        $nuevoEstado = $_POST['status']; 
        
        $check = ApiRequest::get(self::TABLE, ['linkTo' => 'id_ticket', 'equalTo' => $idTicket, 'select' => 'status_ticket']);
        if(ApiRequest::isSuccess($check) && !empty($check->results)) {
            $actual = is_array($check->results) ? $check->results[0] : $check->results;
            if ($actual->status_ticket == 1) return ['success' => false, 'message' => 'No se puede modificar un número vendido.'];
        }

        $data = ['status_ticket' => $nuevoEstado];
        $res = ApiRequest::put("tickets?id=$idTicket&nameId=id_ticket&token=no&except=number_ticket", $data);
        return ['success' => ApiRequest::isSuccess($res)];
    }

    public static function listarRifas() {
        $res = ApiRequest::get("raffles", ["select" => "id_raffle,title_raffle"]);
        return ApiRequest::isSuccess($res) ? ['success' => true, 'data' => $res->results] : ['success' => false];
    }

    /* =============================================================
     * MÉTODOS DE CONSULTA (Vista Antigua: numeros-vendidos.php)
     * RECUPERADO Y ADAPTADO
     * ============================================================= */

    public static function obtenerNumerosVendidos() {
        
        $search = trim($_POST['search'] ?? '');
        $idRaffle = $_POST['id_raffle'] ?? '';
        
        // Configuración de campos detallados para la tabla de reporte
        $select = '
            id_ticket, number_ticket, id_sale_ticket, date_created_sale,
            name_customer, lastname_customer, phone_customer, email_customer, city_customer, 
            title_raffle, code_sale
        ';

        // Lógica de columnas de búsqueda
        $columnas = ['number_ticket']; // Por defecto
        if ($search !== '') {
            if (is_numeric($search)) $columnas = ['number_ticket'];
            elseif (strpos($search, '@') !== false) $columnas = ['email_customer'];
            else $columnas = ['name_customer', 'lastname_customer', 'code_sale'];
        }

        $acumulado = [];

        foreach ($columnas as $col) {
            $params = [
                'rel'       => 'tickets,sales,customers,raffles',
                'type'      => 'ticket,sale,customer,raffle',
                'select'    => $select,
                'orderBy'   => 'number_ticket',
                'orderMode' => 'ASC',
                'linkTo'    => 'status_ticket',
                'equalTo'   => '1' // Solo vendidos
            ];

            if ($search !== '') {
                $params['linkTo'] .= ',' . $col;
                $params['equalTo'] .= ',' . $search;
            }

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

        return ['success' => true, 'data' => array_values($acumulado)];
    }
}
?>