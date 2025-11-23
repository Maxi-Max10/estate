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

$respond = function (bool $success, string $message = '') use ($isAjaxRequest): void {
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    } else {
        $status = $success ? 'ok' : 'error';
        $query = http_build_query(['farmDelete' => $status]);
        header('Location: panel-admin.php?' . $query);
    }
    exit;
};

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    $respond(false, 'ID de finca inválido.');
}

try {
    $stmt = $pdo->prepare('DELETE FROM fincas WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $respond(true, 'Finca eliminada.');
} catch (Throwable $e) {
    error_log('Error eliminando finca: ' . $e->getMessage());
    $respond(false, 'Ocurrió un error al eliminar la finca.');
}
