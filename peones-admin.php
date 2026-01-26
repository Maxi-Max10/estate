<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: panel-cuadrillero.php');
    exit;
}

require_once __DIR__ . '/config.php';

$availablePeones = [];
$cuadrilleros = [];

try {
    $stmtPeones = $pdo->query('SELECT p.*, u.nombre AS cuadrilla_nombre FROM peones p LEFT JOIN usuarios u ON u.id = p.cuadrilla_id ORDER BY p.nombre ASC, p.apellido ASC');
    $availablePeones = $stmtPeones->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtCuadrilleros = $pdo->prepare('SELECT id, nombre FROM usuarios WHERE rol = :rol ORDER BY nombre ASC');
    $stmtCuadrilleros->execute([':rol' => 'cuadrillero']);
    $cuadrilleros = $stmtCuadrilleros->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('Error cargando peones en vista dedicada: ' . $e->getMessage());
}

$totalPeones = count($availablePeones);
$activos = 0;
$inactivos = 0;
$sinCuadrilla = 0;

foreach ($availablePeones as $peon) {
    $estado = strtolower((string) ($peon['estado'] ?? ''));
    if ($estado === 'inactivo') { $inactivos++; } else { $activos++; }
    if (empty($peon['cuadrilla_id'])) { $sinCuadrilla++; }
}

$userName = trim((string) ($_SESSION['user_name'] ?? 'Administrador'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peones | Estate</title>
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <header class="admin-hero">
        <nav class="navbar navbar-expand-lg navbar-light bg-light admin-navbar">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center gap-2" href="panel-admin.php">
                    <div class="d-flex flex-column">
                        <span class="fw-semibold mb-0">Panel Administrador</span>
                        <small class="text-muted" style="font-size: 0.75rem;">Vista de peones</small>
                    </div>
                </a>
                
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="d-none d-lg-flex ms-auto admin-navbar-right">
                    <ul class="navbar-nav mb-0 align-items-center gap-2">
                        <li class="nav-item">
                            <a class="nav-link" href="panel-admin.php">
                                <i class="bi bi-grid-fill me-1"></i>Panel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="peones-admin.php">
                                <i class="bi bi-people-fill me-1"></i>Peones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cuadrilleros-admin.php">
                                <i class="bi bi-person-lines-fill me-1"></i>Cuadrilleros
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="fincas-admin.php">
                                <i class="bi bi-map-fill me-1"></i>Fincas
                            </a>
                        </li>
                    </ul>
                    <a class="btn btn-danger btn-sm" href="logout.php">
                        <i class="bi bi-box-arrow-right me-1"></i>Salir
                    </a>
                </div>
                
                <div class="collapse navbar-collapse d-lg-none" id="adminNavbar">
                    <ul class="navbar-nav mt-2">
                        <li class="nav-item">
                            <a class="nav-link" href="panel-admin.php">
                                <i class="bi bi-grid-fill me-1"></i>Panel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="peones-admin.php">
                                <i class="bi bi-people-fill me-1"></i>Peones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cuadrilleros-admin.php">
                                <i class="bi bi-person-lines-fill me-1"></i>Cuadrilleros
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="fincas-admin.php">
                                <i class="bi bi-map-fill me-1"></i>Fincas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-danger btn-sm mt-2" href="logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Salir
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="dashboard-shell">
        <div class="container-fluid">
            <div class="row g-3 mb-4 stats-grid">
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Peones totales</p>
                                <h3 class="mb-0"><?php echo $totalPeones; ?></h3>
                            </div>
                            <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
                        </div>
                        <small class="text-success">Catálogo general</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Activos</p>
                                <h3 class="mb-0"><?php echo $activos; ?></h3>
                            </div>
                            <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-check-circle"></i></div>
                        </div>
                        <small class="text-success">Disponibles hoy</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Inactivos</p>
                                <h3 class="mb-0"><?php echo $inactivos; ?></h3>
                            </div>
                            <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-x-circle"></i></div>
                        </div>
                        <small class="text-danger">No asignables</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Sin cuadrilla</p>
                                <h3 class="mb-0"><?php echo $sinCuadrilla; ?></h3>
                            </div>
                            <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
                        </div>
                        <small class="text-warning">Requieren asignación</small>
                    </div>
                </div>
            </div>

            <div class="card form-section p-4 mb-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Filtro de peones</h2>
                        <small class="text-muted">Busca por nombre, documento, cuadrilla o estado.</small>
                    </div>
                    <p class="text-muted mb-0">Mostrando <span id="workersFilteredCount">0</span> de <?php echo $totalPeones; ?> registros.</p>
                </div>
                <form class="row g-3" id="workersFilters" onsubmit="return false;">
                    <div class="col-md-4">
                        <label class="form-label" for="searchWorker">Buscar</label>
                        <input type="search" class="form-control" id="searchWorker" placeholder="Nombre, apellido o DNI">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="filterStatus">Estado</label>
                        <select class="form-select" id="filterStatus">
                            <option value="">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="filterCuadrilla">Cuadrilla</label>
                        <select class="form-select" id="filterCuadrilla">
                            <option value="">Todas</option>
                            <?php foreach ($cuadrilleros as $c): ?>
                                <option value="<?php echo htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                            <option value="sin">Sin asignar</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-outline-secondary" id="clearWorkersFilters"><i class="bi bi-eraser me-1"></i>Limpiar filtros</button>
                    </div>
                </form>
            </div>

            <div class="card table-card border-0 shadow-sm mb-5">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Peones registrados</h2>
                            <small class="text-muted">Consulta, edita o elimina registros existentes.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-primary" href="panel-admin.php#workerForm"><i class="bi bi-person-plus me-1"></i>Registrar nuevo</a>
                            <button class="btn btn-outline-success" id="downloadWorkersCsv" type="button"><i class="bi bi-filetype-csv me-1"></i>Exportar CSV</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="fullWorkersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Cuadrilla</th>
                                    <th>Estado</th>
                                    <th>Ingreso</th>
                                    <th>Teléfono</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="7" class="text-center text-muted">Cargando información...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
        <div id="workersToast" class="toast align-items-center text-bg-primary border-0" role="status" aria-live="assertive">
            <div class="d-flex">
                <div class="toast-body" id="workersToastBody">Acción realizada.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="workerEditModal" tabindex="-1" aria-labelledby="workerEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="workerEditForm" method="post" action="actualizar_trabajador.php">
                    <div class="modal-header border-0 pb-0">
                        <h1 class="modal-title fs-5" id="workerEditModalLabel">Editar peón</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editWorkerId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" name="nombre" id="editWorkerName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido</label>
                                <input type="text" class="form-control" name="apellido" id="editWorkerLastName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">DNI</label>
                                <input type="text" class="form-control" name="dni" id="editWorkerDocument" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" name="telefono" id="editWorkerPhone" placeholder="+54 9 ...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha ingreso</label>
                                <input type="date" class="form-control" name="fecha_ingreso" id="editWorkerInicio" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado" id="editWorkerStatus" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Cuadrillero responsable</label>
                                <select class="form-select" name="cuadrilla_id" id="editWorkerCuadrilla">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($cuadrilleros as $c): ?>
                                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h1 class="modal-title fs-5" id="confirmDeleteModalLabel">Eliminar peón</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="confirmDeleteBody">¿Seguro que deseas eliminar este registro?</div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.__PeonesData = <?php echo json_encode($availablePeones, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script src="assets/js/peones-admin.js"></script>
    <script src="assets/js/admin-nav.js"></script>
</body>
</html>