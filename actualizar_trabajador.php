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
        $query = http_build_query(['workerUpdate' => $status]);
        header('Location: panel-admin.php?' . $query);
    }
    exit;
};

$id            = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$nombre        = trim($_POST['nombre'] ?? '');
$documento     = trim($_POST['documento'] ?? '');
$rol           = trim($_POST['rol'] ?? '');
$fincaIdRaw    = $_POST['finca_id'] ?? null;
$inicio        = trim($_POST['inicio'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');
$especialidad  = trim($_POST['especialidad'] ?? '');

$validRoles = ['admin', 'cuadrillero', 'colaborador', 'supervisor'];

if ($id <= 0) {
    $respond(false, 'ID de trabajador inválido.');
}

if ($nombre === '' || $documento === '' || !in_array($rol, $validRoles, true)) {
    $respond(false, 'Nombre, documento y rol son obligatorios.');
}

if ($inicio === '') {
    $respond(false, 'Selecciona la fecha de inicio.');
}

try {
    $inicioDate = new DateTimeImmutable($inicio);
    $inicio = $inicioDate->format('Y-m-d');
} catch (Throwable $e) {
    $respond(false, 'La fecha de inicio no es válida.');
}

$fincaId = null;
$fincaNombre = null;

if ($rol === 'cuadrillero') {
    $fincaId = is_numeric($fincaIdRaw) ? (int) $fincaIdRaw : null;
    if (!$fincaId) {
        $respond(false, 'Debes seleccionar una finca para el cuadrillero.');
    }

    $stmtFinca = $pdo->prepare('SELECT id, nombre FROM fincas WHERE id = :id LIMIT 1');
    $stmtFinca->execute([':id' => $fincaId]);
    $finca = $stmtFinca->fetch(PDO::FETCH_ASSOC);

    if (!$finca) {
        $respond(false, 'La finca seleccionada no existe.');
    }

    $fincaNombre = $finca['nombre'];
}

if ($rol === 'colaborador') {
    $especialidad = $especialidad ?: 'cosechador';
} else {
    $especialidad = null;
}

try {
    $stmt = $pdo->prepare('UPDATE trabajadores SET nombre = :nombre, documento = :documento, rol = :rol, finca_id = :finca_id, finca_nombre = :finca_nombre, especialidad = :especialidad, inicio_actividades = :inicio, observaciones = :observaciones WHERE id = :id');
    $stmt->execute([
        ':nombre'        => $nombre,
        ':documento'     => $documento,
        ':rol'           => $rol,
        ':finca_id'      => $fincaId,
        ':finca_nombre'  => $fincaNombre,
        ':especialidad'  => $especialidad,
        ':inicio'        => $inicio,
        ':observaciones' => $observaciones,
        ':id'            => $id,
    ]);
    $stmtFetch = $pdo->prepare('SELECT id, nombre, documento, rol, finca_id, finca_nombre, especialidad, inicio_actividades, observaciones FROM trabajadores WHERE id = :id');
    $stmtFetch->execute([':id' => $id]);
    $updatedWorker = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    $respond(true, 'Trabajador actualizado correctamente.', ['trabajador' => $updatedWorker]);
} catch (Throwable $e) {
    error_log('Error actualizando trabajador: ' . $e->getMessage());
    $respond(false, 'Ocurrió un error al actualizar el trabajador.');
}
