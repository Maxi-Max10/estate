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

$assignedFarmsMap = [];
$cuadrilleroProfile = null;

try {
    $stmt = $pdo->prepare('SELECT t.*, f.id AS linked_finca_id, f.nombre AS linked_finca_nombre, f.link_ubicacion, f.descripcion, f.tarea_asignada AS linked_tarea, f.observacion AS linked_observacion FROM trabajadores t LEFT JOIN fincas f ON f.id = t.finca_id WHERE t.rol = :rol');
    $stmt->execute([':rol' => 'cuadrillero']);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($records as $record) {
        $matchScore = 0;
        if ($userId && array_key_exists('usuario_id', $record) && (int) $record['usuario_id'] === $userId) {
            $matchScore = 3;
        } elseif ($userEmail !== '' && array_key_exists('email', $record) && strtolower((string) $record['email']) === $userEmail) {
            $matchScore = 2;
        } elseif (strcasecmp((string) ($record['nombre'] ?? ''), $userName) === 0) {
            $matchScore = 1;
        }

        if ($matchScore === 0) {
            continue;
        }

        $fincaId = (int) ($record['linked_finca_id'] ?? $record['finca_id'] ?? 0);
        $farmKey = $fincaId ?: ('manual_' . spl_object_id((object) $record));

        if (!isset($assignedFarmsMap[$farmKey])) {
            $assignedFarmsMap[$farmKey] = [
                'id' => $fincaId,
                'nombre' => $record['linked_finca_nombre'] ?? $record['finca_nombre'] ?? 'Finca sin nombre',
                'link' => $record['link_ubicacion'] ?? null,
                'descripcion' => $record['descripcion'] ?? '',
                'tarea' => $record['linked_tarea'] ?? $record['tarea_asignada'] ?? '',
                'observacion' => $record['linked_observacion'] ?? $record['observaciones'] ?? '',
                'inicio' => $record['inicio_actividades'] ?? null,
            ];
        }

        if ($cuadrilleroProfile === null || $matchScore > ($cuadrilleroProfile['match'] ?? 0)) {
            $cuadrilleroProfile = [
                'nombre' => $record['nombre'] ?? $userName,
                'documento' => $record['documento'] ?? null,
                'inicio' => $record['inicio_actividades'] ?? null,
                'match' => $matchScore,
            ];
        }
    }
} catch (Throwable $e) {
    error_log('Error cargando fincas para cuadrillero: ' . $e->getMessage());
}

$assignedFarms = array_values($assignedFarmsMap);

$farmsCount = count($assignedFarms);
$farmsWithTask = 0;
$farmsWithObservations = 0;
foreach ($assignedFarms as $farm) {
    if (trim((string) ($farm['tarea'] ?? '')) !== '') {
        $farmsWithTask++;
    }
    if (trim((string) ($farm['observacion'] ?? '')) !== '') {
        $farmsWithObservations++;
    }
}

$cuadrilleroStats = [
    'farms' => $farmsCount,
    'farmsLabel' => $farmsCount === 1 ? 'Finca asignada' : 'Fincas asignadas',
    'tasks' => $farmsWithTask,
    'tasksLabel' => $farmsWithTask === 1 ? 'Con tarea registrada' : 'Con tareas registradas',
    'alerts' => $farmsWithObservations,
    'alertsLabel' => $farmsWithObservations === 1 ? 'Observación pendiente' : 'Observaciones pendientes',
];

if ($cuadrilleroProfile === null) {
    $cuadrilleroProfile = [
        'nombre' => $userName,
        'documento' => null,
        'inicio' => null,
        'match' => 0,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Cuadrillero | Estate</title>
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
                <a class="btn btn-outline-light btn-sm" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
            </div>
        </div>
    </nav>

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
                                            <?php if (!empty($farm['tarea'])): ?>
                                                <small class="text-muted">Tarea: <?php echo htmlspecialchars($farm['tarea'], ENT_QUOTES, 'UTF-8'); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Sin tarea registrada</small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($farm['link'])): ?>
                                            <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($farm['link'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                <i class="bi bi-map"></i>
                                            </a>
                                        <?php endif; ?>
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

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card timeline-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 mb-0">Ruta del día</h2>
                                <small class="text-muted">Checklist de hitos completados</small>
                            </div>
                            <span class="badge bg-emerald text-white">Hoy</span>
                        </div>
                        <div id="timelineList"></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card task-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 mb-0">Tareas asignadas</h2>
                                <small class="text-muted">Prioriza pendientes críticos</small>
                            </div>
                            <a href="#" class="btn btn-outline-success btn-sm">Nueva tarea</a>
                        </div>
                        <div class="list-group" id="taskList"></div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card weather-card p-4 h-100">
                        <h2 class="h6 text-muted">Condiciones climáticas</h2>
                        <div class="d-flex align-items-center gap-3 py-3">
                            <i class="bi bi-cloud-sun display-5 text-warning"></i>
                            <div>
                                <div class="fs-2" id="weatherTemp">26°C</div>
                                <small class="text-muted" id="weatherDesc">Parcialmente nublado</small>
                            </div>
                        </div>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between"><span>Viento</span><strong id="weatherWind">12 km/h</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Humedad</span><strong id="weatherHumidity">58%</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Índice UV</span><strong id="weatherUv">7</strong></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card task-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 mb-0">Checklist de seguridad</h2>
                                <small class="text-muted">Confirma avances y reportes</small>
                            </div>
                            <button class="btn btn-success btn-sm"><i class="bi bi-upload me-1"></i>Enviar reporte</button>
                        </div>
                        <div class="row g-3" id="safetyChecks"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.__CuadrilleroData = <?php echo json_encode([
            'stats' => $cuadrilleroStats,
            'assignedFarms' => $assignedFarms,
            'profile' => $cuadrilleroProfile,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script src="assets/js/panel-cuadrillero.js"></script>
</body>
</html>
