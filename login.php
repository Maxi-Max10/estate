<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

$error = null;
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldEmail = trim($_POST['email'] ?? '');
    $email = filter_var($oldEmail, FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || $password === '') {
        $error = 'Ingresa un correo válido y una contraseña.';
    } else {
        $stmt = $pdo->prepare('SELECT id, email, password_hash, rol, nombre FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $storedPassword = $user['password_hash'] ?? '';
        if ($user && hash_equals($storedPassword, $password)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['user_name'] = $user['nombre'];

            $destination = $user['rol'] === 'admin' ? 'panel-admin.php' : 'panel-cuadrillero.php';
            header('Location: ' . $destination);
            exit;
        }

        $error = 'Correo o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso | Estate</title>
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e3a8a;
            --gradient-start: #0f172a;
            --gradient-end: #1d4ed8;
        }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at top, rgba(148,163,184,0.35), transparent 45%),
                        linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #0f172a;
        }

        .login-card {
            width: min(100%, 420px);
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(6px);
        }

        .login-header {
            padding: 2rem 2rem 1rem;
        }

        .brand-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
        }

        .login-footer {
            font-size: 0.9rem;
            color: #475569;
        }

        .role-hint {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        @media (max-width: 576px) {
            body {
                padding: 1rem;
            }

            .login-card {
                border-radius: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header text-center">
            <div class="brand-circle mb-3" style="background: #2563eb;">
                <!-- Ícono cohete SVG -->
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="12" fill="#2563eb"/><path d="M8.5 15.5L9.5 13.5L13.5 9.5L15.5 8.5L14.5 10.5L10.5 14.5L8.5 15.5Z" fill="#fff"/><circle cx="15" cy="9" r="1" fill="#fff"/></svg>
            </div>
            <h1 class="h4 mb-2">Bienvenido</h1>
            <p class="text-muted mb-0">Crea tu cuenta o inicia sesión para continuar</p>
            <p class="role-hint">Administra cuadrillas y fincas fácilmente</p>
        </div>
        <div class="card-body p-4 pt-0">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <form method="post" action="" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Ingresa tu nombre" autocomplete="name">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="usuario@empresa.com" value="<?php echo htmlspecialchars($oldEmail, ENT_QUOTES, 'UTF-8'); ?>" required>
                        <div class="invalid-feedback">Ingresa un correo válido.</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="********" minlength="8" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                        <div class="invalid-feedback">La contraseña debe tener al menos 8 caracteres.</div>
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="terms" name="terms" required>
                    <label class="form-check-label" for="terms">
                        Al registrarte aceptas los <a href="#" class="link-primary">Términos y Condiciones</a>
                    </label>
                    <div class="invalid-feedback">Debes aceptar los términos.</div>
                </div>
                <div class="d-grid gap-2 mb-2">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill">Sign Up</button>
                    <button type="button" class="btn btn-outline-dark rounded-pill" onclick="window.location.href='https://tu-dominio.com'">
                        Volver al sitio
                    </button>
                </div>
                <div class="text-center mb-2">
                    <button type="button" class="btn btn-light rounded-pill" onclick="window.location.href='login.php'">Sign In</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center login-footer">
            <span class="d-block">CREATOR HERE &nbsp;|&nbsp; DESIGNER HERE</span>
            <small>Estate · Seguridad y gestión para cuadrillas</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        togglePassword.addEventListener('click', () => {
            const currentType = passwordField.getAttribute('type');
            passwordField.setAttribute('type', currentType === 'password' ? 'text' : 'password');
            togglePassword.querySelector('i').classList.toggle('bi-eye');
            togglePassword.querySelector('i').classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>
