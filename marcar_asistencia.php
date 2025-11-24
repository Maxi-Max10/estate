<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'error'=>'auth']); exit; }
if (($_SESSION['user_role'] ?? '') !== 'cuadrillero') { echo json_encode(['ok'=>false,'error'=>'role']); exit; }

require_once __DIR__ . '/config.php';

$fincaId = isset($_POST['finca_id']) ? (int) $_POST['finca_id'] : 0;
$peonId = isset($_POST['peon_id']) ? (int) $_POST['peon_id'] : 0;
$marcar = isset($_POST['marcar']) ? ($_POST['marcar'] === '1') : false;
if ($fincaId <= 0 || $peonId <= 0) { echo json_encode(['ok'=>false,'error'=>'params']); exit; }

$fecha = date('Y-m-d');
$presente = $marcar ? 1 : 0;

try {
	// Upsert en tabla de asistencias (unique key finca_id, peon_id, fecha)
	$stmt = $pdo->prepare('INSERT INTO asistencias_peones (finca_id, peon_id, fecha, presente) VALUES (:finca, :peon, :fecha, :presente) ON DUPLICATE KEY UPDATE presente = VALUES(presente)');
	$stmt->execute([
		':finca' => $fincaId,
		':peon' => $peonId,
		':fecha' => $fecha,
		':presente' => $presente,
	]);
} catch (Throwable $e) {
	error_log('Error guardando asistencia: ' . $e->getMessage());
	echo json_encode(['ok'=>false,'error'=>'db']);
	exit;
}

// Mantener compatibilidad con contador en vista finca usando sesión y reseteo diario
$key = 'asistencia_finca_' . $fincaId;
$dateKey = $key . '_fecha';
if (!isset($_SESSION[$dateKey]) || $_SESSION[$dateKey] !== $fecha) {
	$_SESSION[$key] = [];
	$_SESSION[$dateKey] = $fecha;
}
if ($presente === 1) { $_SESSION[$key][$peonId] = true; } else { unset($_SESSION[$key][$peonId]); }

echo json_encode(['ok'=>true,'presente'=> $presente === 1]);
