# Despliegue en hosting compartido (sin terminal root)

En cPanel **no hace falta** `composer install` en el servidor. Las dependencias se generan en su PC y se suben por FTP.

## Causa del error JSON en ventas / números vendidos

Si falta `vendor/autoload.php`, PHP devuelve HTML de error fatal y el navegador muestra:

`JSON.parse: unexpected character at line 1 column 1`

## Pasos (una sola vez por actualización de dependencias)

### En su computador (local)

```bash
cd /ruta/apfenix.local
composer install --no-dev --optimize-autoloader
php scripts/empaquetar-vendor.php
```

Se crea `deploy/vendor.zip`.

### En cPanel / FTP

1. Entrar a **Administrador de archivos** → `public_html`
2. Subir `deploy/vendor.zip`
3. Clic derecho → **Extract** (extraer)
4. Confirmar que existe: `public_html/vendor/autoload.php`

### Subir también el código PHP actualizado

Archivos críticos recientes:

- `controllers/mail.controller.php`
- `controllers/ventas.controller.php`
- `controllers/vendedor.controller.php`
- `includes/head.php`
- `assets/css/styles.min.css`
- `assets/js/numeros-vendidos.js`

## Verificación rápida

En el navegador o con curl:

```bash
curl -s -X POST "https://apfenix.com/front/ajax/ventas.ajax.php" -d "action=obtener_rifas" | head -c 120
```

Debe empezar con `{"success":` y **no** con `<br />` ni `Fatal error`.

## Iconos Tabler

El panel admin carga iconos desde CDN en `includes/head.php`. No hace falta subir las fuentes locales si usa esa versión de `head.php` y `styles.min.css` (sin import local de tabler-icons).

## `.env-ap`

Sigue fuera del repo. En el servidor debe estar en la ruta que lee `config/config.php` (normalmente un nivel arriba de `public_html` o según su instalación).
