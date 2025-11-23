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
        $query = http_build_query(['worker' => $status]);
        header('Location: panel-admin.php?' . $query);
    }

    exit;
};

$nombre        = trim($_POST['nombre'] ?? '');
$documento     = trim($_POST['documento'] ?? '');
$rol           = trim($_POST['rol'] ?? '');
$fincaIdRaw    = $_POST['finca_id'] ?? null;
$inicio        = trim($_POST['inicio'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');
$especialidad  = trim($_POST['especialidad'] ?? '');

$validRoles = ['admin', 'cuadrillero', 'colaborador', 'supervisor'];

if ($nombre === '' || $documento === '' || $rol === '' || !in_array($rol, $validRoles, true)) {
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
} else {
    $fincaId = null;
    $fincaNombre = null;
}

if ($rol === 'colaborador') {
    $especialidad = $especialidad ?: 'cosechador';
} else {
    $especialidad = null;
}

try {
    $stmt = $pdo->prepare('INSERT INTO trabajadores (nombre, documento, rol, finca_id, finca_nombre, especialidad, inicio_actividades, observaciones) VALUES (:nombre, :documento, :rol, :finca_id, :finca_nombre, :especialidad, :inicio, :observaciones)');
    $stmt->execute([
        ':nombre'        => $nombre,
        ':documento'     => $documento,
        ':rol'           => $rol,
        ':finca_id'      => $fincaId,
        ':finca_nombre'  => $fincaNombre,
        ':especialidad'  => $especialidad,
        ':inicio'        => $inicio,
        ':observaciones' => $observaciones,
    ]);

    $respond(true, 'Trabajador guardado correctamente.');
} catch (Throwable $e) {
    error_log('Error al guardar trabajador: ' . $e->getMessage());
    $respond(false, 'Ocurrió un error al guardar el trabajador.');
}
