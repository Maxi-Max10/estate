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
        $query = http_build_query(['farm' => $status]);
        header('Location: panel-admin.php?' . $query);
    }

    exit;
};

$nombre         = trim($_POST['nombre'] ?? '');
$linkUbicacion  = trim($_POST['link_ubicacion'] ?? '');
$descripcion    = trim($_POST['descripcion'] ?? '');
$tareaAsignada  = trim($_POST['tarea_asignada'] ?? '');
$observacion    = trim($_POST['observacion'] ?? '');
$cuadrilleroRaw = $_POST['cuadrillero_id'] ?? '';

$cuadrilleroId = null;
if ($cuadrilleroRaw !== '') {
    if (!ctype_digit((string)$cuadrilleroRaw)) {
        $respond(false, 'Cuadrillero seleccionado inválido.');
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

if ($nombre === '' || $linkUbicacion === '') {
    $respond(false, 'El nombre y el link de ubicación son obligatorios.');
}

try {
    $stmt = $pdo->prepare('INSERT INTO fincas (nombre, link_ubicacion, descripcion, tarea_asignada, observacion, cuadrillero_id) VALUES (:nombre, :link_ubicacion, :descripcion, :tarea_asignada, :observacion, :cuadrillero_id)');
    $stmt->execute([
        ':nombre'         => $nombre,
        ':link_ubicacion' => $linkUbicacion,
        ':descripcion'    => $descripcion,
        ':tarea_asignada' => $tareaAsignada,
        ':observacion'    => $observacion,
        ':cuadrillero_id' => $cuadrilleroId,
    ]);

    $newFinca = [
        'id'                 => (int) $pdo->lastInsertId(),
        'nombre'             => $nombre,
        'link_ubicacion'     => $linkUbicacion,
        'descripcion'        => $descripcion,
        'tarea_asignada'     => $tareaAsignada,
        'observacion'        => $observacion,
        'cuadrillero_id'     => $cuadrilleroId,
        'cuadrillero_nombre' => $foremanRow['nombre'] ?? null,
    ];

    $respond(true, 'Finca guardada correctamente.', ['finca' => $newFinca]);
} catch (Throwable $e) {
    error_log('Error al guardar finca: ' . $e->getMessage());
    $respond(false, 'Ocurrió un error al guardar la finca.');
}
