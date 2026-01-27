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

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$unidad = trim((string) ($_POST['unidad'] ?? ''));
$cantidad = (int) ($_POST['cantidad_normal'] ?? 0);

if ($nombre === '' || $unidad === '' || $cantidad < 0) {
    header('Location: panel-admin.php?tarea=error');
    exit;
}

try {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS tareas_estandar (
            id INT NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(150) NOT NULL,
            unidad VARCHAR(80) NOT NULL DEFAULT "unidades",
            cantidad_normal INT NOT NULL DEFAULT 0,
            actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tarea_nombre (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $stmt = $pdo->prepare(
        'INSERT INTO tareas_estandar (nombre, unidad, cantidad_normal)
         VALUES (:nombre, :unidad, :cantidad)
         ON DUPLICATE KEY UPDATE unidad = VALUES(unidad), cantidad_normal = VALUES(cantidad_normal), actualizado_en = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':unidad' => $unidad,
        ':cantidad' => $cantidad,
    ]);
} catch (Throwable $e) {
    error_log('Error guardando estándar: ' . $e->getMessage());
    header('Location: panel-admin.php?tarea=error');
    exit;
}

header('Location: panel-admin.php?tarea=ok');
exit;
