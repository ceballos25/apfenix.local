# Rol VENDEDOR — Documentación de implementación

## Resumen

Se implementó el rol `vendedor` con acceso restringido al módulo de ventas, dashboard personalizado con metas diarias, y módulo administrativo para gestionar vendedores.

---

## 1. Análisis de la estructura existente

| Componente | Hallazgo |
|------------|----------|
| **Autenticación** | Login vía API `admins?login=true`, sesión PHP |
| **Roles** | Campo `rol_admin` en tabla `admins` (existía pero NULL) |
| **Ventas ↔ Vendedor** | Campo `id_admin_sale` en `sales` **ya existía** |
| **Estado usuario** | Campo `status_admin` en `admins` **ya existía** |
| **Permisos previos** | Solo ocultaban menú; sin bloqueo backend |
| **API** | Toda persistencia vía `API_BASE` + `ApiRequest` |

**Decisión:** Reutilizar `admins` + `id_admin_sale`. No crear tabla nueva de vendedores.

---

## 2. Cambios en base de datos

Archivo: `database/migrations/001_vendedor_role.sql`

```sql
-- Nuevos campos en admins
name_admin          VARCHAR(100)   -- Nombre visible
goal_type_admin     ENUM('ventas','numeros')  -- Tipo de meta
goal_value_admin    INT            -- Valor meta diaria

-- Índice + FK en sales
idx_sales_admin (id_admin_sale)
fk_sales_admins → admins(id_admin)
```

**Ejecutar en el servidor MySQL antes de usar el módulo.**

Roles normalizados:
- `administrador` — acceso completo
- `vendedor` — acceso limitado

---

## 3. Archivos nuevos

| Archivo | Propósito |
|---------|-----------|
| `includes/auth.php` | Middleware de roles y permisos |
| `controllers/vendedores.controller.php` | CRUD vendedores (admin) |
| `controllers/vendedor.controller.php` | Dashboard y métricas del vendedor |
| `front/vendedores.php` | UI administración vendedores |
| `front/dashboard-vendedor.php` | Dashboard exclusivo vendedor |
| `front/ajax/vendedores.ajax.php` | AJAX CRUD (solo admin) |
| `front/ajax/vendedor.ajax.php` | AJAX dashboard vendedor |
| `assets/js/vendedores.js` | Frontend CRUD |
| `assets/js/dashboard-vendedor.js` | Frontend dashboard |

---

## 4. Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `config/config.php` | Carga `Auth` + `enforcePageAccess()` por rol |
| `functions/login.php` | Valida `status_admin`, guarda metas en sesión, redirige por rol |
| `dash.php` | Redirección según rol; error `inactive` |
| `includes/sidebar.php` | Menú distinto para admin vs vendedor |
| `controllers/ventas.controller.php` | Filtro forzado por vendedor; ownership en detalle; anular solo admin |
| `front/ajax/ventas.ajax.php` | Auth por acción (pública / login / admin) |
| `front/ajax/clientes.ajax.php` | Login requerido; CRUD solo admin |
| `front/ajax/rifas.ajax.php` | Mutaciones solo admin |
| `front/ajax/dashboard.ajax.php` | Solo admin |
| `front/ajax/transferencias.ajax.php` | Solo admin |
| `front/ventas.php` | Título y filtro vendedor según rol |
| `assets/js/ventas.js` | Oculta anular y filtro admin para vendedor |
| `front/dashboard.php` | Bloqueo explícito vendedor |

---

## 5. Permisos por rol

### Vendedor (`rol_admin = vendedor`)

**Puede acceder:**
- `dashboard-vendedor.php` — Meta diaria + ventas de hoy
- `vender.php` — Registrar ventas
- `ventas.php` — Solo **sus** ventas

**No puede acceder:**
- Dashboard admin, transferencias, clientes, rifas, números, reportes globales, vendedores

### Administrador (`rol_admin = administrador`)

Acceso completo + módulo **Vendedores** en Configuración.

---

## 6. Metas diarias

Configurables al crear/editar vendedor:

| Tipo | Campo DB | Ejemplo |
|------|----------|---------|
| Ventas | `goal_type_admin = ventas` | 50 ventas/día |
| Números | `goal_type_admin = numeros` | 600 números/día |

**Reglas:**
- Solo cuenta registros del **día actual** (`date('Y-m-d')`)
- Solo ventas con `status_sale = 1` del vendedor autenticado
- Se reinicia automáticamente cada día (sin acumulado histórico)

---

## 7. Seguridad

| Capa | Implementación |
|------|----------------|
| **Rutas PHP** | `Auth::enforcePageAccess()` en `config.php` |
| **AJAX** | `Auth::requireLogin()` / `requireAdmin()` por acción |
| **Consultas ventas** | `Auth::enforceSellerFilter()` fuerza `id_admin_sale` |
| **Crear venta** | `Auth::resolveSellerIdForSale()` impide suplantación |
| **Detalle venta** | `Auth::canAccessSale()` valida propiedad |
| **Usuario inactivo** | `status_admin != 1` → login rechazado |
| **Contraseñas** | `password_hash(BCRYPT, cost 7)` al crear/actualizar |

---

## 8. Flujo de login

```
dash.php → login.php → API admins
    ↓
status_admin == 0 → error "inactive"
    ↓
rol == vendedor → front/dashboard-vendedor.php
rol == administrador → front/dashboard.php
```

---

## 9. Crear un vendedor (admin)

1. Ir a **Configuración → Vendedores**
2. Clic en **Nuevo Vendedor**
3. Completar: nombre, usuario, contraseña, estado, tipo meta, valor meta
4. El vendedor inicia sesión en `dash.php` con su usuario

---

## 10. Notas importantes

1. **Migración SQL obligatoria** en la BD que usa la API remota.
2. **Contraseñas:** la API usa `crypt()` con salt fijo (`$2a$07$azybxcags23425sdg23sdfhsd$`). Al crear vendedores se usa `register=true` (texto plano). Al editar contraseña se usa `apiHashPassword()` en `includes/password.helper.php`. **No usar `password_hash()` de PHP.**
3. Vendedores creados antes de este fix deben **restablecer contraseña** desde el módulo Vendedores (editar → nueva contraseña).
4. Ventas web (OpenPay/transferencias) pueden tener `id_admin_sale = NULL` — no afectan metas de vendedor POS.
5. Los admins existentes con `rol_admin NULL` se normalizan a `administrador` con la migración.
