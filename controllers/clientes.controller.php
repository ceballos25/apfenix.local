<?php
class ClientesController {

    const TABLE = 'customers';

    /**
     * 1. OBTENER: Con Búsqueda en Cascada (Waterfall)
     * Soluciona el problema de búsqueda y mantiene la arquitectura.
     */
    public static function obtenerClientes() {
        
        $search = !empty($_POST['search']) ? trim((string)$_POST['search']) : '';
        $status = (isset($_POST['status']) && $_POST['status'] !== '') ? $_POST['status'] : '';

        // Definimos prioridades de búsqueda
        $columnasCandidatas = [];

        if ($search !== '') {
            $telefonoLimpio = preg_replace('/[^0-9]/', '', $search);
            
            if (strpos($search, '@') !== false) {
                $columnasCandidatas = ['email_customer'];
            } elseif (is_numeric($telefonoLimpio) && strlen($telefonoLimpio) >= 3) {
                $columnasCandidatas = ['phone_customer'];
                if (strlen($telefonoLimpio) >= 10) {
                    $search = self::normalizarCelular($telefonoLimpio);
                } else {
                    $search = $telefonoLimpio;
                }
            } else {
                // Primero Nombre, luego Apellido
                $columnasCandidatas = ['name_customer', 'lastname_customer'];
            }
        } else {
            $columnasCandidatas = ['name_customer'];
        }

        $resultFinal = null;

        // Iteramos hasta encontrar datos
        foreach ($columnasCandidatas as $columna) {
            
            $params = [
                'select' => 'id_customer,name_customer,lastname_customer,phone_customer,email_customer,department_customer,city_customer,status_customer',
                'orderBy' => 'id_customer',
                'orderMode' => 'DESC'
            ];

            if ($search !== '' && $status !== '') {
                $params['linkTo'] = $columna . ',status_customer';
                $params['search'] = "$search,$status";
            } elseif ($search !== '') {
                $params['linkTo'] = $columna;
                $params['search'] = $search;
            } elseif ($status !== '') {
                $params['linkTo'] = 'status_customer';
                $params['equalTo'] = $status;
            }

            $apiResult = ApiRequest::get(self::TABLE, $params);

            if (ApiRequest::isSuccess($apiResult) && isset($apiResult->total) && $apiResult->total > 0) {
                $resultFinal = $apiResult;
                break; 
            }
            
            if (!$resultFinal) $resultFinal = $apiResult;
        }

        if (ApiRequest::isSuccess($resultFinal)) {
            $data = $resultFinal->results ?? [];
            return [
                'success' => true,
                'data' => is_array($data) ? $data : [$data],
                'total' => $resultFinal->total ?? 0
            ];
        }

        return ['success' => true, 'data' => []];
    }

    /**
     * 2. CREAR: Validación Estricta (Todo Obligatorio)
     */
    public static function crearCliente($data) {
        // Validamos que NINGÚN campo venga vacío
        if (empty($data['name_customer']) || 
            empty($data['lastname_customer']) || 
            empty($data['phone_customer']) || 
            empty($data['email_customer']) || 
            empty($data['department_customer']) || 
            empty($data['city_customer'])) {
            
            return ['success' => false, 'message' => 'Error: Todos los campos son obligatorios'];
        }

        $datos = [
            'name_customer'       => trim($data['name_customer']),
            'lastname_customer'   => trim($data['lastname_customer']),
            'phone_customer'      => self::normalizarCelular($data['phone_customer']),
            'email_customer'      => trim($data['email_customer']),
            'department_customer' => trim($data['department_customer']),
            'city_customer'       => trim($data['city_customer']),
            'status_customer'     => isset($data['status_customer']) ? (int)$data['status_customer'] : 1
        ];

        //aqui omití el id_customer porque estaba omitiendo el campo de name_customer
        $url = self::TABLE . "?token=no&except=id_customer";
        $result = ApiRequest::post($url, $datos);
        
        return ApiRequest::isSuccess($result) 
            ? ['success' => true, 'message' => 'Cliente creado exitosamente'] 
            : ['success' => false, 'message' => 'Error al crear en BD'];
    }

