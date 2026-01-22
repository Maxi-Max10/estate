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
        <nav class="navbar navbar-dark admin-navbar navbar-expand-lg">
            <div class="container-fluid py-2">
                <a class="admin-brand d-flex align-items-center gap-2 gap-sm-3 flex-grow-1" href="panel-admin.php">
                    <img src="assets/img/logo.png" class="admin-logo" alt="Estate">
                    <div class="d-flex flex-column">
                        <span class="navbar-brand fs-6 mb-0">Estate · Panel Administrador</span>
                        <small class="text-white-50">Vista de peones</small>
                    </div>
                </a>
                <button class="navbar-toggler ms-auto d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavLinks" aria-controls="adminNavLinks" aria-expanded="false" aria-label="Alternar navegación">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="d-none d-lg-flex align-items-center gap-2 text-white ms-auto">
                    <a class="btn btn-outline-light btn-sm" href="panel-admin.php"><i class="bi bi-grid me-1"></i>Panel</a>
                    <a class="btn btn-light btn-sm text-dark" href="peones-admin.php"><i class="bi bi-people me-1"></i>Peones</a>
                    <a class="btn btn-outline-light btn-sm" href="cuadrilleros-admin.php"><i class="bi bi-person-lines-fill me-1"></i>Cuadrilleros</a>
                    <a class="btn btn-outline-light btn-sm" href="fincas-admin.php"><i class="bi bi-map me-1"></i>Fincas</a>
                    <span class="fw-semibold text-nowrap"><i class="bi bi-person-badge me-2"></i><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <a class="btn btn-danger btn-sm" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
                </div>
                <div class="collapse navbar-collapse fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-sm px-6 pt-6 pb-28 text-white lg:static lg:inset-auto lg:z-auto lg:bg-transparent lg:backdrop-blur-0 lg:p-0" id="adminNavLinks">
                    <div class="flex h-full flex-col lg:h-auto">
                        <div class="flex items-center justify-between lg:hidden">
                            <span class="text-white text-base font-semibold tracking-wide">Menú administrador</span>
                            <button class="h-10 w-10 rounded-full border border-white/30 text-white text-2xl leading-none flex items-center justify-center" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavLinks" aria-label="Cerrar menú">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>

                        <div class="mt-6 lg:mt-0 lg:flex lg:items-start lg:justify-end lg:gap-8">
                            <div class="space-y-6 lg:space-y-0 lg:flex lg:items-start lg:gap-6">
                                <div class="space-y-3">
                                    <button class="submenu-toggle w-full lg:w-auto flex items-center justify-between text-white/80 text-xs font-semibold uppercase tracking-widest" type="button" data-bs-toggle="collapse" data-bs-target="#adminMenuMain" aria-expanded="true" aria-controls="adminMenuMain">
                                        Gestión
                                        <i class="bi bi-chevron-right submenu-chevron"></i>
                                    </button>
                                    <div class="collapse show lg:!block" id="adminMenuMain">
                                        <a class="block py-2 text-white text-lg lg:text-sm lg:font-medium lg:py-1 lg:inline-flex lg:items-center lg:gap-2 lg:rounded-full lg:bg-white/10 lg:px-3 lg:py-2 lg:hover:bg-white/20" href="panel-admin.php">Panel</a>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <button class="submenu-toggle w-full lg:w-auto flex items-center justify-between text-white/80 text-xs font-semibold uppercase tracking-widest" type="button" data-bs-toggle="collapse" data-bs-target="#adminMenuPeople" aria-expanded="true" aria-controls="adminMenuPeople">
                                        Personas
                                        <i class="bi bi-chevron-right submenu-chevron"></i>
                                    </button>
                                    <div class="collapse show lg:!block" id="adminMenuPeople">
                                        <a class="block py-2 text-amber-300 text-lg lg:text-sm lg:font-medium lg:py-1 lg:inline-flex lg:items-center lg:gap-2 lg:rounded-full lg:bg-white/10 lg:px-3 lg:py-2 lg:text-white" href="peones-admin.php">Peones</a>
                                        <a class="block py-2 text-white text-lg lg:text-sm lg:font-medium lg:py-1 lg:inline-flex lg:items-center lg:gap-2 lg:rounded-full lg:bg-white/10 lg:px-3 lg:py-2 lg:hover:bg-white/20" href="cuadrilleros-admin.php">Cuadrilleros</a>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <button class="submenu-toggle w-full lg:w-auto flex items-center justify-between text-white/80 text-xs font-semibold uppercase tracking-widest" type="button" data-bs-toggle="collapse" data-bs-target="#adminMenuOps" aria-expanded="true" aria-controls="adminMenuOps">
                                        Operaciones
                                        <i class="bi bi-chevron-right submenu-chevron"></i>
                                    </button>
                                    <div class="collapse show lg:!block" id="adminMenuOps">
                                        <a class="block py-2 text-white text-lg lg:text-sm lg:font-medium lg:py-1 lg:inline-flex lg:items-center lg:gap-2 lg:rounded-full lg:bg-white/10 lg:px-3 lg:py-2 lg:hover:bg-white/20" href="fincas-admin.php">Fincas</a>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 lg:mt-0 lg:flex lg:items-center lg:gap-3">
                                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-sm font-semibold text-white">
                                    <i class="bi bi-person-badge"></i><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <a class="inline-flex items-center justify-center rounded-full bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-900 shadow-lg" href="panel-admin.php">Acción rápida</a>
                                <a class="inline-flex items-center justify-center rounded-full border border-white/30 px-4 py-2 text-sm font-semibold text-white" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
                            </div>
                        </div>

                        <div class="mt-auto lg:hidden pt-6">
                            <div class="flex items-center justify-between text-white/70 text-xs uppercase tracking-widest">
                                <span>Síguenos</span>
                                <div class="flex items-center gap-3 text-base">
                                    <a class="text-white/80 hover:text-white" href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                    <a class="text-white/80 hover:text-white" href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                    <a class="text-white/80 hover:text-white" href="#" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
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
</body>
</html>