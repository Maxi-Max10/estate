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
$apellido      = trim($_POST['apellido'] ?? '');
$dni           = trim($_POST['dni'] ?? '');
$fechaIngreso  = trim($_POST['fecha_ingreso'] ?? '');
$estado        = strtolower(trim($_POST['estado'] ?? 'activo'));
$telefono      = trim($_POST['telefono'] ?? '');
$cuadrillaRaw  = $_POST['cuadrilla_id'] ?? null;

if ($nombre === '' || $apellido === '' || $dni === '' || $fechaIngreso === '') {
    $respond(false, 'Nombre, apellido, DNI y fecha de ingreso son obligatorios.');
}

if (!in_array($estado, ['activo', 'inactivo'], true)) {
    $estado = 'activo';
}

try {
    $fecha = new DateTimeImmutable($fechaIngreso);
    $fechaIngreso = $fecha->format('Y-m-d');
} catch (Throwable $e) {
    $respond(false, 'La fecha de ingreso no es válida.');
}

$cuadrillaId = null;
$cuadrillaNombre = null;
if ($cuadrillaRaw !== null && $cuadrillaRaw !== '') {
    if (!is_numeric($cuadrillaRaw)) {
        $respond(false, 'El cuadrillero seleccionado no es válido.');
    }

    $cuadrillaId = (int) $cuadrillaRaw;
    $stmtUser = $pdo->prepare('SELECT id, nombre, rol FROM usuarios WHERE id = :id LIMIT 1');
    $stmtUser->execute([':id' => $cuadrillaId]);
    $cuadrillero = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$cuadrillero || ($cuadrillero['rol'] ?? '') !== 'cuadrillero') {
        $respond(false, 'El cuadrillero seleccionado no existe.');
    }

    $cuadrillaNombre = $cuadrillero['nombre'] ?? null;
}

try {
    $stmt = $pdo->prepare('INSERT INTO peones (nombre, apellido, dni, fecha_ingreso, estado, telefono, cuadrilla_id) VALUES (:nombre, :apellido, :dni, :fecha_ingreso, :estado, :telefono, :cuadrilla_id)');
    $stmt->execute([
        ':nombre'        => $nombre,
        ':apellido'      => $apellido,
        ':dni'           => $dni,
        ':fecha_ingreso' => $fechaIngreso,
        ':estado'        => $estado,
        ':telefono'      => $telefono ?: null,
        ':cuadrilla_id'  => $cuadrillaId,
    ]);

    $newId = (int) $pdo->lastInsertId();
    $stmtFetch = $pdo->prepare('SELECT p.*, u.nombre AS cuadrilla_nombre FROM peones p LEFT JOIN usuarios u ON u.id = p.cuadrilla_id WHERE p.id = :id');
    $stmtFetch->execute([':id' => $newId]);
    $newPeon = $stmtFetch->fetch(PDO::FETCH_ASSOC) ?: [
        'id' => $newId,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'dni' => $dni,
        'fecha_ingreso' => $fechaIngreso,
        'estado' => $estado,
        'telefono' => $telefono,
        'cuadrilla_id' => $cuadrillaId,
        'cuadrilla_nombre' => $cuadrillaNombre,
    ];

    $respond(true, 'Peón registrado correctamente.', ['peon' => $newPeon]);
} catch (Throwable $e) {
    error_log('Error al guardar peón: ' . $e->getMessage());
    $respond(false, 'Ocurrió un error al guardar el peón.');
}
