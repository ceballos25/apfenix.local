<?php

/**
 * Auth — Control de roles y permisos
 *
 * Roles soportados:
 *   - administrador : acceso completo
 *   - vendedor      : solo vender + dashboard/historial propio
 */
class Auth
{
    const ROLE_ADMIN    = 'administrador';
    const ROLE_VENDEDOR = 'vendedor';

    /** Páginas exclusivas de administrador */
    const ADMIN_PAGES = [
        'dashboard.php',
        'transferencias.php',
        'clientes.php',
        'numeros-vendidos.php',
        'numeros.php',
        'rifas.php',
        'vendedores.php',
    ];

    /** Páginas accesibles por vendedor */
    const VENDEDOR_PAGES = [
        'dashboard-vendedor.php',
        'vender.php',
        'ventas.php',
    ];

    /** Endpoints AJAX que el vendedor necesita (vender, dashboard, ventas) */
    const VENDEDOR_AJAX = [
        'rifas.ajax.php',
        'vendedor.ajax.php',
        'ventas.ajax.php',
        'clientes.ajax.php',
    ];

    /** Dashboard por rol */
    const DASHBOARD_BY_ROLE = [
        self::ROLE_ADMIN    => '/front/dashboard.php',
        self::ROLE_VENDEDOR => '/front/dashboard-vendedor.php',
    ];

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): string
    {
        $rol = $_SESSION['user_role'] ?? null;
        if (empty($rol)) {
            return self::ROLE_ADMIN;
        }
        return $rol;
    }

    public static function isAdmin(): bool
    {
        return self::role() === self::ROLE_ADMIN;
    }

    public static function isVendedor(): bool
    {
        return self::role() === self::ROLE_VENDEDOR;
    }

    public static function dashboardUrl(): string
    {
        return BASE_URL . (self::DASHBOARD_BY_ROLE[self::role()] ?? self::DASHBOARD_BY_ROLE[self::ROLE_ADMIN]);
    }

    public static function redirectToDashboard(): void
    {
        header('Location: ' . self::dashboardUrl());
        exit;
    }

    /**
     * Bloquea acceso a páginas front/ según rol (llamar desde config.php).
     */
    public static function enforcePageAccess(string $scriptName): void
    {
        if (!self::isLoggedIn()) {
            return;
        }

        if (self::isAdmin()) {
            if (in_array($scriptName, ['dashboard-vendedor.php'], true)) {
                header('Location: ' . BASE_URL . '/front/dashboard.php');
                exit;
            }
            return;
        }

        if (self::isVendedor()) {
            $allowed = array_merge(self::VENDEDOR_PAGES, self::VENDEDOR_AJAX);
            if (!in_array($scriptName, $allowed, true)) {
                header('Location: ' . BASE_URL . '/front/dashboard-vendedor.php');
                exit;
            }
        }
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sesión requerida']);
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }
    }

    /**
     * Fuerza filtro por vendedor autenticado en consultas de ventas.
     */
    public static function enforceSellerFilter(array &$filtros): void
    {
        if (self::isVendedor()) {
            $filtros['idAdmin'] = (string) self::userId();
        }
    }

    /**
     * Impide que un vendedor manipule id_admin en POST.
     */
    public static function resolveSellerIdForSale(array $data): ?int
    {
        if (self::isVendedor()) {
            return self::userId();
        }
        return isset($data['id_admin']) ? (int) $data['id_admin'] : self::userId();
    }

    /**
     * Verifica que la venta pertenezca al vendedor autenticado.
     */
    public static function canAccessSale($venta): bool
    {
        if (self::isAdmin()) {
            return true;
        }
        if (self::isVendedor()) {
            return (int) ($venta->id_admin_sale ?? 0) === self::userId();
        }
        return false;
    }

    /**
     * Obtiene venta por ID y valida propiedad.
     */
    public static function getOwnedSale(int $idSale)
    {
        require_once ROOT_PATH . '/controllers/apiRequest.controller.php';

        $res = ApiRequest::get('sales', [
            'linkTo' => 'id_sale',
            'equalTo' => $idSale,
            'select' => 'id_sale,id_admin_sale,status_sale',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return null;
        }

        $venta = is_array($res->results) ? $res->results[0] : $res->results;

        return self::canAccessSale($venta) ? $venta : null;
    }
}
