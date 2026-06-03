<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/apiRequest.controller.php';
require_once __DIR__ . '/../controllers/ventas.controller.php';

$orderId = $_GET['order_id'] ?? null;

$estado  = 'error';
$detalle = null;
$venta   = null;

if ($orderId) {

    $res = ApiRequest::get('sales', [
        'linkTo'  => 'code_sale',
        'equalTo' => $orderId
    ]);

    if (ApiRequest::isSuccess($res) && !empty($res->results)) {
        $venta   = $res->results[0];
        $detalle = VentasController::obtenerDetalleVenta($venta->id_sale);
        $estado  = !empty($detalle['success']) ? 'ok' : 'pending';
    } else {
        $estado = 'pending';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de compra | Ap Fenix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos de marca (sin fondo) -->
    <style>
        body {
            background: #f7f7f7;
        }
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
        .brand-title {
            letter-spacing: -.02em;
        }
        .logo {
            max-width: 110px;
        }
    </style>

    <!-- Meta Pixel -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window,document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');

        fbq('init', '515720491212872');
    </script>
</head>

<body>

<?php if ($estado === 'ok'): ?>

    <!-- ✅ VENTA CONFIRMADA -->
    <div class="container py-3">
        <?= $detalle['html_recibo']; ?>

        <div class="mt-4 text-center no-print">
            <a href="https://apfenix.test"
               class="btn btn-brand px-5 py-3 shadow">
                Seguir comprando ⚡
            </a>
        </div>
    </div>

    <script>
        fbq('track','Purchase');
    </script>

<?php elseif ($estado === 'pending'): ?>

<!-- ⏳ PAGO EN PROCESO -->
<div class="container mt-3 d-flex align-items-center justify-content-center px-3">
    <div class="brand-card shadow-lg p-4 text-center w-100"
         style="max-width:460px;">

        <span class="brand-badge mb-3 d-inline-block">
            Confirmación en curso
        </span>

        <div class="my-3">
            <div class="spinner-border spinner-brand" role="status"></div>
        </div>

        <h4 class="fw-bold mb-2 brand-title">
            Tu pago está siendo confirmado..
        </h4>

        <p class="text-muted mb-3">
            Estamos esperando la confirmación final de tu entidad bancaria.
        </p>

        <div class="alert alert-success small text-start">
            <strong>Información importante:</strong><br>
            ✔ No necesitas realizar ningún pago adicional<br>
            ✔ Tu compra está registrada en nuestro sistema<br>
            ✔ En cuanto se confirme, recibirás un correo automáticamente, o puedes recargar est página.
        </div>

        <p class="small text-muted mb-3">
            🔒 Operación protegida • Plataforma segura • Proceso automático
        </p>

        <button onclick="location.reload()"
                class="btn btn-brand w-100 py-3 mb-3">
            Verificar estado 🔄
        </button>

        <a href="https://apfenix.test"
           class="text-decoration-none text-muted small">
            Volver al inicio
        </a>
    </div>
</div>


<?php else: ?>

    <!-- ❌ ERROR -->
    <div class="container d-flex align-items-center justify-content-center px-3">
        <div class="brand-card p-5 text-center shadow"
             style="max-width:420px;">
            <h4 class="fw-bold mb-2">Código no válido</h4>
            <p class="text-muted">
                El enlace no es correcto o ya expiró.
            </p>
            <a href="https://apfenix.test"
               class="btn btn-brand px-4 py-2">
                Volver al inicio
            </a>
        </div>
    </div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
