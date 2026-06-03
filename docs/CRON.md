# Cron jobs — AP Fenix (cPanel / hosting compartido)

## Requisitos previos (servidor)

1. **Código desplegado** en `public_html` (incluye `vendor/` — ver `docs/DEPLOY_GIT.md`).
2. **`.env-ap`** fuera del repo, en la ruta que lee `config/config.php` (normalmente `/home/apfenixc/.env-ap`, un nivel arriba de `public_html`).
3. Variables del informe gerencial en `.env-ap`:

```env
CORREO_INFORME=gerencia@tu-dominio.com
CORREO_INFORME_BCC=ceballosmarincristiancamilo@gmail.com
INFORME_GERENCIAL_ENABLED=true
```

4. **Carpeta de logs** (no va en Git):

```bash
mkdir -p /home/apfenixc/public_html/logs
chmod 755 /home/apfenixc/public_html/logs
```

En cPanel → Administrador de archivos: crear carpeta `public_html/logs`.

5. **PHP CLI** — en cPanel suele ser `/usr/local/bin/php`. Para confirmar:

```bash
which php
# o en cPanel → Select PHP Version → ver ruta del binario
```

---

## Jobs configurados

| Job | Horario (Colombia) | Script | Argumento |
|-----|-------------------|--------|-----------|
| Informe mediodía | 12:59 PM diario | `cron/enviar-informe-gerencial.php` | `mediodia` |
| Informe cierre | 11:59 PM diario | `cron/enviar-informe-gerencial.php` | `cierre` |

Zona horaria del proyecto: `America/Bogota` (`config/config.php`).

---

## cPanel → Cron Jobs

Ruta base del proyecto en producción:

```
/home/apfenixc/public_html
```

### Job 1 — Informe mediodía (12:59 PM)

| Campo | Valor |
|-------|-------|
| Minuto | `59` |
| Hora | `12` |
| Día | `*` |
| Mes | `*` |
| Día semana | `*` |

**Comando:**

```bash
/usr/local/bin/php /home/apfenixc/public_html/cron/enviar-informe-gerencial.php mediodia >> /home/apfenixc/public_html/logs/cron-informe.log 2>&1
```

### Job 2 — Informe cierre (11:59 PM)

| Campo | Valor |
|-------|-------|
| Minuto | `59` |
| Hora | `23` |
| Día | `*` |
| Mes | `*` |
| Día semana | `*` |

**Comando:**

```bash
/usr/local/bin/php /home/apfenixc/public_html/cron/enviar-informe-gerencial.php cierre >> /home/apfenixc/public_html/logs/cron-informe.log 2>&1
```

---

## Líneas completas (crontab)

Copiar desde `cron/crontab.produccion.txt` o pegar directamente:

```cron
59 12 * * * /usr/local/bin/php /home/apfenixc/public_html/cron/enviar-informe-gerencial.php mediodia >> /home/apfenixc/public_html/logs/cron-informe.log 2>&1
59 23 * * * /usr/local/bin/php /home/apfenixc/public_html/cron/enviar-informe-gerencial.php cierre >> /home/apfenixc/public_html/logs/cron-informe.log 2>&1
```

---

## Importante

- **No ejecutar scripts de prueba en producción.**
- Logs: `public_html/logs/informe-gerencial.log` y `public_html/logs/mail.log`.

---

## Si la ruta de PHP es distinta

Algunos servidores usan `/usr/bin/php` o una versión específica, por ejemplo:

```bash
/usr/local/bin/ea-php82 /home/apfenixc/public_html/cron/enviar-informe-gerencial.php mediodia
```

Sustituir solo la parte del binario PHP; el resto del comando igual.

---

## Más información

Detalle del informe PDF: `docs/INFORME_GERENCIAL.md`.
