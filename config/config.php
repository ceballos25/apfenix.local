<?php
require_once __DIR__ . '/envLoader.php';

$envCandidates = [
    __DIR__ . '/../../.env-ap', // producción: /home/usuario/.env-ap (fuera de public_html)
    __DIR__ . '/../.env-ap',    // alternativa: raíz del proyecto
];

$envLoaded = false;
foreach ($envCandidates as $envPath) {
    if (is_readable($envPath)) {
        $envLoader = new EnvLoader($envPath);
        $envLoader->load();
        define('ENV_FILE_LOADED', $envPath);
        $envLoaded = true;
        break;
    }
}

if (!$envLoaded) {
    throw new Exception(
        'No se encontró .env-ap legible. Rutas probadas: ' . implode(', ', $envCandidates)
    );
}

/**
 * ===============================
 * RUTAS ABSOLUTAS (FIX WINDOWS/LINUX)
 * ===============================
 */
define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('DS', DIRECTORY_SEPARATOR);
define('BASE_URL', rtrim(env('SITE_URL'), '/'));
define('ASSETS_URL', BASE_URL . '/assets');

/**
 * ===============================
 * CONFIG DB
 * ===============================
 */
define('DB_HOST', env('DB_HOST'));
define('DB_PORT', env('DB_PORT'));
define('DB_USER', env('DB_USER'));
define('DB_PASS', env('DB_PASS'));
define('DB_NAME', env('DB_NAME'));
define('DB_CHARSET', env('DB_CHARSET'));

/**
 * ===============================
 * SMTP / MAIL
 * ===============================
 */
define('SMTP_HOST', env('SMTP_HOST'));
define('SMTP_PORT', env('SMTP_PORT'));
define('SMTP_USER', env('SMTP_USER'));
define('SMTP_PASS', env('SMTP_PASS'));
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION'));

define('MAIL_FROM', env('MAIL_FROM'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME'));
define('MAIL_BCC', env('MAIL_BCC'));

/**
 * ===============================
 * INFORME GERENCIAL
 * ===============================
 */
define('CORREO_INFORME', env('CORREO_INFORME'));
define('CORREO_INFORME_BCC', env('CORREO_INFORME_BCC', env('CORREO_INFORME_CC', '')));
define('INFORME_GERENCIAL_ENABLED', filter_var(env('INFORME_GERENCIAL_ENABLED', true), FILTER_VALIDATE_BOOLEAN));

/**
 * ===============================
 * OPENPAY
 * ===============================
 */
define('OPENPAY_MERCHANT_ID', env('OPENPAY_MERCHANT_ID'));
define('OPENPAY_PRIVATE_KEY', env('OPENPAY_PRIVATE_KEY'));
define('OPENPAY_PUBLIC_KEY', env('OPENPAY_PUBLIC_KEY'));

define(
    'OPENPAY_URL',
    env('OPENPAY_ENV') === 'production'
        ? 'https://api.openpay.co/v1/' . env('OPENPAY_MERCHANT_ID')
        : 'https://sandbox-api.openpay.co/v1/'. env('OPENPAY_MERCHANT_ID')
);

define('OPENPAY_RETURN_URL', env('OPENPAY_RETURN_URL'));

/**
 * ===============================
 * CONFIG SITIO
 * ===============================
 */
define('SITE_NAME', env('SITE_NAME'));

/**
 * ===============================
 * TIMEZONE
 * ===============================
 */
date_default_timezone_set(env('TIMEZONE') ?: 'America/Bogota');

/**
 * ===============================
 * API
 * ===============================
 */
define('API_BASE', rtrim(env('API_BASE'), '/') . '/');
define('API_KEY', env('API_KEY'));

/**
 * ===============================
 * WEBHOOK METEOR (chatbot)
 * ===============================
 */
define('METEOR_WEBHOOK_URL', env('METEOR_WEBHOOK_URL', ''));
define('METEOR_WEBHOOK_SECRET', env('METEOR_WEBHOOK_SECRET', ''));

/**
 * ===============================
 * MODO / ERRORES
 * ===============================
 */
define('APP_ENV', env('APP_ENV'));
define('DEBUG_MODE', (bool) env('DEBUG_MODE'));

if (env('DISPLAY_ERRORS')) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

/**
 * ===============================
 * CONFIGURACIÓN DE POS
 * ===============================
 */
define('SALE_PREFIX', env('SALE_PREFIX') ?? 'CR');
define('SALE_PAD', (int)(env('SALE_PAD') ?? 6));

$isCli = (php_sapi_name() === 'cli');

/**
 * ===============================
 * CONFIGURACIÓN Y MANEJO DE SESIÓN (solo HTTP)
 * ===============================
 */
if (!$isCli) {
ini_set('session.cookie_httponly', env('SESSION_COOKIE_HTTPONLY') ? '1' : '0');
ini_set('session.cookie_secure', env('SESSION_COOKIE_SECURE') ? '1' : '0');
ini_set('session.cookie_lifetime', env('SESSION_LIFETIME'));
ini_set('session.gc_maxlifetime', env('SESSION_LIFETIME'));
}

if (!$isCli && env('SESSION_AUTO_START') && session_status() === PHP_SESSION_NONE) {
    session_name(env('SESSION_NAME'));
    session_start();
}

require_once ROOT_PATH . '/includes/auth.php';

/**
 * ===============================
 * PROTECCIÓN DE RUTAS (solo HTTP)
 * ===============================
 */
if (!$isCli) {
$currentScript = basename($_SERVER['SCRIPT_FILENAME']);
$publicPages = ['index.php', 'login.php', 'dash.php', 'logout.php', 'index_.php', 'webhook.php', 'check-promo.php', 'numeros.ajax.php', 'ventas.ajax.php', 'web.ajax.php','clientes.ajax.php', 'success.php', 'confirm.php', 'transferencia.php']; // Páginas sin protección

$isPublicPage = in_array($currentScript, $publicPages);

// Solo validar autenticación si NO es una página pública
if (!$isPublicPage) {
    if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {
        header("Location: " . BASE_URL . "/dash.php");
        exit;
    }
    
    // Validar expiración de token (opcional)
    if (isset($_SESSION["token_exp_admin"])) {
        $now = time();
        if ($now >= $_SESSION["token_exp_admin"]) {
            session_unset();
            session_destroy();
            header("Location: " . BASE_URL . "/index.php?error=session_expired");
            exit;
        }
    }

    Auth::enforcePageAccess($currentScript);
}
}