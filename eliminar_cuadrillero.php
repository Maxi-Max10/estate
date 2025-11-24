<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cuadrilleros-admin.php');
    exit;
}

$isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
    || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

$respond = function(bool $success, string $message = '') use ($isAjax): void {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
    } else {
        $qs = http_build_query(['foremanDelete' => $success ? 'ok' : 'error']);
        header('Location: cuadrilleros-admin.php?' . $qs);
    }
    exit;
};

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    $respond(false, 'ID inválido.');
}

try {
    // Impedir borrar a uno mismo opcionalmente
    if ($id === (int)($_SESSION['user_id'])) {
        $respond(false, 'No puedes eliminar tu propia cuenta.');
    }

    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id AND rol = :rol');
    $stmt->execute([':id' => $id, ':rol' => 'cuadrillero']);
    $respond(true, 'Cuadrillero eliminado.');
} catch (Throwable $e) {
    error_log('Error eliminando cuadrillero: ' . $e->getMessage());
    $respond(false, 'No se pudo eliminar.');
}
