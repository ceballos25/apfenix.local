# Despliegue con Git — hosting compartido (sin Composer en el servidor)

## Solución adoptada

**`vendor/` va dentro del repositorio Git.**

En hosting compartido no siempre hay terminal ni Composer. Si `vendor/` está en `.gitignore`, el deploy nunca lo sube y fallan correos, PDF e informes.

Flujo correcto:

```
En tu PC:  composer install --no-dev  →  git add vendor  →  git push
Servidor:  git pull  →  public_html/vendor/autoload.php ✓
```

---

## Primera vez (o al cambiar composer.json)

En **tu computador**:

```bash
cd apfenix.local
composer install --no-dev --optimize-autoloader
git add vendor composer.json composer.lock
git commit -m "Incluir dependencias Composer para deploy en hosting compartido"
git push
```

En **cPanel → Git → Deploy** (o pull automático del servidor).

Verificar en el administrador de archivos:

```
public_html/vendor/autoload.php   ← debe existir
```

---

## Cuando agregues una dependencia nueva

1. En local: `composer require paquete/nombre --no-dev` (o editar `composer.json`)
2. `composer install --no-dev --optimize-autoloader`
3. `git add vendor composer.json composer.lock`
4. Commit + push

---

## Verificación rápida

```bash
curl -s -X POST "https://apfenix.com/front/ajax/ventas.ajax.php" -d "action=obtener_rifas" | head -c 80
```

Debe empezar con `{"success":true,...}`

---

## Qué Git NO despliega

| Elemento | Motivo |
|----------|--------|
| `.env-ap` | Secretos — configurar manualmente en el servidor |
| `logs/` | Generados en runtime |
| `uploads/comprobantes/` | Subidas de usuarios |
