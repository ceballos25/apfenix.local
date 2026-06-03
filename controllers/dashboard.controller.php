<?php

class DashboardController {

    public static function obtenerDashboard() {
        
        $fechaDesde = $_POST['fechaDesde'] ?? date('Y-01-01');
        $fechaHasta = $_POST['fechaHasta'] ?? date('Y-12-31');
        $idRaffle   = $_POST['id_raffle'] ?? '';

        $between1 = $fechaDesde . " 00:00:00";
        $between2 = $fechaHasta . " 23:59:59";

        $response = [
            'kpis' => [
                'totalVentas' => 0,
                'numerosVendidos' => 0,
                'numerosDisponibles' => 0,
                'totalClientes' => 0,
                'avanceRifa' => [
                    'titulo'      => '',
                    'porcentaje'  => 0,
                    'vendidos'    => 0,
                    'reservados'  => 0,
                    'total'       => 0,
                    'disponibles' => 0,
                ],
            ],
            'graficas' => [
                'tendencia' => [],
                
                // Las 3 Donas
                'mediosPagoTransacciones' => [],
                'mediosPagoTickets'       => [],
                'mediosPagoDinero'        => [],
                'mediosPagoLabels'        => [],

                'topClientes' => [],
                'topCiudades' => [],

                // NUEVOS GRÁFICOS
                'heatmap'  => [], // Mapa de calor (Día vs Hora)
                'paquetes' => [],  // Distribución (Cantidad vs Frecuencia)
                'ventasPorVendedor'   => [],
                'numerosPorVendedor'  => [],
            ],
            'ultimasVentas' => []
        ];

        // 1. OBTENER VENTAS
        $paramsSales = [
            'rel'       => 'sales,customers,raffles',
            'type'      => 'sale,customer,raffle',
            'select'    => 'id_sale,total_sale,quantity_sale,date_created_sale,payment_method_sale,name_customer,lastname_customer,phone_customer,city_customer,title_raffle,code_sale,id_admin_sale,status_sale',
            'linkTo'    => 'date_created_sale',
            'between1'  => $between1,
            'between2'  => $between2,
            'orderBy'   => 'id_sale',
            'orderMode' => 'DESC',
            'startAt'   => 0,
            'endAt'     => 10000 
        ];

        if (!empty($idRaffle)) {
            $paramsSales['filterTo'] = 'id_raffle_sale';
            $paramsSales['inTo']     = $idRaffle;
        }

        $resSales = ApiRequest::get("relations", $paramsSales);
        $ventas = (ApiRequest::isSuccess($resSales) && !empty($resSales->results)) 
                  ? (is_array($resSales->results) ? $resSales->results : [$resSales->results]) 
                  : [];

        // Estructuras de Datos
        $tendenciaMap = [];
        $mediosTransaccionesMap = [];
        $mediosTicketsMap = [];
        $mediosDineroMap = [];
        $ciudadesMap = [];
        $clientesDetalle = [];
        
        // Estructura para Heatmap (Inicializar días y horas en 0)
        // 1=Lunes ... 7=Domingo
        $heatmapRaw = [];
        for($d=1; $d<=7; $d++) {
            for($h=0; $h<=23; $h++) {
                $heatmapRaw[$d][$h] = 0;
            }
        }

        // Estructura para Paquetes
        $paquetesMap = [];

        // Mapa de admins para resolver nombres (ventas POS guardan id_admin_sale del usuario logueado)
        $adminsMap = self::obtenerMapaAdmins();
        $statsVendedor = [];
        $sinVendedorKey = 0;
        $statsVendedor[$sinVendedorKey] = [
            'nombre'  => 'Sin vendedor (Web)',
            'ventas'  => 0,
            'numeros' => 0,
            'dinero'  => 0.0,
        ];

        foreach ($ventas as $v) {
            if ((int)($v->status_sale ?? 1) !== 1) {
                continue;
            }

            $monto = floatval($v->total_sale);
            $cantidad = intval($v->quantity_sale);
            $timestamp = strtotime($v->date_created_sale);

            // KPIs
            $response['kpis']['totalVentas'] += $monto;
            $response['kpis']['numerosVendidos'] += $cantidad;

            // Stats por vendedor / admin que registró la venta
            $idAdmin = (int)($v->id_admin_sale ?? 0);
            if ($idAdmin <= 0) {
                $idAdmin = $sinVendedorKey;
            } elseif (!isset($statsVendedor[$idAdmin])) {
                $statsVendedor[$idAdmin] = [
                    'nombre'  => self::etiquetaAdminVenta($idAdmin, $adminsMap),
                    'ventas'  => 0,
                    'numeros' => 0,
                    'dinero'  => 0.0,
                ];
            }
            $statsVendedor[$idAdmin]['ventas']++;
            $statsVendedor[$idAdmin]['numeros'] += $cantidad;
            $statsVendedor[$idAdmin]['dinero'] += $monto;

            // Tendencia
            $fecha = date('Y-m-d', $timestamp);
            if (!isset($tendenciaMap[$fecha])) $tendenciaMap[$fecha] = 0;
            $tendenciaMap[$fecha] += $monto;

            // Donas (Medios)
            $metodo = $v->payment_method_sale ?: 'Otros';
            if (!isset($mediosDineroMap[$metodo])) $mediosDineroMap[$metodo] = 0;
            $mediosDineroMap[$metodo] += $monto;

            if (!isset($mediosTicketsMap[$metodo])) $mediosTicketsMap[$metodo] = 0;
            $mediosTicketsMap[$metodo] += $cantidad;

            if (!isset($mediosTransaccionesMap[$metodo])) $mediosTransaccionesMap[$metodo] = 0;
            $mediosTransaccionesMap[$metodo] += 1;

            // Top Clientes
            $nombreFull = $v->name_customer . " " . $v->lastname_customer;
            if (!isset($clientesDetalle[$nombreFull])) {
                $clientesDetalle[$nombreFull] = ['total' => 0, 'cantidad' => 0, 'telefono' => $v->phone_customer ?: 'N/A', 'ciudad' => $v->city_customer ?: 'N/A'];
            }
            $clientesDetalle[$nombreFull]['total'] += $monto;
            $clientesDetalle[$nombreFull]['cantidad'] += $cantidad;

            // Top Ciudades
            $ciudad = strtoupper($v->city_customer ?: 'NO REGISTRADA');
            if (!isset($ciudadesMap[$ciudad])) $ciudadesMap[$ciudad] = 0;
            $ciudadesMap[$ciudad] += $cantidad;

            // --- NUEVO: HEATMAP (Día y Hora) ---
            // N = 1 (Lunes) a 7 (Domingo), G = 0 a 23
            $diaSemana = date('N', $timestamp);
            $horaDia   = intval(date('G', $timestamp));
            $heatmapRaw[$diaSemana][$horaDia]++; // Contamos ventas por hora

            // --- NUEVO: PAQUETES ---
            $keyPaquete = $cantidad . ' Ticket' . ($cantidad > 1 ? 's' : '');
            if (!isset($paquetesMap[$keyPaquete])) $paquetesMap[$keyPaquete] = 0;
            $paquetesMap[$keyPaquete]++;
        }

        $response['ultimasVentas'] = array_slice($ventas, 0, 10);

        // KPIs STOCK & CLIENTES (Consultas ligeras)
        $paramsTickets = ['select' => 'id_ticket', 'linkTo' => 'status_ticket', 'equalTo' => '0', 'startAt' => 0, 'endAt' => 100000];
        if (!empty($idRaffle)) { $paramsTickets['linkTo'] = 'id_raffle_ticket,status_ticket'; $paramsTickets['equalTo'] = $idRaffle . ',0'; }
        $resTickets = ApiRequest::get("tickets", $paramsTickets);
        $response['kpis']['numerosDisponibles'] = (ApiRequest::isSuccess($resTickets) && !empty($resTickets->results)) 
            ? count(is_array($resTickets->results) ? $resTickets->results : [$resTickets->results]) : 0;

        $resCust = ApiRequest::get("customers", ['select' => 'id_customer', 'startAt' => 0, 'endAt' => 100000]);
        $response['kpis']['totalClientes'] = (ApiRequest::isSuccess($resCust) && !empty($resCust->results)) 
            ? count(is_array($resCust->results) ? $resCust->results : [$resCust->results]) : 0;

        $response['kpis']['avanceRifa'] = self::calcularAvanceRifa($idRaffle);

        // --- FORMATEO FINAL ---

        // 1. Tendencia
        ksort($tendenciaMap);
        foreach ($tendenciaMap as $f => $monto) $response['graficas']['tendencia'][] = ['fecha' => $f, 'total' => $monto];

        // 2. Donas
        foreach ($mediosDineroMap as $m => $dinero) {
            $response['graficas']['mediosPagoDinero'][] = $dinero;
            $response['graficas']['mediosPagoTickets'][] = $mediosTicketsMap[$m] ?? 0;
            $response['graficas']['mediosPagoTransacciones'][] = $mediosTransaccionesMap[$m] ?? 0;
            $response['graficas']['mediosPagoLabels'][] = $m;
        }

        // 3. Tops
        uasort($clientesDetalle, function($a, $b) { return $b['total'] - $a['total']; });
        $i = 0;
        foreach ($clientesDetalle as $nombre => $datos) {
            if ($i++ >= 5) break;
            $response['graficas']['topClientes'][] = ['name' => $nombre, 'total' => $datos['total'], 'cantidad' => $datos['cantidad'], 'telefono' => $datos['telefono'], 'ciudad' => $datos['ciudad']];
        }

        arsort($ciudadesMap);
        $j = 0;
        foreach ($ciudadesMap as $ciu => $cant) {
            if ($j++ >= 5) break;
            $response['graficas']['topCiudades'][] = ['name' => $ciu, 'data' => $cant];
        }

        // 4. NUEVO: HEATMAP (Formato ApexCharts)
        $diasLabels = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo'];
        foreach ($diasLabels as $num => $nombreDia) {
            $dataDia = [];
            for($h=0; $h<=23; $h++) {
                $dataDia[] = ['x' => $h . ':00', 'y' => $heatmapRaw[$num][$h]];
            }
            // ApexCharts espera {name: 'Lunes', data: [{x,y}, {x,y}...]}
            $response['graficas']['heatmap'][] = ['name' => $nombreDia, 'data' => $dataDia];
        }

        // 5. NUEVO: PAQUETES (Top 10 más comunes)
        arsort($paquetesMap);
        $k = 0;
        foreach ($paquetesMap as $label => $cant) {
            if ($k++ >= 10) break;
            $response['graficas']['paquetes'][] = ['name' => $label, 'data' => $cant];
        }

        // 6. Ventas y números por vendedor
        $listaVendedores = array_values($statsVendedor);
        usort($listaVendedores, function ($a, $b) {
            return $b['ventas'] <=> $a['ventas'];
        });

        foreach ($listaVendedores as $row) {
            if ($row['ventas'] === 0 && $row['numeros'] === 0) {
                continue;
            }
            $response['graficas']['ventasPorVendedor'][] = [
                'name'   => $row['nombre'],
                'ventas' => $row['ventas'],
                'dinero' => $row['dinero'],
            ];
            $response['graficas']['numerosPorVendedor'][] = [
                'name'    => $row['nombre'],
                'numeros' => $row['numeros'],
            ];
        }

        return ['success' => true, 'data' => $response];
    }

