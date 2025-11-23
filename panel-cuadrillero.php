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

$userName = $_SESSION['user_name'] ?: 'Cuadrillero';
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
                                <p class="text-muted mb-1">Asistencia de hoy</p>
                                <h3 id="summaryAsistencia">0</h3>
                                <small class="text-muted" id="summaryAsistenciaMeta">-</small>
                            </div>
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-check2-circle me-1"></i>EN OPERACIÓN</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1">Tareas activas</p>
                                <h3 id="summaryTareas">0</h3>
                                <small class="text-muted" id="summaryTareasMeta">-</small>
                            </div>
                            <span class="badge bg-warning text-dark"><i class="bi bi-clipboard-check me-1"></i>CHECKLIST</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1">Alertas</p>
                                <h3 id="summaryAlertas">0</h3>
                                <small class="text-muted">Revisa novedades urgentes</small>
                            </div>
                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>ALERTA</span>
                        </div>
                    </div>
                </div>
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
    <script src="assets/js/panel-cuadrillero.js"></script>
</body>
</html>
