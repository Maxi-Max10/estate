<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (($_SESSION['user_role'] ?? '') !== 'cuadrillero') { header('Location: panel-admin.php'); exit; }
require_once __DIR__ . '/config.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$redirectFinca = isset($_POST['redirect_finca']) ? (int) $_POST['redirect_finca'] : 0;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel-cuadrillero.php');
    exit;
}

$nombre = trim((string)($_POST['nombre'] ?? ''));
$apellido = trim((string)($_POST['apellido'] ?? ''));
$dni = trim((string)($_POST['dni'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$fechaIngreso = $_POST['fecha_ingreso'] ?? date('Y-m-d');

if ($nombre === '' || $dni === '') {
    $_SESSION['flash_error'] = 'Nombre y DNI son obligatorios.';
    header('Location: finca-detalle.php?id=' . $redirectFinca);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO peones (nombre, apellido, dni, fecha_ingreso, estado, telefono, cuadrilla_id) VALUES (:n, :a, :dni, :fi, :estado, :tel, :cid)');
    $stmt->execute([
        ':n' => $nombre,
        ':a' => $apellido,
        ':dni' => $dni,
        ':fi' => $fechaIngreso,
        ':estado' => 'activo',
        ':tel' => $telefono !== '' ? $telefono : null,
        ':cid' => $userId,
    ]);
    // Marcar asistencia presente por defecto para la finca destino (sesión temporal)
    if ($redirectFinca > 0) {
        $newPeonId = (int) $pdo->lastInsertId();
        if ($newPeonId > 0) {
            $attendanceKey = 'asistencia_finca_' . $redirectFinca;
            if (!isset($_SESSION[$attendanceKey])) { $_SESSION[$attendanceKey] = []; }
            $_SESSION[$attendanceKey][$newPeonId] = true; // presente por defecto
            // Persistir también en la tabla de asistencias para el día de hoy
            try {
                $stmtA = $pdo->prepare('INSERT INTO asistencias_peones (finca_id, peon_id, fecha, presente) VALUES (:f, :p, CURDATE(), 1) ON DUPLICATE KEY UPDATE presente = 1');
                $stmtA->execute([':f'=>$redirectFinca, ':p'=>$newPeonId]);
            } catch (Throwable $e2) {
                error_log('No se pudo registrar asistencia por defecto: ' . $e2->getMessage());
            }
        }
    }
    $_SESSION['flash_success'] = 'Peón creado correctamente';
} catch (Throwable $e) {
    error_log('Error creando peón: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Error al crear el peón';
}

header('Location: finca-detalle.php?id=' . $redirectFinca);
exit;