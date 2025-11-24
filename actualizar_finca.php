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
$cuadrilleroRaw = $_POST['cuadrillero_id'] ?? '';

$cuadrilleroId = null;
if ($cuadrilleroRaw !== '') {
    if (!ctype_digit((string)$cuadrilleroRaw)) {
        $respond(false, 'Cuadrillero inválido.');
    }
    $cuadrilleroId = (int)$cuadrilleroRaw;
    try {
        $stmtCheckForeman = $pdo->prepare('SELECT id, nombre, rol FROM usuarios WHERE id = :id LIMIT 1');
        $stmtCheckForeman->execute([':id' => $cuadrilleroId]);
        $foremanRow = $stmtCheckForeman->fetch(PDO::FETCH_ASSOC);
        if (!$foremanRow || ($foremanRow['rol'] ?? '') !== 'cuadrillero') {
            $respond(false, 'El cuadrillero seleccionado no existe.');
        }
    } catch (Throwable $e) {
        $respond(false, 'Error validando cuadrillero.');
    }
}

if ($id <= 0) {
    $respond(false, 'ID de finca inválido.');
}

if ($nombre === '' || $linkUbicacion === '') {
    $respond(false, 'Nombre y link de ubicación son obligatorios.');
}

try {
    $stmt = $pdo->prepare('UPDATE fincas SET nombre = :nombre, link_ubicacion = :link_ubicacion, descripcion = :descripcion, tarea_asignada = :tarea_asignada, observacion = :observacion, cuadrillero_id = :cuadrillero_id WHERE id = :id');
    $stmt->execute([
        ':nombre'          => $nombre,
        ':link_ubicacion'  => $linkUbicacion,
        ':descripcion'     => $descripcion,
        ':tarea_asignada'  => $tareaAsignada,
        ':observacion'     => $observacion,
        ':cuadrillero_id'  => $cuadrilleroId,
        ':id'              => $id,
    ]);
    $stmtFetch = $pdo->prepare('SELECT f.id, f.nombre, f.link_ubicacion, f.descripcion, f.tarea_asignada, f.observacion, f.cuadrillero_id, u.nombre AS cuadrillero_nombre FROM fincas f LEFT JOIN usuarios u ON u.id = f.cuadrillero_id WHERE f.id = :id');
    $stmtFetch->execute([':id' => $id]);
    $updatedFinca = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    $respond(true, 'Finca actualizada correctamente.', ['finca' => $updatedFinca]);
} catch (Throwable $e) {
    error_log('Error actualizando finca: ' . $e->getMessage());
    $respond(false, 'Ocurrió un error al actualizar la finca.');
}
