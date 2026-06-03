<?php
require_once "config/config.php";

// Si ya está logueado, redirigir al dashboard según rol
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    require_once ROOT_PATH . '/includes/auth.php';
    Auth::redirectToDashboard();
}

$error = $_GET['error'] ?? '';
$detail = $_GET['detail'] ?? '';

$messages = [
    'missing' => '⚠️ Completa usuario y contraseña.',
    'bad_credentials' => '❌ Usuario o contraseña incorrectos.',
    'session_expired' => '⏱️ Tu sesión ha expirado. Ingresa nuevamente.',
    'api' => '🔴 Error en la API. Intenta de nuevo.',
    'curl' => '🌐 No se pudo conectar a la API.',
    'json' => '⚠️ Respuesta inválida de la API.',
    'invalid_response' => '⚠️ Formato de respuesta incorrecto.',
    'no_token' => '🔑 No se generó el token de acceso.',
    'inactive' => '🚫 Tu cuenta está inactiva. Contacta al administrador.',
];

$msg = $messages[$error] ?? '';
if ($detail) {
    $msg .= "<br><small class='text-muted'>" . htmlspecialchars($detail) . "</small>";
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= SITE_NAME ?></title>
    <link rel="shortcut icon" type="image/png" href="./assets/images/logos/logo.ico" />
    <link rel="stylesheet" href="./assets/css/styles.min.css" />
    <link rel="stylesheet" href="./assets/css/icons/tabler-icons/tabler-icons.css" />
    <style>
        .password-field {
            position: relative;
        }
        .password-field .form-control {
            padding-right: 2.75rem;
            border-radius: 0.375rem;
        }
        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            padding: 0;
            border: none;
            background: transparent;
            color: #94a3b8;
            border-radius: 50%;
            transition: color 0.15s ease, background-color 0.15s ease;
        }
        .password-toggle:hover {
            color: #475569;
            background-color: rgba(148, 163, 184, 0.12);
        }
        .password-toggle:focus {
            outline: none;
            box-shadow: none;
        }
        .password-toggle i {
            font-size: 1.125rem;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        <div class="position-relative overflow-hidden text-bg-light min-vh-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center justify-content-center w-100">
                <div class="row justify-content-center w-100">
                    <div class="col-md-8 col-lg-6 col-xxl-3">
                        <div class="card mb-0">
                            <div class="card-body">
                                <a href="/" class="text-nowrap logo-img text-center d-block py-3 w-100">
                                    <img src="./assets/images/logos/logo.jpg" width="200" alt="">
                                </a>
                                <?php if ($msg): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?= $msg ?>
                                    </div>
                                <?php endif; ?>
                                <form action="functions/login.php" method="POST" autocomplete="off">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Usuario</label>
                                        <input type="text" value="" class="form-control" id="email" name="email" required>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password" class="form-label">Contraseña</label>
                                        <div class="password-field">
                                            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Mostrar contraseña" tabindex="-1">
                                                <i class="ti ti-eye" id="togglePasswordIcon"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2">
                                        Ingresar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="./assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="./assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon  = document.getElementById('togglePasswordIcon');
            const show  = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('ti-eye', !show);
            icon.classList.toggle('ti-eye-off', show);
            this.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    </script>
</body>
</html>