    /**
     * 3. ACTUALIZAR: Validación Estricta (Todo Obligatorio)
     */
    public static function actualizarCliente($data) {
        if (empty($data['id_customer'])) return ['success' => false, 'message' => 'ID requerido'];

        // Validamos también al actualizar para evitar borrar datos accidentalmente
        if (empty($data['name_customer']) || 
            empty($data['lastname_customer']) || 
            empty($data['phone_customer']) || 
            empty($data['email_customer']) || 
            empty($data['department_customer']) || 
            empty($data['city_customer'])) {
            
            return ['success' => false, 'message' => 'Error: No puedes dejar campos vacíos'];
        }

        $datosActualizar = [
            'name_customer'       => trim($data['name_customer']),
            'lastname_customer'   => trim($data['lastname_customer']),
            'phone_customer'      => self::normalizarCelular($data['phone_customer']),
            'email_customer'      => trim($data['email_customer']),
            'department_customer' => trim($data['department_customer']),
            'city_customer'       => trim($data['city_customer']),
            'status_customer'     => isset($data['status_customer']) ? (int)$data['status_customer'] : 1
        ];

        $url = self::TABLE . "?id=" . $data['id_customer'] . "&nameId=id_customer&token=no&except=name_customer";
        $result = ApiRequest::put($url, $datosActualizar);
        
        return ApiRequest::isSuccess($result) 
            ? ['success' => true, 'message' => 'Cliente actualizado correctamente'] 
            : ['success' => false, 'message' => 'Error al actualizar'];
    }

    /**
     * 4. ELIMINAR (Sin cambios, solo requiere ID)
     */
    public static function eliminarCliente($data) {
        if (empty($data['id_customer'])) return ['success' => false, 'message' => 'ID requerido'];
        
        $url = self::TABLE . "?id=" . $data['id_customer'] . "&nameId=id_customer&token=no&except=name_customer";
        $result = ApiRequest::delete($url);
        
        return ApiRequest::isSuccess($result) 
            ? ['success' => true, 'message' => 'Cliente eliminado'] 
            : ['success' => false, 'message' => 'Error al eliminar'];
    }
    public static function normalizarCelular($raw): string
    {
        $tel = preg_replace('/[^0-9]/', '', (string) $raw);
        if (strlen($tel) >= 12 && substr($tel, 0, 2) === '57') {
            $tel = substr($tel, -10);
        }
        if (strlen($tel) > 10) {
            $tel = substr($tel, -10);
        }
        return $tel;
    }

    /**
     * Busca un cliente por celular de 10 dígitos (ignora 57 y caracteres).
     */
    public static function buscarPorCelular($telefono, $status = '')
    {
        $tel = self::normalizarCelular($telefono);
        if (strlen($tel) !== 10) {
            return ['success' => true, 'data' => []];
        }

        $select = 'id_customer,name_customer,lastname_customer,phone_customer,email_customer,department_customer,city_customer,status_customer';
        $encontrados = [];

        $probar = function (array $params) use ($tel, &$encontrados) {
            $apiResult = ApiRequest::get(self::TABLE, $params);
            if (!ApiRequest::isSuccess($apiResult) || empty($apiResult->total)) {
                return;
            }
            $data = $apiResult->results ?? [];
            $rows = is_array($data) ? $data : [$data];
            foreach ($rows as $row) {
                if (self::normalizarCelular($row->phone_customer ?? '') !== $tel) {
                    continue;
                }
                $encontrados[(int) ($row->id_customer ?? 0)] = $row;
            }
        };

        foreach ([$tel, '57' . $tel] as $variante) {
            $params = [
                'select' => $select,
                'linkTo' => 'phone_customer',
                'equalTo' => $variante,
            ];
            if ($status !== '') {
                $params['linkTo'] = 'phone_customer,status_customer';
                $params['equalTo'] = $variante . ',' . $status;
            }
            $probar($params);
            if ($encontrados) {
                break;
            }
        }

        if (!$encontrados) {
            $params = [
                'select' => $select,
                'linkTo' => 'phone_customer',
                'search' => $tel,
            ];
            $probar($params);
        }

        return [
            'success' => true,
            'data' => array_values($encontrados),
        ];
    }

    /**
     * 5. OBTENER O CREAR CLIENTE: Busca por celular y crea si no existe
     */
    public static function obtenerOCrearCliente(array $data): int
    {
        $res = self::buscarPorCelular($data['phone_customer'] ?? '');

        if (!empty($res['data'])) {
            return (int)$res['data'][0]->id_customer;
        }

        // 2️⃣ Crear cliente si no existe
        $crear = self::crearCliente($data);

        if (!$crear['success']) {
            throw new Exception('No se pudo crear el cliente');
        }

        // 3️⃣ Volver a buscar para obtener el ID
        $res = self::buscarPorCelular($data['phone_customer'] ?? '');

        if (empty($res['data'])) {
            throw new Exception('Cliente creado pero no encontrado');
        }

        return (int)$res['data'][0]->id_customer;
    }

}
?>