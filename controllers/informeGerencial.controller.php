<?php

require_once __DIR__ . '/apiRequest.controller.php';
require_once ROOT_PATH . '/includes/informe.logger.php';
require_once ROOT_PATH . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * InformeGerencialController
 * Genera y envía informes PDF de ventas por vendedor.
 */
class InformeGerencialController
{
    const TIPO_MEDIODIA = 'mediodia';
    const TIPO_CIERRE   = 'cierre';

    /**
     * Punto de entrada del cron.
     */
    public static function ejecutarEnvio(string $tipo): bool
    {
        if (!INFORME_GERENCIAL_ENABLED) {
            InformeLogger::info("Informe deshabilitado (INFORME_GERENCIAL_ENABLED=false). Tipo: {$tipo}");
            return false;
        }

        if (empty(CORREO_INFORME)) {
            InformeLogger::error('CORREO_INFORME no configurado en .env-ap');
            return false;
        }

        if (!in_array($tipo, [self::TIPO_MEDIODIA, self::TIPO_CIERRE], true)) {
            InformeLogger::error("Tipo de informe inválido: {$tipo}");
            return false;
        }

        InformeLogger::info("Iniciando informe gerencial: {$tipo}");

        try {
            $datos = self::construirDatosInforme($tipo);
            $pdfPath = self::generarPdf($datos);

            $enviado = self::enviarConReintentos($pdfPath, $datos);

            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            if ($enviado) {
                InformeLogger::info("Informe {$tipo} enviado a " . CORREO_INFORME);
            }

            return $enviado;
        } catch (Throwable $e) {
            InformeLogger::error($e->getMessage());
            return false;
        }
    }

    /**
     * Arma estructura completa del informe.
     */
    public static function construirDatosInforme(string $tipo, ?string $fecha = null): array
    {
        $fecha = $fecha ?? date('Y-m-d');
        $horaCorte = ($tipo === self::TIPO_MEDIODIA) ? '12:59:59' : '23:59:59';
        $horaCorteLabel = ($tipo === self::TIPO_MEDIODIA) ? '12:59 PM' : '11:59 PM';
        $tipoLabel = ($tipo === self::TIPO_MEDIODIA)
            ? 'Corte medio día'
            : 'Corte cierre diario';

        $vendedores = self::obtenerVendedoresActivos();
        $ventas     = self::obtenerVentasDelPeriodo($fecha, $horaCorte);

        $ventasPorVendedor = [];
        foreach ($ventas as $v) {
            $idAdmin = (int)($v->id_admin_sale ?? 0);
            if ($idAdmin <= 0) {
                continue;
            }
            if (!isset($ventasPorVendedor[$idAdmin])) {
                $ventasPorVendedor[$idAdmin] = [];
            }
            $ventasPorVendedor[$idAdmin][] = $v;
        }

        $filas = [];
        $totales = [
            'ventas_count'  => 0,
            'numeros_count' => 0,
            'dinero'        => 0.0,
            'metodos'       => [],
        ];

        foreach ($vendedores as $v) {
            $id = (int)$v->id_admin;
            $ventasV = $ventasPorVendedor[$id] ?? [];

            $cantidadVentas = count($ventasV);
            $cantidadNumeros = 0;
            $totalDinero = 0.0;
            $metodos = [];

            foreach ($ventasV as $venta) {
                $qty = (int)($venta->quantity_sale ?? 0);
                $monto = (float)($venta->total_sale ?? 0);
                $metodo = trim($venta->payment_method_sale ?? '') ?: 'Otros';

                $cantidadNumeros += $qty;
                $totalDinero += $monto;
                $metodos[$metodo] = ($metodos[$metodo] ?? 0) + 1;

                $totales['ventas_count']++;
                $totales['numeros_count'] += $qty;
                $totales['dinero'] += $monto;
                $totales['metodos'][$metodo] = ($totales['metodos'][$metodo] ?? 0) + 1;
            }

            $goalType  = $v->goal_type_admin ?? 'ventas';
            $goalValue = (int)($v->goal_value_admin ?? 0);
            $progreso  = ($goalType === 'numeros') ? $cantidadNumeros : $cantidadVentas;
            $metaLabel = ($goalType === 'numeros') ? 'Números' : 'Ventas';
            $porcentaje = ($goalValue > 0)
                ? min(100, round(($progreso / $goalValue) * 100, 1))
                : 0;

            ksort($metodos);

            $filas[] = [
                'id_admin'         => $id,
                'nombre'           => $v->name_admin ?: $v->email_admin,
                'email'            => $v->email_admin,
                'goal_type'        => $goalType,
                'goal_type_label'  => $metaLabel,
                'goal_value'       => $goalValue,
                'progreso'         => $progreso,
                'porcentaje'       => $porcentaje,
                'cantidad_ventas'  => $cantidadVentas,
                'cantidad_numeros' => $cantidadNumeros,
                'total_dinero'     => $totalDinero,
                'metodos_pago'     => $metodos,
            ];
        }

        usort($filas, function ($a, $b) {
            if ($b['porcentaje'] === $a['porcentaje']) {
                return $b['total_dinero'] <=> $a['total_dinero'];
            }
            return $b['porcentaje'] <=> $a['porcentaje'];
        });

        ksort($totales['metodos']);

        return [
            'tipo'             => $tipo,
            'tipo_label'       => $tipoLabel,
            'fecha'            => $fecha,
            'fecha_formateada' => self::formatearFecha($fecha),
            'hora_corte'       => $horaCorteLabel,
            'generado_en'      => date('d/m/Y h:i A'),
            'site_name'        => SITE_NAME,
            'logo_path'        => ROOT_PATH . '/assets/images/logos/logo.jpg',
            'vendedores'       => $filas,
            'totales'          => $totales,
            'total_vendedores' => count($filas),
        ];
    }