    /**
     * % vendido de la rifa contando solo tickets con status 1 (vendido).
     * Status: 0 = libre, 1 = vendido, 2 = reservado.
     */
    private static function calcularAvanceRifa(string $idRaffle): array
    {
        $vacío = [
            'titulo'      => '',
            'porcentaje'  => 0,
            'vendidos'    => 0,
            'reservados'  => 0,
            'total'       => 0,
            'disponibles' => 0,
        ];

        if (!empty($idRaffle)) {
            $res = ApiRequest::get('raffles', [
                'linkTo'  => 'id_raffle',
                'equalTo' => $idRaffle,
                'select'  => 'id_raffle,title_raffle,digits_raffle',
            ]);
        } else {
            $res = ApiRequest::get('raffles', [
                'linkTo'    => 'status_raffle',
                'equalTo'   => '1',
                'select'    => 'id_raffle,title_raffle,digits_raffle',
                'orderBy'   => 'id_raffle',
                'orderMode' => 'DESC',
                'startAt'   => 0,
                'endAt'     => 1,
            ]);
        }

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return $vacío;
        }

        $rifa = is_array($res->results) ? $res->results[0] : $res->results;
        $idRifa = (int)($rifa->id_raffle ?? 0);
        $digits = (int)($rifa->digits_raffle ?? 0);
        $total  = ($digits > 0) ? (int)pow(10, $digits) : 0;

