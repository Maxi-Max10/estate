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
$userEmail = strtolower((string) ($_SESSION['user_email'] ?? ''));
$userName = trim((string) ($_SESSION['user_name'] ?? 'Cuadrillero'));

// Cargar fincas asignadas según nuevo esquema (fincas.cuadrillero_id)
$assignedFarms = [];
try {
    $stmtFincas = $pdo->prepare('SELECT id, nombre, link_ubicacion, descripcion, tarea_asignada, observacion FROM fincas WHERE cuadrillero_id = :id ORDER BY nombre ASC');
    $stmtFincas->execute([':id' => $userId]);
    $assignedFarms = $stmtFincas->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('Error obteniendo fincas asignadas: ' . $e->getMessage());
}

// Cargar peones asignados (peones.cuadrilla_id = usuario cuadrillero)
$assignedWorkers = [];
try {
    $stmtWorkers = $pdo->prepare('SELECT id, nombre, apellido, dni, estado, fecha_ingreso, telefono FROM peones WHERE cuadrilla_id = :id ORDER BY nombre ASC, apellido ASC');
    $stmtWorkers->execute([':id' => $userId]);
    $assignedWorkers = $stmtWorkers->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('Error obteniendo peones asignados: ' . $e->getMessage());
}

$taskStandards = [];
$cuadrilleroNotifications = [];
try {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS tareas_estandar (
            id INT NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(150) NOT NULL,
            unidad VARCHAR(80) NOT NULL DEFAULT "unidades",
            cantidad_normal INT NOT NULL DEFAULT 0,
            actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tarea_nombre (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notificaciones (
            id INT NOT NULL AUTO_INCREMENT,
            rol_destino ENUM("admin","cuadrillero") NOT NULL,
            usuario_id INT NULL,
            titulo VARCHAR(150) NOT NULL,
            mensaje TEXT NOT NULL,
            leida TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_rol (rol_destino),
            KEY idx_usuario (usuario_id),
            KEY idx_fecha (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $stmtStandards = $pdo->query('SELECT id, nombre, unidad, cantidad_normal FROM tareas_estandar ORDER BY nombre ASC');
    $taskStandards = $stmtStandards->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtNotifs = $pdo->prepare('SELECT titulo, mensaje, created_at FROM notificaciones WHERE rol_destino = :rol AND usuario_id = :uid ORDER BY created_at DESC LIMIT 10');
    $stmtNotifs->execute([':rol' => 'cuadrillero', ':uid' => $userId]);
    $cuadrilleroNotifications = $stmtNotifs->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('Error cargando estándares/notificaciones: ' . $e->getMessage());
}

// Perfil básico del cuadrillero (se podría ampliar consultando usuarios)
$cuadrilleroProfile = [
    'nombre' => $userName,
    'documento' => null,
    'inicio' => null,
];

$farmsCount = count($assignedFarms);
$farmsWithTask = 0;
$farmsWithObservations = 0;
foreach ($assignedFarms as $farm) {
    $taskText = trim((string) ($farm['tarea_asignada'] ?? ''));
    if ($taskText !== '') {
        $parts = preg_split('/[\r\n]+|[.;]+/', $taskText);
        if (is_array($parts)) {
            foreach ($parts as $part) {
                if (trim($part) !== '') {
                    $farmsWithTask++;
                }
            }
        }
    }
    if (trim((string) ($farm['observacion'] ?? '')) !== '') {
        $farmsWithObservations++;
    }
}

// Estadísticas de peones
$workersTotal = count($assignedWorkers);
$workersActive = 0;
$workersInactive = 0;
foreach ($assignedWorkers as $w) {
    $estado = strtolower((string) ($w['estado'] ?? ''));
    if ($estado === 'inactivo') { $workersInactive++; } else { $workersActive++; }
}

$cuadrilleroStats = [
    'farms' => $farmsCount,
    'farmsLabel' => $farmsCount === 1 ? 'Finca asignada' : 'Fincas asignadas',
    'tasks' => $farmsWithTask,
    'tasksLabel' => $farmsWithTask === 1 ? 'Con tarea registrada' : 'Con tareas registradas',
    'alerts' => $farmsWithObservations,
    'alertsLabel' => $farmsWithObservations === 1 ? 'Observación pendiente' : 'Observaciones pendientes',
    'workers' => $workersTotal,
    'workersActive' => $workersActive,
    'workersInactive' => $workersInactive,
];

$photoStatus = trim((string) ($_GET['foto'] ?? ''));
$photoAlert = null;
$photoAlertClass = 'success';
if ($photoStatus !== '') {
    switch ($photoStatus) {
        case 'ok':
            $photoAlert = 'Foto cargada correctamente.';
            $photoAlertClass = 'success';
            break;
        case 'size':
            $photoAlert = 'La foto supera el tamaño permitido.';
            $photoAlertClass = 'warning';
            break;
        case 'type':
            $photoAlert = 'Formato no permitido. Usa JPG, PNG o WEBP.';
            $photoAlertClass = 'warning';
            break;
        case 'missing':
            $photoAlert = 'Selecciona una foto antes de subir.';
            $photoAlertClass = 'warning';
            break;
        case 'forbidden':
            $photoAlert = 'No tienes permiso para cargar fotos de este peón.';
            $photoAlertClass = 'danger';
            break;
        default:
            $photoAlert = 'No se pudo cargar la foto. Inténtalo nuevamente.';
            $photoAlertClass = 'danger';
            break;
    }
}

$rendStatus = trim((string) ($_GET['rend'] ?? ''));
$rendAlert = null;
$rendAlertClass = 'success';
if ($rendStatus !== '') {
    switch ($rendStatus) {
        case 'ok':
            $rendAlert = 'Rendimiento guardado correctamente.';
            $rendAlertClass = 'success';
            break;
        case 'low':
            $rendAlert = 'Rendimiento por debajo del estándar. Se notificó al administrador.';
            $rendAlertClass = 'warning';
            break;
        case 'missing':
            $rendAlert = 'Completa tarea y cantidad antes de guardar.';
            $rendAlertClass = 'warning';
            break;
        default:
            $rendAlert = 'No se pudo guardar el rendimiento.';
            $rendAlertClass = 'danger';
            break;
    }
}

if ($cuadrilleroProfile === null) {
    $cuadrilleroProfile = [
        'nombre' => $userName,
        'documento' => null,
        'inicio' => null,
        'match' => 0,
    ];
}

// Saludo dinámico según hora del servidor
$hourNow = (int) date('G');
if ($hourNow >= 5 && $hourNow < 12) {
    $dynamicGreeting = 'Buenos días';
} elseif ($hourNow >= 12 && $hourNow < 19) {
    $dynamicGreeting = 'Buenas tardes';
} else {
    $dynamicGreeting = 'Buenas noches';
}
// Opcional: agregar nombre
$dynamicGreeting .= ', ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Cuadrillero | Estate</title>
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/cuadrillero.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark cuadrillero-navbar">
        <div class="container-fluid py-3">
            <div>
                <span class="navbar-brand h3 mb-0">Estate · Operaciones</span>
                <p class="mb-0 text-white-50">Seguimiento diario de cuadrillas y tareas</p>
            </div>
            <div class="d-flex align-items-center gap-3 text-white">
                <span class="fw-semibold"><i class="bi bi-person-workspace me-2"></i><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                <a class="btn btn-danger btn-sm" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
            </div>
        </div>
    </nav>

    <!-- Banner con saludo dinámico -->
    <header class="banner-hero" role="banner" aria-label="Saludo del día">
        <div class="banner-greeting">
            <span><?php echo $dynamicGreeting; ?></span>
        </div>
    </header>

    <div class="dashboard-shell">
        <div class="container-fluid">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card summary-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1">Fincas asignadas</p>
                                <h3 id="summaryAsistencia"><?php echo $cuadrilleroStats['farms']; ?></h3>
                                <small class="text-muted" id="summaryAsistenciaMeta"><?php echo htmlspecialchars($cuadrilleroStats['farmsLabel'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-geo-alt me-1"></i>Asignadas</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1">Tareas planificadas</p>
                                <h3 id="summaryTareas"><?php echo $cuadrilleroStats['tasks']; ?></h3>
                                <small class="text-muted" id="summaryTareasMeta"><?php echo htmlspecialchars($cuadrilleroStats['tasksLabel'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                            <span class="badge bg-warning text-dark"><i class="bi bi-clipboard-check me-1"></i>Tareas</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1">Observaciones</p>
                                <h3 id="summaryAlertas"><?php echo $cuadrilleroStats['alerts']; ?></h3>
                                <small class="text-muted" id="summaryAlertasMeta"><?php echo htmlspecialchars($cuadrilleroStats['alertsLabel'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </div>
                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Alertas</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card assigned-card p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Notificaciones de rendimiento</h2>
                        <small class="text-muted">Alertas por bajas de rendimiento registradas.</small>
                    </div>
                </div>
                <?php if ($cuadrilleroNotifications): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($cuadrilleroNotifications as $notif): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars((string) $notif['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="text-muted small"><?php echo htmlspecialchars((string) $notif['mensaje'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                    <small class="text-muted"><?php echo htmlspecialchars((string) $notif['created_at'], ENT_QUOTES, 'UTF-8'); ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-muted">Sin alertas recientes.</div>
                <?php endif; ?>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card summary-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1">Peones asignados</p>
                                <h3 id="summaryPeonesTotales"><?php echo $cuadrilleroStats['workers']; ?></h3>
                                <small class="text-muted">Total vinculados</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary"><i class="bi bi-people me-1"></i>Peones</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1">Activos</p>
                                <h3 id="summaryPeonesActivos"><?php echo $cuadrilleroStats['workersActive']; ?></h3>
                                <small class="text-muted">Disponibles hoy</small>
                            </div>
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-person-check me-1"></i>Activos</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1">Inactivos</p>
                                <h3 id="summaryPeonesInactivos"><?php echo $cuadrilleroStats['workersInactive']; ?></h3>
                                <small class="text-muted">No asignables</small>
                            </div>
                            <span class="badge bg-secondary"><i class="bi bi-person-x me-1"></i>Inactivos</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card assigned-card p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Mis fincas asignadas</h2>
                        <small class="text-muted">Consulta las tareas, links de ubicación y observaciones pendientes.</small>
                    </div>
                    <?php if ($cuadrilleroProfile['inicio']): ?>
                        <div class="text-muted small">
                            <i class="bi bi-calendar-week me-1"></i>En operativo desde <?php echo htmlspecialchars($cuadrilleroProfile['inicio'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($assignedFarms): ?>
                    <div class="row g-4">
                        <?php foreach ($assignedFarms as $farm): ?>
                            <div class="col-md-6 col-xl-4">
                                <article class="assigned-farm-card h-100 p-4">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                        <div>
                                            <h3 class="h5 mb-1"><?php echo htmlspecialchars($farm['nombre'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <?php if (!empty($farm['tarea_asignada'])): ?>
                                                <small class="text-muted">Tarea: <?php echo htmlspecialchars($farm['tarea_asignada'], ENT_QUOTES, 'UTF-8'); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Sin tarea registrada</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex flex-column align-items-end gap-2">
                                            <a class="btn btn-outline-success btn-sm" href="finca-detalle.php?id=<?php echo (int)$farm['id']; ?>" title="Ver detalle"><i class="bi bi-people"></i></a>
                                            <?php if (!empty($farm['link_ubicacion'])): ?>
                                                <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($farm['link_ubicacion'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" title="Ubicación"><i class="bi bi-map"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($farm['descripcion'])): ?>
                                        <p class="text-muted small mb-3"><?php echo nl2br(htmlspecialchars($farm['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($farm['observacion'])): ?>
                                        <div class="alert alert-warning border-0 small mb-0">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo htmlspecialchars($farm['observacion'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small mb-0">Sin observaciones recientes.</div>
                                    <?php endif; ?>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="assigned-empty-state text-center text-muted">
                        <i class="bi bi-geo-alt display-5 mb-2"></i>
                        <p class="mb-1">Aún no tienes fincas asignadas.</p>
                        <small>Contacta al administrador para que te vincule a una cuadrilla.</small>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card assigned-card p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Mis peones asignados</h2>
                        <small class="text-muted">Estado y fecha de ingreso de cada trabajador.</small>
                    </div>
                </div>
                <?php if ($photoAlert): ?>
                    <div class="alert alert-<?php echo $photoAlertClass; ?> border-0">
                        <i class="bi bi-camera me-1"></i><?php echo htmlspecialchars($photoAlert, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <?php if ($rendAlert): ?>
                    <div class="alert alert-<?php echo $rendAlertClass; ?> border-0">
                        <i class="bi bi-clipboard-check me-1"></i><?php echo htmlspecialchars($rendAlert, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$taskStandards): ?>
                    <div class="alert alert-warning border-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>No hay estándares de tarea configurados por el administrador.
                    </div>
                <?php endif; ?>

                <form id="peonPhotoForm" method="post" action="upload_peon_foto.php" enctype="multipart/form-data">
                    <input type="hidden" name="peon_id" id="peonPhotoPeonId" value="">
                    <input type="file" name="foto_tarea" id="peonPhotoFile" accept="image/jpeg,image/png,image/webp" style="position:absolute;left:-9999px;width:1px;height:1px;" aria-hidden="true">
                </form>
                <div id="photoUploadStatus" class="small text-muted mb-3"></div>
                <?php if ($assignedWorkers): ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Estado</th>
                                    <th>Ingreso</th>
                                    <th>Teléfono</th>
                                    <th>Foto tarea</th>
                                    <th>Rendimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignedWorkers as $w): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(trim((string)$w['nombre'].' '.$w['apellido']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string)$w['dni'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php if (strtolower((string)$w['estado']) === 'activo'): ?>
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars((string)$w['fecha_ingreso'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo $w['telefono'] ? htmlspecialchars((string)$w['telefono'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">-</span>'; ?></td>
                                        <td>
                                            <button type="button" class="btn btn-outline-primary btn-sm js-upload-peon-photo" data-peon-id="<?php echo (int)$w['id']; ?>" data-peon-name="<?php echo htmlspecialchars(trim((string)$w['nombre'].' '.$w['apellido']), ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bi bi-camera me-1"></i>Subir
                                            </button>
                                        </td>
                                        <td>
                                            <?php if ($taskStandards): ?>
                                                <form method="post" action="registrar_rendimiento.php" class="d-flex flex-wrap gap-2 align-items-center">
                                                    <input type="hidden" name="peon_id" value="<?php echo (int)$w['id']; ?>">
                                                    <select name="tarea_id" class="form-select form-select-sm" required>
                                                        <option value="">Tarea</option>
                                                        <?php foreach ($taskStandards as $t): ?>
                                                            <option value="<?php echo (int) $t['id']; ?>">
                                                                <?php echo htmlspecialchars((string) $t['nombre'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int) $t['cantidad_normal']; ?> <?php echo htmlspecialchars((string) $t['unidad'], ENT_QUOTES, 'UTF-8'); ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input type="number" name="cantidad" min="0" class="form-control form-control-sm" placeholder="Cantidad" required style="max-width:120px;">
                                                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check2"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">Sin estándares</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="assigned-empty-state text-center text-muted">
                        <i class="bi bi-people display-5 mb-2"></i>
                        <p class="mb-1">No hay peones asignados todavía.</p>
                        <small>Solicita al administrador la asignación correspondiente.</small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Secciones eliminadas: Ruta del día, Tareas asignadas, Clima y Checklist de seguridad -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.__CuadrilleroData = <?php echo json_encode([
            'stats' => $cuadrilleroStats,
            'assignedFarms' => $assignedFarms,
            'assignedWorkers' => $assignedWorkers,
            'profile' => $cuadrilleroProfile,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script src="assets/js/panel-cuadrillero.js"></script>
</body>
</html>
