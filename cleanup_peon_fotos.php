<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

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

    echo "OK\n";
} catch (Throwable $e) {
    error_log('Error limpiando fotos de peones: ' . $e->getMessage());
    http_response_code(500);
    echo "ERROR\n";
}
