<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'error'=>'auth']); exit; }
if (($_SESSION['user_role'] ?? '') !== 'cuadrillero') { echo json_encode(['ok'=>false,'error'=>'role']); exit; }

$fincaId = isset($_POST['finca_id']) ? (int) $_POST['finca_id'] : 0;
$peonId = isset($_POST['peon_id']) ? (int) $_POST['peon_id'] : 0;
$marcar = isset($_POST['marcar']) ? ($_POST['marcar'] === '1') : false;
if ($fincaId <= 0 || $peonId <= 0) { echo json_encode(['ok'=>false,'error'=>'params']); exit; }

$key = 'asistencia_finca_' . $fincaId;
if (!isset($_SESSION[$key])) { $_SESSION[$key] = []; }
if ($marcar) { $_SESSION[$key][$peonId] = true; } else { unset($_SESSION[$key][$peonId]); }

echo json_encode(['ok'=>true,'presente'=> isset($_SESSION[$key][$peonId])]);
