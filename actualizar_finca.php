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

$isAjaxRequest = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
    || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

$respond = function (bool $success, string $message = '', array $extra = []) use ($isAjaxRequest): void {
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra));
    } else {
        $status = $success ? 'ok' : 'error';
        $query = http_build_query(['farmUpdate' => $status]);
        header('Location: panel-admin.php?' . $query);
    }
    exit;
};

$id            = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$nombre        = trim($_POST['nombre'] ?? '');
$linkUbicacion = trim($_POST['link_ubicacion'] ?? '');
$descripcion   = trim($_POST['descripcion'] ?? '');
$tareaAsignada = trim($_POST['tarea_asignada'] ?? '');
$observacion   = trim($_POST['observacion'] ?? '');

if ($id <= 0) {
    $respond(false, 'ID de finca inválido.');
}

if ($nombre === '' || $linkUbicacion === '') {
    $respond(false, 'Nombre y link de ubicación son obligatorios.');
}

try {
    $stmt = $pdo->prepare('UPDATE fincas SET nombre = :nombre, link_ubicacion = :link_ubicacion, descripcion = :descripcion, tarea_asignada = :tarea_asignada, observacion = :observacion WHERE id = :id');
    $stmt->execute([
        ':nombre'        => $nombre,
        ':link_ubicacion' => $linkUbicacion,
        ':descripcion'   => $descripcion,
        ':tarea_asignada'=> $tareaAsignada,
        ':observacion'   => $observacion,
        ':id'            => $id,
    ]);
    $stmtFetch = $pdo->prepare('SELECT id, nombre, link_ubicacion, descripcion, tarea_asignada, observacion FROM fincas WHERE id = :id');
    $stmtFetch->execute([':id' => $id]);
    $updatedFinca = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    $respond(true, 'Finca actualizada correctamente.', ['finca' => $updatedFinca]);
} catch (Throwable $e) {
    error_log('Error actualizando finca: ' . $e->getMessage());
    $respond(false, 'Ocurrió un error al actualizar la finca.');
}
