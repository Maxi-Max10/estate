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
        $qs = http_build_query(['foreman' => $success ? 'ok' : 'error']);
        header('Location: cuadrilleros-admin.php?' . $qs);
    }
    exit;
};

$nombre = trim($_POST['nombre'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$passwordConfirm = (string)($_POST['password_confirm'] ?? '');

if ($nombre === '' || $email === '' || $password === '' || $passwordConfirm === '') {
    $respond(false, 'Todos los campos son obligatorios.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $respond(false, 'El email no es válido.');
}

if ($password !== $passwordConfirm) {
    $respond(false, 'Las contraseñas no coinciden.');
}

if (strlen($password) < 6) {
    $respond(false, 'La contraseña debe tener al menos 6 caracteres.');
}

try {
    $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $stmtCheck->execute([':email' => $email]);
    if ($stmtCheck->fetch()) {
        $respond(false, 'Ya existe un usuario con ese email.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (email, password_hash, rol, nombre) VALUES (:email, :hash, :rol, :nombre)');
    $stmt->execute([
        ':email' => $email,
        ':hash' => $hash,
        ':rol' => 'cuadrillero',
        ':nombre' => $nombre,
    ]);
    $newId = (int)$pdo->lastInsertId();

    $stmtFetch = $pdo->prepare('SELECT id, nombre, email, creado_en, actualizado_en FROM usuarios WHERE id = :id');
    $stmtFetch->execute([':id' => $newId]);
    $foreman = $stmtFetch->fetch(PDO::FETCH_ASSOC) ?: [
        'id' => $newId,
        'nombre' => $nombre,
        'email' => $email,
        'creado_en' => null,
        'actualizado_en' => null,
    ];

    $respond(true, 'Cuadrillero creado correctamente.', ['foreman' => $foreman]);
} catch (Throwable $e) {
    error_log('Error creando cuadrillero: ' . $e->getMessage());
    $respond(false, 'Ocurrió un error al guardar.');
}
