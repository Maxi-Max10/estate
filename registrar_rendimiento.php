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

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel-cuadrillero.php');
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$peonId = (int) ($_POST['peon_id'] ?? 0);
$tareaId = (int) ($_POST['tarea_id'] ?? 0);
$cantidad = (int) ($_POST['cantidad'] ?? -1);
$fecha = date('Y-m-d');

if ($peonId <= 0 || $tareaId <= 0 || $cantidad < 0) {
    header('Location: panel-cuadrillero.php?rend=missing');
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
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS rendimiento_peones (
            id INT NOT NULL AUTO_INCREMENT,
            peon_id INT NOT NULL,
            cuadrillero_id INT NOT NULL,
            tarea_id INT NOT NULL,
            cantidad INT NOT NULL DEFAULT 0,
            fecha DATE NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_peon_fecha (peon_id, fecha),
            KEY idx_cuadrillero (cuadrillero_id),
            KEY idx_tarea (tarea_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notificaciones (
            id INT NOT NULL AUTO_INCREMENT,
            rol_destino ENUM("admin","cuadrillero") NOT NULL,
            usuario_id INT NULL,
            titulo VARCHAR(150) NOT NULL,
            mensaje TEXT NOT NULL,
            leida TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_rol (rol_destino),
            KEY idx_usuario (usuario_id),
            KEY idx_fecha (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $stmtPeon = $pdo->prepare('SELECT id, nombre, apellido FROM peones WHERE id = :id AND cuadrilla_id = :cuadrilla_id');
    $stmtPeon->execute([':id' => $peonId, ':cuadrilla_id' => $userId]);
    $peon = $stmtPeon->fetch(PDO::FETCH_ASSOC);
    if (!$peon) {
        header('Location: panel-cuadrillero.php?rend=error');
        exit;
    }

    $stmtTask = $pdo->prepare('SELECT id, nombre, unidad, cantidad_normal FROM tareas_estandar WHERE id = :id');
    $stmtTask->execute([':id' => $tareaId]);
    $task = $stmtTask->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        header('Location: panel-cuadrillero.php?rend=error');
        exit;
    }

    $stmtInsert = $pdo->prepare(
        'INSERT INTO rendimiento_peones (peon_id, cuadrillero_id, tarea_id, cantidad, fecha)
         VALUES (:peon_id, :cuadrillero_id, :tarea_id, :cantidad, :fecha)'
    );
    $stmtInsert->execute([
        ':peon_id' => $peonId,
        ':cuadrillero_id' => $userId,
        ':tarea_id' => $tareaId,
        ':cantidad' => $cantidad,
        ':fecha' => $fecha,
    ]);

    $expected = (int) ($task['cantidad_normal'] ?? 0);
    if ($expected > 0 && $cantidad < $expected) {
        $peonName = trim((string) ($peon['nombre'] ?? '') . ' ' . ($peon['apellido'] ?? ''));
        $taskName = (string) ($task['nombre'] ?? 'Tarea');
        $unit = (string) ($task['unidad'] ?? 'unidades');
        $message = sprintf(
            'Peón %s registró %d %s en "%s". Estándar: %d %s/día.',
            $peonName !== '' ? $peonName : 'sin nombre',
            $cantidad,
            $unit,
            $taskName,
            $expected,
            $unit
        );

        $stmtNotif = $pdo->prepare(
            'INSERT INTO notificaciones (rol_destino, usuario_id, titulo, mensaje)
             VALUES (:rol, :usuario_id, :titulo, :mensaje)'
        );

        $stmtNotif->execute([
            ':rol' => 'admin',
            ':usuario_id' => null,
            ':titulo' => 'Baja de rendimiento detectada',
            ':mensaje' => $message,
        ]);

        $stmtNotif->execute([
            ':rol' => 'cuadrillero',
            ':usuario_id' => $userId,
            ':titulo' => 'Rendimiento bajo registrado',
            ':mensaje' => $message,
        ]);

        header('Location: panel-cuadrillero.php?rend=low');
        exit;
    }
} catch (Throwable $e) {
    error_log('Error registrando rendimiento: ' . $e->getMessage());
    header('Location: panel-cuadrillero.php?rend=error');
    exit;
}

header('Location: panel-cuadrillero.php?rend=ok');
exit;
