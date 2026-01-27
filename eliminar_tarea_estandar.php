<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: panel-cuadrillero.php');
    exit;
}

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel-admin.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: panel-admin.php?tarea=error');
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM tareas_estandar WHERE id = :id');
    $stmt->execute([':id' => $id]);
} catch (Throwable $e) {
    error_log('Error eliminando estándar: ' . $e->getMessage());
    header('Location: panel-admin.php?tarea=error');
    exit;
}

header('Location: panel-admin.php?tarea=deleted');
exit;
