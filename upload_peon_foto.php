<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'cuadrillero') {
    header('Location: panel-admin.php');
    exit;
}

require_once __DIR__ . '/config.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$peonId = (int) ($_POST['peon_id'] ?? 0);

if ($peonId <= 0) {
    header('Location: panel-cuadrillero.php?foto=missing');
    exit;
}

try {
    $stmtPeon = $pdo->prepare('SELECT id FROM peones WHERE id = :peon_id AND cuadrilla_id = :cuadrilla_id');
    $stmtPeon->execute([
        ':peon_id' => $peonId,
        ':cuadrilla_id' => $userId,
    ]);
    $peon = $stmtPeon->fetch(PDO::FETCH_ASSOC);
    if (!$peon) {
        header('Location: panel-cuadrillero.php?foto=forbidden');
        exit;
    }
} catch (Throwable $e) {
    error_log('Error validando peón: ' . $e->getMessage());
    header('Location: panel-cuadrillero.php?foto=error');
    exit;
}

if (!isset($_FILES['foto_tarea']) || ($_FILES['foto_tarea']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    header('Location: panel-cuadrillero.php?foto=missing');
    exit;
}

$uploadedFile = $_FILES['foto_tarea'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (($uploadedFile['size'] ?? 0) > $maxSize) {
    header('Location: panel-cuadrillero.php?foto=size');
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($uploadedFile['tmp_name']);
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

if (!isset($allowedTypes[$mimeType])) {
    header('Location: panel-cuadrillero.php?foto=type');
    exit;
}

$uploadDir = __DIR__ . '/assets/uploads/peones';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$filename = sprintf(
    'peon_%d_%s_%s.%s',
    $peonId,
    date('Ymd_His'),
    bin2hex(random_bytes(4)),
    $allowedTypes[$mimeType]
);

$destination = $uploadDir . '/' . $filename;
$relativePath = 'assets/uploads/peones/' . $filename;

if (!move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
    header('Location: panel-cuadrillero.php?foto=error');
    exit;
}

try {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS peon_tarea_fotos (
            id INT NOT NULL AUTO_INCREMENT,
            peon_id INT NOT NULL,
            cuadrillero_id INT NOT NULL,
            foto_path VARCHAR(255) NOT NULL,
            nota VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_peon (peon_id),
            KEY idx_cuadrillero (cuadrillero_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $stmtOld = $pdo->prepare(
        'SELECT id, foto_path FROM peon_tarea_fotos WHERE created_at < (NOW() - INTERVAL 7 DAY)'
    );
    $stmtOld->execute();
    $oldPhotos = $stmtOld->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($oldPhotos) {
        $stmtDelete = $pdo->prepare('DELETE FROM peon_tarea_fotos WHERE id = :id');
        foreach ($oldPhotos as $old) {
            $path = (string) ($old['foto_path'] ?? '');
            if ($path !== '') {
                $fullPath = __DIR__ . '/' . ltrim($path, '/');
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
            $stmtDelete->execute([':id' => (int) $old['id']]);
        }
    }

    $stmtInsert = $pdo->prepare(
        'INSERT INTO peon_tarea_fotos (peon_id, cuadrillero_id, foto_path) VALUES (:peon_id, :cuadrillero_id, :foto_path)'
    );
    $stmtInsert->execute([
        ':peon_id' => $peonId,
        ':cuadrillero_id' => $userId,
        ':foto_path' => $relativePath,
    ]);
} catch (Throwable $e) {
    error_log('Error guardando foto de peón: ' . $e->getMessage());
    header('Location: panel-cuadrillero.php?foto=error');
    exit;
}

header('Location: panel-cuadrillero.php?foto=ok');
exit;
