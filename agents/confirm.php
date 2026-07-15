<?php
/**
 * Confirmación pública — ventas Agente IA
 * URL: /agents/confirm?code=CARO-001
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/agentsController.php';

header('Content-Type: text/html; charset=utf-8');

$code = trim($_GET['code'] ?? '');
$agent = $code !== '' ? AgentsController::obtenerPorCode($code) : null;
$estado = 'error';
$detalle = null;
$codigo = $code !== '' ? $code : '---';

if ($agent && isset($agent['status_agent'])) {
    $status = (int) $agent['status_agent'];
    $estado = match ($status) {
        AgentsController::STATUS_PENDIENTE => 'pending',
        AgentsController::STATUS_VALIDADO  => 'approved',
        AgentsController::STATUS_RECHAZADO => 'rejected',
        AgentsController::STATUS_ERROR     => 'error',
        default => 'error',
    };
    $codigo = $agent['code_agent'] ?? $code;
}

if ($estado === 'approved') {
    $detalle = AgentsController::obtenerReciboPublicoPorCodigo($code);
    if (empty($detalle['success'])) {
        $estado = 'pending';
    }
}

$homeUrl = rtrim(BASE_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tus números | Ap Fenix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; font-family: 'Segoe UI', Roboto, sans-serif; }
        .brand-card {
            background: #ffffff;
            color: #1b1b1b;
            border-radius: 1.5rem;
        }
        .brand-badge {
            background: #f5c542;
            color: #1b1b1b;
            font-weight: 700;
            border-radius: 50px;
            padding: .4rem 1rem;
            font-size: .85rem;
        }
        .btn-brand {
            background: #1b1b1b;
            color: #ffffff;
            border-radius: 50px;
            font-weight: 700;
            border: none;
        }
        .btn-brand:hover {
            background: #000000;
            color: #ffffff;
        }
        .spinner-brand {
            width: 3.2rem;
            height: 3.2rem;
            border-width: .35rem;
            color: #d500f9;
        }
        .card-custom {
            border-radius: 1.5rem;
            max-width: 420px;
            width: 100%;
            border: none;
        }
        .btn-whatsapp {
            background: linear-gradient(45deg, #25D366, #128C7E);
            color: white !important;
            border-radius: 50px;
            font-weight: 600;
            border: none;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window,document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1574275570334087');
    fbq('track', 'PageView');
    </script>
</head>
<body>

<?php if ($estado === 'approved' && !empty($detalle['html_recibo'])): ?>

    <div class="container py-3">
        <?= $detalle['html_recibo']; ?>

        <div class="mt-4 text-center no-print">
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="btn btn-brand px-5 py-3 shadow">
                Seguir comprando ⚡
            </a>
        </div>
    </div>

    <?php include ROOT_PATH . '/includes/btn-share.php'; ?>

    <script>
        fbq('track', 'Purchase');
    </script>

<?php elseif ($estado === 'pending'): ?>

    <div class="container mt-5 d-flex justify-content-center px-3">
        <div class="brand-card shadow-lg p-4 text-center w-100" style="max-width:460px;">
            <span class="brand-badge mb-3 d-inline-block">Validación en curso</span>

            <div class="my-3">
                <div class="spinner-border spinner-brand" role="status"></div>
            </div>

            <h4 class="fw-bold mb-2">⏳ Pago en validación</h4>
            <p class="text-muted mb-3">
                Hemos recibido tu comprobante.<br>
                Estamos validando tu pago en el sistema.
            </p>

            <div class="alert alert-warning py-2">
                <small class="d-block text-uppercase fw-bold" style="font-size:0.7rem;">Código de compra:</small>
                <strong><?= htmlspecialchars($codigo) ?></strong>
            </div>

            <p class="small text-muted mb-3">
                En cuanto se valide, podrás ver tus números en esta misma página.
            </p>

            <button onclick="location.reload()" class="btn btn-brand w-100 py-3 mb-3">
                Actualizar estado 🔄
            </button>

            <a href="<?= htmlspecialchars($homeUrl) ?>" class="text-decoration-none text-muted small">
                Volver al inicio
            </a>
        </div>
    </div>

<?php elseif ($estado === 'rejected'): ?>

    <div class="container mt-5 d-flex justify-content-center px-3">
        <div class="card card-custom shadow-lg p-4 text-center">
            <div style="font-size:3rem;margin-bottom:1rem;">❌</div>
            <h4 class="fw-bold text-danger">Pago rechazado</h4>
            <p class="text-muted">
                No pudimos validar tu comprobante.<br>
                Escríbenos para revisar qué sucedió.
            </p>
            <div class="alert alert-light py-2 mb-3">
                <small class="text-muted">Código:</small>
                <strong><?= htmlspecialchars($codigo) ?></strong>
            </div>
            <a href="https://wa.me/573106817993?text=<?= rawurlencode('Hola, mi código ' . $codigo . ' fue rechazado. Necesito ayuda.') ?>"
               target="_blank" rel="noopener" class="btn btn-whatsapp w-100 py-2 mb-3">
                📲 Contactar soporte
            </a>
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="btn btn-brand w-100 py-2">
                Volver al inicio
            </a>
        </div>
    </div>

<?php else: ?>

    <div class="container mt-5 d-flex justify-content-center px-3">
        <div class="card card-custom shadow-lg p-4 text-center">
            <div style="font-size:3rem;margin-bottom:1rem;">❓</div>
            <h4 class="fw-bold">Código inválido</h4>
            <p class="text-muted">El enlace no es válido o ya expiró.</p>
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="btn btn-brand w-100 mt-3 py-2">
                Volver al inicio
            </a>
        </div>
    </div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
