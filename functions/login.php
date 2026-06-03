<?php
// ============================================
// 1. ACTIVAR ERRORES TEMPORALMENTE
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ============================================
// 2. CARGAR CONFIGURACIÓN
// ============================================
require_once "../config/config.php";

// Verificar que las constantes existen
if (!defined('API_BASE') || !defined('API_KEY')) {
    die("ERROR: config.php no cargó las constantes API_BASE o API_KEY");
}

// ============================================
// 3. VALIDAR DATOS DEL FORMULARIO
// ============================================
$email = trim($_POST['email'] ?? '');
$pass  = trim($_POST['password'] ?? '');

if ($email === '' || $pass === '') {
    header("Location: ../dash.php?error=missing");
    exit;
}

// ============================================
// 4. PREPARAR REQUEST A LA API
// ============================================
$url = API_BASE . "admins?login=true&suffix=admin";

$postFields = http_build_query([
    "email_admin"    => $email,
    "password_admin" => $pass
]);

// ============================================
// 5. EJECUTAR CURL
// ============================================
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postFields,
    CURLOPT_HTTPHEADER     => [
        "Authorization: " . API_KEY,
        "Content-Type: application/x-www-form-urlencoded"
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false // Solo si es HTTPS local
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// ============================================
// 6. DEBUG LOG (crear archivo)
// ============================================
// $logContent = "=== LOGIN DEBUG " . date('Y-m-d H:i:s') . " ===\n";
// $logContent .= "URL: $url\n";
// $logContent .= "HTTP Code: $httpCode\n";
// $logContent .= "Curl Error: $curlError\n";
// $logContent .= "Response: $response\n\n";
// file_put_contents(__DIR__ . '/../login_debug.log', $logContent, FILE_APPEND);

// ============================================
// 7. VALIDAR RESPUESTA CURL
// ============================================
if ($response === false) {
    header("Location: ../dash.php?error=curl&detail=" . urlencode($curlError));
    exit;
}

// ============================================
// 8. DECODIFICAR JSON
// ============================================
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    header("Location: ../dash.php?error=json&detail=" . urlencode(json_last_error_msg()));
    exit;
}

// ============================================
// 9. VALIDAR ESTRUCTURA DE RESPUESTA
// ============================================
if (!is_array($data)) {
    header("Location: ../dash.php?error=invalid_response");
    exit;
}

if (($data["status"] ?? 0) != 200) {
    $errorMsg = $data["results"] ?? "Credenciales incorrectas";
    header("Location: ../dash.php?error=bad_credentials&detail=" . urlencode($errorMsg));
    exit;
}

// ============================================
// 10. EXTRAER DATOS DEL ADMIN
// ============================================
$admin = $data["results"][0] ?? null;

if (!$admin || empty($admin["token_admin"])) {
    header("Location: ../dash.php?error=no_token");
    exit;
}

// Usuario inactivo
if ((int)($admin["status_admin"] ?? 1) !== 1) {
    header("Location: ../dash.php?error=inactive");
    exit;
}

// Normalizar rol
$rol = trim($admin["rol_admin"] ?? '');
if ($rol === '') {
    $rol = 'administrador';
}

// ============================================
// 11. CREAR SESIÓN
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION["user_id"]          = $admin["id_admin"] ?? null;
$_SESSION["user_role"]        = $rol;
$_SESSION["token_admin"]      = $admin["token_admin"];
$_SESSION["token_exp_admin"]   = $admin["token_exp_admin"] ?? null;
$_SESSION["email_admin"]       = $admin["email_admin"] ?? $email;
$_SESSION["name_admin"]        = $admin["name_admin"] ?? $admin["email_admin"] ?? $email;
$_SESSION["goal_type_admin"]   = $admin["goal_type_admin"] ?? 'ventas';
$_SESSION["goal_value_admin"]  = (int)($admin["goal_value_admin"] ?? 0);
$_SESSION["status_admin"]      = (int)($admin["status_admin"] ?? 1);
$_SESSION["id_branch"]         = $admin["id_branch"] ?? null;

// ============================================
// 12. REDIRECCIONAR SEGÚN ROL
// ============================================
if ($rol === 'vendedor') {
    header("Location: ../front/dashboard-vendedor.php");
} else {
    header("Location: ../front/dashboard.php");
}
exit;