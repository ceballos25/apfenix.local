<?php

class DashboardController {

    public static function obtenerDashboard() {
        
        $fechaDesde = $_POST['fechaDesde'] ?? date('Y-m-01');
        $fechaHasta = $_POST['fechaHasta'] ?? date('Y-m-d');
        $idRaffle   = $_POST['id_raffle'] ?? '';

        $between1 = $fechaDesde . " 00:00:00";
        $between2 = $fechaHasta . " 23:59:59";

        $response = [
            'kpis' => [
                'totalVentas' => 0,
                'numerosVendidos' => 0,
                'numerosDisponibles' => 0,
                'totalClientes' => 0
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
                'paquetes' => []  // Distribución (Cantidad vs Frecuencia)
            ],
            'ultimasVentas' => []
        ];

        // 1. OBTENER VENTAS
        $paramsSales = [
            'rel'       => 'sales,customers,raffles',
            'type'      => 'sale,customer,raffle',
            'select'    => 'id_sale,total_sale,quantity_sale,date_created_sale,payment_method_sale,name_customer,lastname_customer,phone_customer,city_customer,title_raffle,code_sale',
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

        foreach ($ventas as $v) {
            $monto = floatval($v->total_sale);
            $cantidad = intval($v->quantity_sale);
            $timestamp = strtotime($v->date_created_sale);

            // KPIs
            $response['kpis']['totalVentas'] += $monto;
            $response['kpis']['numerosVendidos'] += $cantidad;

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

        return ['success' => true, 'data' => $response];
    }

    public static function listarRifas() {
        $res = ApiRequest::get("raffles", ["select" => "id_raffle,title_raffle"]);
        return ApiRequest::isSuccess($res) ? ['success' => true, 'data' => $res->results] : ['success' => false];
    }
}
?>