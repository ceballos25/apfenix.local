# Informe Gerencial Automático (PDF por correo)

## Resumen

Sistema que genera un informe ejecutivo en PDF con ventas por vendedor y lo envía por correo **2 veces al día** vía cron.

| Corte | Hora | Incluye ventas de |
|-------|------|-------------------|
| Medio día | 12:59 PM | 00:00:00 — 12:59:59 del día actual |
| Cierre | 11:59 PM | 00:00:00 — 23:59:59 del día actual |

---

## Configuración `.env-ap`

Agregar en `/websites/.env-ap`:

```env
CORREO_INFORME=tu-correo@ejemplo.com
INFORME_GERENCIAL_ENABLED=true
```

- `CORREO_INFORME` — destinatario del informe (obligatorio)
- `INFORME_GERENCIAL_ENABLED` — `true` / `false` para activar o pausar

Usa la misma configuración SMTP existente (`SMTP_*`, `MAIL_FROM`).

---

## Crontab (producción)

Ajustar rutas según el servidor. Zona horaria: `America/Bogota` (ya configurada en `config.php`).

```cron
59 12 * * * /usr/bin/php /home/cristian-ceballos/websites/apfenix.local/cron/enviar-informe-gerencial.php mediodia >> /home/cristian-ceballos/websites/apfenix.local/logs/cron-informe.log 2>&1
59 23 * * * /usr/bin/php /home/cristian-ceballos/websites/apfenix.local/cron/enviar-informe-gerencial.php cierre >> /home/cristian-ceballos/websites/apfenix.local/logs/cron-informe.log 2>&1
```

---

## Ejecución manual (pruebas)

```bash
cd /home/cristian-ceballos/websites/apfenix.local
php cron/enviar-informe-gerencial.php mediodia
php cron/enviar-informe-gerencial.php cierre
```

---

## Archivos creados

| Archivo | Función |
|---------|---------|
| `controllers/informeGerencial.controller.php` | Lógica, consultas API, PDF |
| `controllers/mail.controller.php` | Método `enviarInformeGerencial()` |
| `includes/templates/informe-gerencial-pdf.php` | Plantilla HTML del PDF |
| `includes/informe.logger.php` | Logs del job |
| `cron/enviar-informe-gerencial.php` | Script cron CLI |
| `storage/tmp/informes/` | PDFs temporales (se eliminan tras envío) |
| `logs/informe-gerencial.log` | Log de ejecuciones |
| `logs/mail.log` | Errores SMTP (existente, ampliado) |

---

## Dependencia nueva

```bash
composer require dompdf/dompdf
```

---

## Consultas (optimización)

- **1 consulta** `relations` — todas las ventas del día hasta la hora de corte
- **1 consulta** `admins` — vendedores activos con metas
- Agregación por vendedor en PHP (sin N+1)

Solo cuenta ventas con `status_sale = 1` y `id_admin_sale` asignado (ventas POS por vendedor).

---

## Contenido del PDF

- Encabezado corporativo AP FENIX (logo + título)
- Tipo de corte y fecha/hora
- Resumen global (ventas, números, total $)
- Por cada vendedor (ordenado por % cumplimiento):
  - Meta, avance, barra de progreso
  - Cantidad ventas / números / total $
  - Desglose por método de pago
- Resumen global de métodos de pago

---

## Seguridad y estabilidad

- Reintentos de envío: **3 intentos** con pausa de 2 s
- PDF temporal eliminado tras envío (éxito o fallo final)
- Cron corre en CLI: no afecta sesiones web (`config.php` detecta `php_sapi_name() === 'cli'`)
- No modifica ventas ni vendedores existentes

---

## Compatibilidad

- No altera flujos de venta, OpenPay, webhooks ni dashboard
- Reutiliza `ApiRequest`, SMTP y convenciones del proyecto
- Compatible con vendedores creados con metas `ventas` / `numeros`
