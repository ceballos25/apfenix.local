<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 40px 48px 95px 48px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        table { page-break-inside: auto; }
        .tabla-vendedores { border-collapse: collapse; }
    </style>
</head>
<body>

    <!-- ══════════════ ENCABEZADO ══════════════ -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
        <tr>
            <td width="90" valign="middle" style="padding-right: 16px;">
                <?php if (!empty($datos['logo_base64'])): ?>
                <img src="data:<?= $datos['logo_mime'] ?>;base64,<?= $datos['logo_base64'] ?>"
                     width="85" style="max-height: 50px;" alt="">
                <?php endif; ?>
            </td>
            <td valign="middle">
                <div style="font-size: 18px; font-weight: bold; color: #222222; margin-bottom: 6px;">
                    Informe de Ventas por Vendedor
                </div>
                <div style="font-size: 10px; color: #666666; line-height: 1.6;">
                    <?= htmlspecialchars($datos['site_name']) ?>
                    &nbsp;|&nbsp;
                    <?= htmlspecialchars($datos['tipo_label']) ?>
                    &nbsp;|&nbsp;
                    <?= htmlspecialchars($datos['fecha_formateada']) ?>
                    &nbsp;|&nbsp;
                    Corte <?= htmlspecialchars($datos['hora_corte']) ?>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 16px;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="border-bottom: 2px solid #b8960c; font-size: 1px; line-height: 1px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <?php
        $recaudoGlobal = $datos['totales']['recaudo'] ?? [
            'total' => $datos['totales']['dinero'] ?? 0,
            'transferencia' => 0,
            'efectivo' => 0,
            'otros' => 0,
        ];
    ?>
    <!-- ══════════════ RESUMEN GLOBAL ══════════════ -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 16px;">
        <tr>
            <td width="32%" style="padding: 14px 16px; border: 1px solid #dddddd; background-color: #fafafa;">
                <div style="font-size: 8px; color: #888888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Ventas totales</div>
                <div style="font-size: 20px; font-weight: bold; color: #222222;"><?= (int)$datos['totales']['ventas_count'] ?></div>
            </td>
            <td width="2%"></td>
            <td width="32%" style="padding: 14px 16px; border: 1px solid #dddddd; background-color: #fafafa;">
                <div style="font-size: 8px; color: #888888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Números vendidos</div>
                <div style="font-size: 20px; font-weight: bold; color: #222222;"><?= (int)$datos['totales']['numeros_count'] ?></div>
            </td>
            <td width="2%"></td>
            <td width="32%" style="padding: 14px 16px; border: 1px solid #dddddd; background-color: #fafafa;">
                <div style="font-size: 8px; color: #888888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Total recaudado</div>
                <div style="font-size: 20px; font-weight: bold; color: #2d6a4f;">$<?= number_format($recaudoGlobal['total'], 0, ',', '.') ?></div>
            </td>
        </tr>
    </table>

    <!-- ══════════════ CONSOLIDADO RECAUDO ══════════════ -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 32px;">
        <tr>
            <td style="font-size: 9px; font-weight: bold; color: #444444; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 8px;">
                Consolidado general de recaudo
                <?php if (!empty($datos['periodo_desde']) && !empty($datos['periodo_hasta'])): ?>
                <span style="font-weight: normal; color: #888888; text-transform: none;">
                    — <?= htmlspecialchars($datos['periodo_desde']) ?> a <?= htmlspecialchars($datos['periodo_hasta']) ?>
                </span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td width="32%" style="padding: 12px 14px; border: 1px solid #dddddd; background-color: #f8faf8;">
                <div style="font-size: 8px; color: #888888; text-transform: uppercase; margin-bottom: 4px;">Por transferencia</div>
                <div style="font-size: 16px; font-weight: bold; color: #1d4ed8;">$<?= number_format($recaudoGlobal['transferencia'], 0, ',', '.') ?></div>
            </td>
            <td width="2%"></td>
            <td width="32%" style="padding: 12px 14px; border: 1px solid #dddddd; background-color: #f8faf8;">
                <div style="font-size: 8px; color: #888888; text-transform: uppercase; margin-bottom: 4px;">En efectivo</div>
                <div style="font-size: 16px; font-weight: bold; color: #15803d;">$<?= number_format($recaudoGlobal['efectivo'], 0, ',', '.') ?></div>
            </td>
            <td width="2%"></td>
            <td width="32%" style="padding: 12px 14px; border: 1px solid #dddddd; background-color: #fffbeb;">
                <div style="font-size: 8px; color: #888888; text-transform: uppercase; margin-bottom: 4px;">Total consolidado</div>
                <div style="font-size: 16px; font-weight: bold; color: #222222;">$<?= number_format($recaudoGlobal['total'], 0, ',', '.') ?></div>
            </td>
        </tr>
        <?php if (($recaudoGlobal['otros'] ?? 0) > 0): ?>
        <tr>
            <td colspan="5" style="padding-top: 6px; font-size: 8px; color: #888888;">
                Otros métodos: $<?= number_format($recaudoGlobal['otros'], 0, ',', '.') ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- ══════════════ TÍTULO SECCIÓN ══════════════ -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 12px;">
        <tr>
            <td style="font-size: 11px; font-weight: bold; color: #222222; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 8px; border-bottom: 1px solid #cccccc;">
                Desempeño por vendedor
                <span style="font-weight: normal; color: #888888; text-transform: none; letter-spacing: 0;">
                    — ordenado por cumplimiento de meta
                </span>
            </td>
        </tr>
    </table>

    <?php if (empty($datos['vendedores'])): ?>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="padding: 40px 0; text-align: center; color: #888888; font-size: 11px;">
                No hay vendedores activos registrados.
            </td>
        </tr>
    </table>
    <?php else: ?>

    <!-- ══════════════ TABLA PRINCIPAL ══════════════ -->
    <table class="tabla-vendedores" width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #cccccc;">
        <tr style="background-color: #f0f0f0;">
            <td style="padding: 10px 12px; font-size: 8px; font-weight: bold; color: #444444; text-transform: uppercase; border-bottom: 1px solid #cccccc; width: 18%;">Vendedor</td>
            <td style="padding: 10px 8px; font-size: 8px; font-weight: bold; color: #444444; text-transform: uppercase; border-bottom: 1px solid #cccccc; text-align: center; width: 9%;">Meta</td>
            <td style="padding: 10px 8px; font-size: 8px; font-weight: bold; color: #444444; text-transform: uppercase; border-bottom: 1px solid #cccccc; text-align: center; width: 8%;">Avance</td>
            <td style="padding: 10px 8px; font-size: 8px; font-weight: bold; color: #444444; text-transform: uppercase; border-bottom: 1px solid #cccccc; text-align: center; width: 7%;">Ventas</td>
            <td style="padding: 10px 8px; font-size: 8px; font-weight: bold; color: #444444; text-transform: uppercase; border-bottom: 1px solid #cccccc; text-align: center; width: 7%;">Núm.</td>
            <td style="padding: 10px 8px; font-size: 8px; font-weight: bold; color: #444444; text-transform: uppercase; border-bottom: 1px solid #cccccc; text-align: right; width: 11%;">Transfer.</td>
            <td style="padding: 10px 8px; font-size: 8px; font-weight: bold; color: #444444; text-transform: uppercase; border-bottom: 1px solid #cccccc; text-align: right; width: 11%;">Efectivo</td>
            <td style="padding: 10px 8px; font-size: 8px; font-weight: bold; color: #444444; text-transform: uppercase; border-bottom: 1px solid #cccccc; text-align: right; width: 12%;">Total</td>
            <td style="padding: 10px 10px; font-size: 8px; font-weight: bold; color: #444444; text-transform: uppercase; border-bottom: 1px solid #cccccc; text-align: center; width: 17%;">Cumplimiento</td>
        </tr>

        <?php foreach ($datos['vendedores'] as $i => $v): ?>
        <?php
            $pct      = $v['porcentaje'];
            $barColor = $pct >= 80 ? '#2d6a4f' : ($pct >= 50 ? '#d97706' : '#b91c1c');
            $barW     = max(1, min(100, (int)round($pct)));
            $barRest  = 100 - $barW;
            $bgRow    = ($i % 2 === 0) ? '#ffffff' : '#fafafa';
            $recV     = $v['recaudo'] ?? [
                'total' => $v['total_dinero'] ?? 0,
                'transferencia' => 0,
                'efectivo' => 0,
                'otros' => 0,
            ];
        ?>
        <tr style="background-color: <?= $bgRow ?>;">
            <td style="padding: 9px 12px; border-bottom: 1px solid #e5e5e5; vertical-align: middle;">
                <div style="font-size: 10px; font-weight: bold; color: #222222; margin-bottom: 2px;">
                    <?= htmlspecialchars($v['nombre']) ?>
                </div>
                <div style="font-size: 8px; color: #888888;">
                    <?= htmlspecialchars($v['email']) ?>
                </div>
            </td>
            <td style="padding: 9px 10px; border-bottom: 1px solid #e5e5e5; text-align: center; vertical-align: middle;">
                <div style="font-size: 11px; font-weight: bold; color: #222222;"><?= (int)$v['goal_value'] ?></div>
                <div style="font-size: 8px; color: #888888;"><?= htmlspecialchars(strtolower($v['goal_type_label'])) ?></div>
            </td>
            <td style="padding: 9px 10px; border-bottom: 1px solid #e5e5e5; text-align: center; vertical-align: middle;">
                <div style="font-size: 11px; font-weight: bold; color: #222222;"><?= (int)$v['progreso'] ?></div>
                <div style="font-size: 8px; color: #888888;">de <?= (int)$v['goal_value'] ?></div>
            </td>
            <td style="padding: 9px 10px; border-bottom: 1px solid #e5e5e5; text-align: center; vertical-align: middle; font-size: 11px; font-weight: bold;">
                <?= (int)$v['cantidad_ventas'] ?>
            </td>
            <td style="padding: 9px 8px; border-bottom: 1px solid #e5e5e5; text-align: center; vertical-align: middle; font-size: 11px; font-weight: bold;">
                <?= (int)$v['cantidad_numeros'] ?>
            </td>
            <td style="padding: 9px 8px; border-bottom: 1px solid #e5e5e5; text-align: right; vertical-align: middle; font-size: 10px; font-weight: bold; color: #1d4ed8;">
                $<?= number_format($recV['transferencia'], 0, ',', '.') ?>
            </td>
            <td style="padding: 9px 8px; border-bottom: 1px solid #e5e5e5; text-align: right; vertical-align: middle; font-size: 10px; font-weight: bold; color: #15803d;">
                $<?= number_format($recV['efectivo'], 0, ',', '.') ?>
            </td>
            <td style="padding: 9px 8px; border-bottom: 1px solid #e5e5e5; text-align: right; vertical-align: middle; font-size: 11px; font-weight: bold; color: #2d6a4f;">
                $<?= number_format($recV['total'], 0, ',', '.') ?>
            </td>
            <td style="padding: 9px 12px; border-bottom: 1px solid #e5e5e5; vertical-align: middle;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="font-size: 13px; font-weight: bold; color: #222222; padding-bottom: 4px; text-align: right; width: 36px;">
                            <?= $pct ?>%
                        </td>
                        <td style="padding-left: 10px; vertical-align: middle;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="height: 8px;">
                                <tr>
                                    <td width="<?= $barW ?>%" style="background-color: <?= $barColor ?>; height: 8px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                    <td width="<?= $barRest ?>%" style="background-color: #e8e8e8; height: 8px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php endif; ?>

    <!-- Espacio antes del área del pie de página -->
    <div style="height: 20px; font-size: 1px; line-height: 1px;">&nbsp;</div>

</body>
</html>
