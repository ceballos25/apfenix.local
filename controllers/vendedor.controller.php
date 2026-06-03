<?php

require_once __DIR__ . '/apiRequest.controller.php';

/**
 * VendedorController — Dashboard y métricas del vendedor autenticado
 */
class VendedorController
{
    /**
     * Progreso diario de meta + resumen del día.
     */
    public static function obtenerDashboard()
    {
        if (!Auth::isVendedor()) {
            return ['success' => false, 'message' => 'Acceso denegado'];
        }

        $userId    = Auth::userId();
        $goalType  = $_SESSION['goal_type_admin'] ?? 'ventas';
        $goalValue = (int)($_SESSION['goal_value_admin'] ?? 0);
        $today     = date('Y-m-d');

        $ventasHoy = self::obtenerVentasDelDia($userId, $today);

        $totalVentas  = count($ventasHoy);
        $totalNumeros = 0;
        $totalDinero  = 0.0;

        foreach ($ventasHoy as $v) {
            $totalNumeros += (int)($v->quantity_sale ?? 0);
            $totalDinero  += (float)($v->total_sale ?? 0);
        }

        $progresoActual = ($goalType === 'numeros') ? $totalNumeros : $totalVentas;
        $porcentaje     = ($goalValue > 0)
            ? min(100, round(($progresoActual / $goalValue) * 100, 1))
            : 0;

        $ultimas = array_slice($ventasHoy, 0, 10);

        return [
            'success' => true,
            'data'    => [
                'nombre'          => $_SESSION['name_admin'] ?? $_SESSION['email_admin'] ?? '',
                'fecha'           => $today,
                'goal_type'       => $goalType,
                'goal_value'      => $goalValue,
                'goal_label'      => $goalType === 'numeros' ? 'números' : 'ventas',
                'progreso_actual' => $progresoActual,
                'porcentaje'      => $porcentaje,
                'total_ventas'    => $totalVentas,
                'total_numeros'   => $totalNumeros,
                'total_dinero'    => $totalDinero,
                'ultimas_ventas'  => $ultimas,
            ],
        ];
    }

    /**
     * Ventas del vendedor para el día indicado (por defecto hoy).
     */
    public static function obtenerVentasDelDia(?int $userId = null, ?string $fecha = null): array
    {
        $userId = $userId ?? Auth::userId();
        $fecha  = $fecha ?? date('Y-m-d');

        if (!$userId) {
            return [];
        }

        $res = ApiRequest::get('relations', [
            'rel'       => 'sales,customers,raffles',
            'type'      => 'sale,customer,raffle',
            'select'    => 'id_sale,code_sale,total_sale,quantity_sale,payment_method_sale,status_sale,date_created_sale,name_customer,lastname_customer,title_raffle,id_admin_sale',
            'linkTo'    => 'id_admin_sale',
            'search'    => (string) $userId,
            'orderBy'   => 'id_sale',
            'orderMode' => 'DESC',
            'startAt'   => 0,
            'endAt'     => 10000,
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return [];
        }

        $ventas = is_array($res->results) ? $res->results : [$res->results];

        return array_values(array_filter($ventas, function ($v) use ($fecha) {
            $fechaVenta = substr($v->date_created_sale ?? '', 0, 10);
            return $fechaVenta === $fecha && (int)($v->status_sale ?? 1) === 1;
        }));
    }
}