        if ($idRifa <= 0 || $total <= 0) {
            return $vacío;
        }

        $libres     = self::contarTicketsPorEstado($idRifa, 0);
        $vendidos   = self::contarTicketsPorEstado($idRifa, 1);
        $reservados = self::contarTicketsPorEstado($idRifa, 2);
        $porcentaje = round(($vendidos / $total) * 100, 2);

        return [
            'titulo'      => trim($rifa->title_raffle ?? 'Rifa'),
            'porcentaje'  => $porcentaje,
            'vendidos'    => $vendidos,
            'reservados'  => $reservados,
            'total'       => $total,
            'disponibles' => $libres,
        ];
    }

    private static function contarTicketsPorEstado(int $idRifa, int $status): int
    {
        $res = ApiRequest::get('tickets', [
            'linkTo'  => 'id_raffle_ticket,status_ticket',
            'equalTo' => $idRifa . ',' . $status,
            'select'  => 'id_ticket',
            'startAt' => 0,
            'endAt'   => 100000,
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return 0;
        }

        return count(is_array($res->results) ? $res->results : [$res->results]);
    }

    /**
     * Todos los admins activos: id => [nombre, rol].
     */
    private static function obtenerMapaAdmins(): array
    {
        $res = ApiRequest::get('admins', [
            'linkTo'    => 'status_admin',
            'equalTo'   => '1',
            'select'    => 'id_admin,name_admin,email_admin,rol_admin',
            'orderBy'   => 'name_admin',
            'orderMode' => 'ASC',
            'startAt'   => 0,
            'endAt'     => 5000,
        ]);

        $map = [];
        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return $map;
        }

        $rows = is_array($res->results) ? $res->results : [$res->results];
        foreach ($rows as $a) {
            $id = (int)$a->id_admin;
            $nombre = trim($a->name_admin ?? '') ?: trim($a->email_admin ?? '');
            if ($nombre === '') {
                $nombre = "Usuario #{$id}";
            }
            $map[$id] = [
                'nombre' => $nombre,
                'rol'    => strtolower(trim($a->rol_admin ?? 'administrador')),
            ];
        }

        return $map;
    }

    /**
     * Etiqueta para gráficas: vendedor por nombre; admin POS con sufijo (Admin).
     */
    private static function etiquetaAdminVenta(int $idAdmin, array $adminsMap): string
    {
        if (!isset($adminsMap[$idAdmin])) {
            return "Usuario #{$idAdmin}";
        }

        $admin = $adminsMap[$idAdmin];
        if ($admin['rol'] === Auth::ROLE_VENDEDOR) {
            return $admin['nombre'];
        }

        return $admin['nombre'] . ' (Admin)';
    }

    public static function listarRifas() {
        $res = ApiRequest::get("raffles", ["select" => "id_raffle,title_raffle"]);
        return ApiRequest::isSuccess($res) ? ['success' => true, 'data' => $res->results] : ['success' => false];
    }
}
?>