    /**
     * Una sola consulta API: ventas del día hasta hora de corte.
     */
    public static function obtenerVentasDelPeriodo(string $fecha, string $horaFin): array
    {
        $params = [
            'rel'       => 'sales,customers,raffles',
            'type'      => 'sale,customer,raffle',
            'select'    => 'id_sale,id_admin_sale,total_sale,quantity_sale,payment_method_sale,status_sale,date_created_sale',
            'linkTo'    => 'date_created_sale',
            'between1'  => $fecha . ' 00:00:00',
            'between2'  => $fecha . ' ' . $horaFin,
            'orderBy'   => 'id_sale',
            'orderMode' => 'ASC',
            'startAt'   => 0,
            'endAt'     => 50000,
        ];

        $res = ApiRequest::get('relations', $params);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return [];
        }

        $ventas = is_array($res->results) ? $res->results : [$res->results];

        return array_values(array_filter($ventas, function ($v) {
            return (int)($v->status_sale ?? 1) === 1;
        }));
    }

    /**
     * Una sola consulta API: vendedores activos con metas.
     */
    public static function obtenerVendedoresActivos(): array
    {
        $res = ApiRequest::get('admins', [
            'linkTo'    => 'rol_admin,status_admin',
            'equalTo'   => 'vendedor,1',
            'select'    => 'id_admin,name_admin,email_admin,goal_type_admin,goal_value_admin',
            'orderBy'   => 'name_admin',
            'orderMode' => 'ASC',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return [];
        }

        return is_array($res->results) ? $res->results : [$res->results];
    }

    /**
     * Genera PDF temporal y retorna ruta absoluta.
     */
    public static function generarPdf(array $datos): string
    {
        $dir = ROOT_PATH . '/storage/tmp/informes';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        ob_start();
        if (file_exists($datos['logo_path'])) {
            $datos['logo_base64'] = base64_encode(file_get_contents($datos['logo_path']));
            $datos['logo_mime'] = 'image/jpeg';
        }
        include ROOT_PATH . '/includes/templates/informe-gerencial-pdf.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', ROOT_PATH);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->setBasePath(ROOT_PATH);
        $dompdf->render();
        self::aplicarPiePaginaPdf($dompdf, $datos);

        $filename = 'informe-' . $datos['tipo'] . '-' . $datos['fecha'] . '-' . time() . '.pdf';
        $path = $dir . '/' . $filename;
        file_put_contents($path, $dompdf->output());

        return $path;
    }

    /**
     * Dibuja pie de página en cada hoja vía canvas (evita página en blanco y solapamiento).
     */
    private static function aplicarPiePaginaPdf(Dompdf $dompdf, array $datos): void
    {
        $canvas = $dompdf->getCanvas();
        $siteName = (string) ($datos['site_name'] ?? SITE_NAME);
        $generado = (string) ($datos['generado_en'] ?? date('d/m/Y h:i A'));
        $total    = (int) ($datos['total_vendedores'] ?? 0);

        $lineas = [
            'Documento confidencial · ' . $siteName,
            'Generado el ' . $generado . ' · ' . $total . ' vendedor(es) activo(s)',
            'Desarrollado por Cristian Ceballos — cristianceballos.com',
        ];

        $canvas->page_script(function (
            int $pageNum,
            int $pageCount,
            $pdf,
            $fontMetrics
        ) use ($lineas) {
            $font  = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $size  = 8;
            $color = [0.67, 0.67, 0.67];
            $w     = $pdf->get_width();
            $h     = $pdf->get_height();
            $margin = 48;
            $footerH = 62;
            $topY = $h - $footerH;

            $pdf->line($margin, $topY - 6, $w - $margin, $topY - 6, $color, 0.5);

            $y = $topY + 2;
            foreach ($lineas as $i => $linea) {
                $textW = $fontMetrics->getTextWidth($linea, $font, $size);
                $x = ($w - $textW) / 2;
                $pdf->text($x, $y, $linea, $font, $size, $color);
                if ($i === 2) {
                    $pdf->add_link('https://cristianceballos.com', $x, $y - 2, $textW, 12);
                }
                $y += 12;
            }
        });
    }

    private static function enviarConReintentos(string $pdfPath, array $datos, int $maxIntentos = 3): bool
    {
        require_once __DIR__ . '/mail.controller.php';

        for ($i = 1; $i <= $maxIntentos; $i++) {
            if (MailController::enviarInformeGerencial($pdfPath, $datos)) {
                return true;
            }
            InformeLogger::error("Intento {$i}/{$maxIntentos} fallido al enviar correo.");
            if ($i < $maxIntentos) {
                sleep(2);
            }
        }

        return false;
    }

    private static function formatearFecha(string $fecha): string
    {
        $ts = strtotime($fecha);
        $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
    }
}
