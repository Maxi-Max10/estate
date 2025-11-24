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

$respond = function(bool $success, string $message = '', array $extra = []) use ($isAjax): void {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    } else {
        $qs = http_build_query(['foremanUpdate' => $success ? 'ok' : 'error']);
        header('Location: cuadrilleros-admin.php?' . $qs);
    }
    exit;
};

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nombre = trim($_POST['nombre'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = trim($_POST['password'] ?? '');

if ($id <= 0) {
    $respond(false, 'ID inválido.');
}
if ($nombre === '' || $email === '') {
    $respond(false, 'Nombre y email son obligatorios.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $respond(false, 'Email inválido.');
}

try {
    $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1');
    $stmtCheck->execute([':email' => $email, ':id' => $id]);
    if ($stmtCheck->fetch()) {
        $respond(false, 'Otro usuario ya posee ese email.');
    }

    if ($password !== '') {
        if (strlen($password) < 6) {
            $respond(false, 'La nueva contraseña es demasiado corta.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE usuarios SET nombre = :nombre, email = :email, password_hash = :hash WHERE id = :id AND rol = :rol');
        $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':hash' => $hash,
            ':id' => $id,
            ':rol' => 'cuadrillero',
        ]);
    } else {
        $stmt = $pdo->prepare('UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id AND rol = :rol');
        $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':id' => $id,
            ':rol' => 'cuadrillero',
        ]);
    }

    $stmtFetch = $pdo->prepare('SELECT id, nombre, email, creado_en, actualizado_en FROM usuarios WHERE id = :id AND rol = :rol');
    $stmtFetch->execute([':id' => $id, ':rol' => 'cuadrillero']);
    $foreman = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    $respond(true, 'Cuadrillero actualizado correctamente.', ['foreman' => $foreman]);
} catch (Throwable $e) {
    error_log('Error actualizando cuadrillero: ' . $e->getMessage());
    $respond(false, 'No se pudo actualizar.');
}
