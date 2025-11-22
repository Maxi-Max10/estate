<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'cuadrillero') {
    header('Location: panel-admin.php');
    exit;
}

$userName = $_SESSION['user_name'] ?: 'Cuadrillero';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Cuadrillero | Estate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">Panel Cuadrillero</span>
            <div class="d-flex align-items-center gap-3 text-white">
                <span><strong>Hola:</strong> <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                <a class="btn btn-outline-light btn-sm" href="logout.php">Salir</a>
            </div>
        </div>
    </nav>
    <main class="container py-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">Panel operativo</h1>
                <p class="text-muted">
                    Usa este espacio para mostrar órdenes de trabajo, checklists y reportes diarios.
                </p>
                <div class="alert alert-success">
                    Rol autenticado: <strong><?php echo htmlspecialchars($_SESSION['user_role'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
