<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once "../../config/config.php";
require_once "../../controllers/apiRequest.controller.php";
require_once "../../controllers/paymentBackupsController.php";
require_once "../../controllers/openpay.controller.php";
require_once "../../controllers/transfersController.php";
require_once "../../controllers/dashboard.controller.php";

$action = $_POST['action'] ?? '';

try {

    /* =====================================
     * AVANCE RIFA (público – landing)
     * ===================================== */
    if ($action === 'avance_rifa') {
        echo json_encode(DashboardController::obtenerAvancePublico());
        exit;
    }

    /* =====================================
     * 1. CREAR RESPALDO (OK)
     * ===================================== */
    if ($action === 'crear_respaldo') {

        echo json_encode(
            PaymentBackupsController::crearRespaldo($_POST)
        );
        exit;
    }

    /* =====================================
     * 2. IR A OPENPAY (ÚNICA FORMA)
     * ===================================== */
    if ($action === 'ir_openpay') {

        echo json_encode(
            OpenPayController::irAOpenPay($_POST)
        );
        exit;
    }

/* =====================================
 * 3. TRANSFERENCIA COMPLETA (FLUJO ÚNICO)
 * ===================================== */
if ($action === 'crear_transferencia_completa') {

    /* ===============================
    VALIDAR ARCHIVO
    =============================== */
    if (empty($_FILES['comprobante'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Comprobante requerido'
        ]);
        exit;
    }

    $file = $_FILES['comprobante'];

    // 🔒 Validaciones básicas (PRO)
    $allowed = ['image/jpeg','image/png','image/jpg'];

    if (!in_array($file['type'], $allowed)) {
        echo json_encode([
            'success' => false,
            'message' => 'Formato no permitido'
        ]);
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode([
            'success' => false,
            'message' => 'Archivo muy pesado (max 5MB)'
        ]);
        exit;
    }

    /* ===============================
    GENERAR NOMBRE Y RUTAS
    =============================== */
    $nombre = time() . "_" . preg_replace('/[^A-Za-z0-9.\-_]/', '', $file['name']);

    $rutaRelativa = "uploads/comprobantes/" . $nombre;

    // ⚠️ AJUSTA "ap-fenix" SI TU CARPETA CAMBIA
    $rutaFisica = $_SERVER['DOCUMENT_ROOT'] . "/" . $rutaRelativa;

    // 📁 Crear carpeta si no existe
    if (!is_dir(dirname($rutaFisica))) {
        mkdir(dirname($rutaFisica), 0755, true);
    }

    /* ===============================
    MOVER ARCHIVO
    =============================== */
    if (!move_uploaded_file($file['tmp_name'], $rutaFisica)) {
        echo json_encode([
            'success' => false,
            'message' => 'Error subiendo archivo'
        ]);
        exit;
    }

    /* ===============================
    CREAR TRANSFERENCIA
    =============================== */
    $res = TransfersController::crearTransferencia($_POST);

    if (!$res['success']) {
        echo json_encode($res);
        exit;
    }

    if (empty($res['id_transfer'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No se obtuvo ID de transferencia',
            'debug' => $res
        ]);
        exit;
    }

    $idTransfer = $res['id_transfer'];
    $code = $res['code_transfer'];

    /* ===============================
    GUARDAR URL DEL COMPROBANTE
    =============================== */
    $rutafinal = "https://apfenix.com/" . $rutaRelativa;
    $update = ApiRequest::put(
        "transfers?id={$idTransfer}&nameId=id_transfer&token=no&except=id_transfer&table=transfers&suffix=transfer",
        ["url_transfer" => $rutafinal]
    );

    if (!ApiRequest::isSuccess($update)) {
        echo json_encode([
            'success' => false,
            'message' => 'Error actualizando transferencia',
            'debug' => $update
        ]);
        exit;
    }

    /* ===============================
    RESPUESTA FINAL
    =============================== */
    echo json_encode([
        'success' => true,
        'code_transfer' => $code
    ]);

    exit;
}  

    throw new Exception('Accion no valida');

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
