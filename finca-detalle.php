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
$userName = trim((string) ($_SESSION['user_name'] ?? 'Cuadrillero'));
$fincaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($fincaId <= 0) {
    header('Location: panel-cuadrillero.php');
    exit;
}

// Obtener datos de la finca y validar que pertenezca al cuadrillero
$stmtFinca = $pdo->prepare('SELECT id, nombre, link_ubicacion, descripcion, tarea_asignada, observacion FROM fincas WHERE id = :fid AND cuadrillero_id = :cid LIMIT 1');
$stmtFinca->execute([':fid' => $fincaId, ':cid' => $userId]);
$finca = $stmtFinca->fetch(PDO::FETCH_ASSOC);
if (!$finca) {
    header('Location: panel-cuadrillero.php');
    exit;
}

// Peones del cuadrillero (no existe relación directa con finca en esquema actual)
$stmtPeones = $pdo->prepare('SELECT id, nombre, apellido, dni, estado, fecha_ingreso, telefono FROM peones WHERE cuadrilla_id = :cid ORDER BY nombre ASC, apellido ASC');
$stmtPeones->execute([':cid' => $userId]);
$peones = $stmtPeones->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Asistencias simuladas en sesión (placeholder hasta crear tabla real)
$sessionKey = 'asistencia_finca_' . $fincaId;
if (!isset($_SESSION[$sessionKey])) {
    $_SESSION[$sessionKey] = [];
}
$asistencias = $_SESSION[$sessionKey];

// Saludo reutilizable
$hourNow = (int) date('G');
if ($hourNow >= 5 && $hourNow < 12) { $dynamicGreeting = 'Buenos días'; }
elseif ($hourNow >= 12 && $hourNow < 19) { $dynamicGreeting = 'Buenas tardes'; }
else { $dynamicGreeting = 'Buenas noches'; }
$dynamicGreeting .= ', ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finca: <?php echo htmlspecialchars($finca['nombre'], ENT_QUOTES, 'UTF-8'); ?> | Estate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/cuadrillero.css" rel="stylesheet">
    <style>
    .attendance-toggle { cursor:pointer; }
    .attendance-badge-present { background:#10b981; }
    .attendance-badge-absent { background:#dc2626; }
    .finca-header-card { border:none; border-radius:1.25rem; box-shadow:0 12px 32px rgba(15,23,42,0.12); }
    </style>
</head>
<body>
<nav class="navbar navbar-dark cuadrillero-navbar">
    <div class="container-fluid py-3">
        <div>
            <span class="navbar-brand h3 mb-0">Estate · Finca</span>
            <p class="mb-0 text-white-50">Detalle y asistencia de peones</p>
        </div>
        <div class="d-flex align-items-center gap-3 text-white">
            <span class="fw-semibold"><i class="bi bi-person-workspace me-2"></i><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
            <a class="btn btn-outline-light btn-sm" href="panel-cuadrillero.php"><i class="bi bi-arrow-left me-1"></i>Volver</a>
            <a class="btn btn-outline-light btn-sm" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
        </div>
    </div>
</nav>
<header class="banner-hero" role="banner" aria-label="Saludo del día">
    <div class="banner-greeting"><span><?php echo $dynamicGreeting; ?></span></div>
</header>
<div class="dashboard-shell">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card finca-header-card p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-4">
                        <div>
                            <h1 class="h4 mb-2"><?php echo htmlspecialchars($finca['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
                            <p class="text-muted mb-2"><?php echo $finca['descripcion'] ? nl2br(htmlspecialchars($finca['descripcion'], ENT_QUOTES, 'UTF-8')) : 'Sin descripción.'; ?></p>
                            <p class="mb-2"><strong>Tarea asignada:</strong> <?php echo $finca['tarea_asignada'] ? htmlspecialchars($finca['tarea_asignada'], ENT_QUOTES, 'UTF-8') : 'Sin tarea.'; ?></p>
                            <?php if ($finca['observacion']): ?><div class="alert alert-warning py-2 mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo htmlspecialchars($finca['observacion'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                            <?php if ($finca['link_ubicacion']): ?><a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($finca['link_ubicacion'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-map me-1"></i>Ubicación</a><?php endif; ?>
                        </div>
                        <div class="align-self-start">
                            <form class="border rounded p-3" method="post" action="crear-peon.php">
                                <h2 class="h6 mb-3">Crear nuevo peón</h2>
                                <input type="hidden" name="redirect_finca" value="<?php echo (int)$fincaId; ?>">
                                <div class="mb-2"><input required name="nombre" class="form-control form-control-sm" placeholder="Nombre"></div>
                                <div class="mb-2"><input name="apellido" class="form-control form-control-sm" placeholder="Apellido"></div>
                                <div class="mb-2"><input required name="dni" class="form-control form-control-sm" placeholder="DNI"></div>
                                <div class="mb-2"><input name="telefono" class="form-control form-control-sm" placeholder="Teléfono"></div>
                                <div class="mb-2"><input type="date" name="fecha_ingreso" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>"></div>
                                <button class="btn btn-success btn-sm w-100"><i class="bi bi-person-plus me-1"></i>Guardar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card assigned-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h5 mb-1">Peones vinculados a tu cuadrilla</h2>
                            <small class="text-muted">(Asignación por finca no disponible en esquema actual)</small>
                        </div>
                    </div>
                    <?php if ($peones): ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>DNI</th>
                                        <th>Estado</th>
                                        <th>Ingreso</th>
                                        <th>Teléfono</th>
                                        <th>Asistencia hoy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($peones as $p): $pid=(int)$p['id']; $present = isset($asistencias[$pid]) && $asistencias[$pid] === true; ?>
                                    <tr data-peon-id="<?php echo $pid; ?>">
                                        <td><?php echo htmlspecialchars(trim((string)$p['nombre'].' '.$p['apellido']), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string)$p['dni'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php if (strtolower((string)$p['estado']) === 'activo'): ?><span class="badge bg-success-subtle text-success">Activo</span><?php else: ?><span class="badge bg-secondary">Inactivo</span><?php endif; ?></td>
                                        <td><?php echo htmlspecialchars((string)$p['fecha_ingreso'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo $p['telefono'] ? htmlspecialchars((string)$p['telefono'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">-</span>'; ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm attendance-toggle <?php echo $present ? 'btn-success' : 'btn-outline-secondary'; ?>" data-present="<?php echo $present ? '1' : '0'; ?>">
                                                <?php echo $present ? 'Presente' : 'Ausente'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="assigned-empty-state text-center text-muted">
                            <i class="bi bi-people display-5 mb-2"></i>
                            <p class="mb-1">No hay peones en tu cuadrilla.</p>
                            <small>Utiliza el formulario para crear uno.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle asistencia via fetch a script que guarda en sesión
const toggles = document.querySelectorAll('.attendance-toggle');
toggles.forEach(btn => {
    btn.addEventListener('click', () => {
        const tr = btn.closest('tr');
        const id = tr.getAttribute('data-peon-id');
        const present = btn.getAttribute('data-present') === '1';
        fetch('marcar_asistencia.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ finca_id: '<?php echo $fincaId; ?>', peon_id: id, marcar: present ? '0' : '1' })
        }).then(r => r.json()).then(data => {
            if (data.ok) {
                btn.setAttribute('data-present', data.presente ? '1' : '0');
                btn.textContent = data.presente ? 'Presente' : 'Ausente';
                btn.classList.toggle('btn-success', data.presente);
                btn.classList.toggle('btn-outline-secondary', !data.presente);
            }
        }).catch(e => console.error(e));
    });
});
</script>
</body>
</html>
