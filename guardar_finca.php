<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel-admin.php');
    exit;
}

$nombre         = trim($_POST['nombre'] ?? '');
$linkUbicacion  = trim($_POST['link_ubicacion'] ?? '');
$descripcion    = trim($_POST['descripcion'] ?? '');
$tareaAsignada  = trim($_POST['tarea_asignada'] ?? '');
$observacion    = trim($_POST['observacion'] ?? '');

if ($nombre === '' || $linkUbicacion === '') {
    header('Location: panel-admin.php');
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO fincas (nombre, link_ubicacion, descripcion, tarea_asignada, observacion) VALUES (:nombre, :link_ubicacion, :descripcion, :tarea_asignada, :observacion)');
    $stmt->execute([
        ':nombre'         => $nombre,
        ':link_ubicacion' => $linkUbicacion,
        ':descripcion'    => $descripcion,
        ':tarea_asignada' => $tareaAsignada,
        ':observacion'    => $observacion,
    ]);

    header('Location: panel-admin.php');
    exit;
} catch (Throwable $e) {
    // En un entorno real podrías loguear el error
    header('Location: panel-admin.php');
    exit;
}
