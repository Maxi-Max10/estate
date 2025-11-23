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

$availableFincas = [];
$availableTrabajadores = [];
$fincasById = [];

try {
    $stmtFincas = $pdo->query('SELECT * FROM fincas ORDER BY nombre ASC');
    $availableFincas = $stmtFincas->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($availableFincas as $finca) {
        $id = isset($finca['id']) ? (int) $finca['id'] : null;
        if ($id) {
            $fincasById[$id] = $finca['nombre'] ?? 'Sin nombre';
        }
    }

    $stmtTrabajadores = $pdo->query('SELECT * FROM trabajadores ORDER BY nombre ASC');
    $availableTrabajadores = $stmtTrabajadores->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($availableTrabajadores && $fincasById) {
        foreach ($availableTrabajadores as &$trabajador) {
            $fincaId = isset($trabajador['finca_id']) ? (int) $trabajador['finca_id'] : null;
            if ($fincaId && (empty($trabajador['finca_nombre']) || !is_string($trabajador['finca_nombre']))) {
                $trabajador['finca_nombre'] = $fincasById[$fincaId] ?? null;
            }
        }
        unset($trabajador);
    }
} catch (Throwable $e) {
    error_log('Error cargando trabajadores admin: ' . $e->getMessage());
}

$roleLabels = [
    'admin'        => 'Administradores',
    'cuadrillero'  => 'Cuadrilleros',
    'supervisor'   => 'Supervisores',
    'colaborador'  => 'Colaboradores',
];

$roleTotals = [];
foreach ($availableTrabajadores as $trabajador) {
    $role = $trabajador['rol'] ?? 'colaborador';
    $roleTotals[$role] = ($roleTotals[$role] ?? 0) + 1;
}

$uniqueRoles = array_keys($roleTotals);
$orderedRoles = array_values(array_intersect(array_keys($roleLabels), $uniqueRoles));
if (count($orderedRoles) < count($uniqueRoles)) {
    foreach ($uniqueRoles as $role) {
        if (!in_array($role, $orderedRoles, true)) {
            $orderedRoles[] = $role;
        }
    }
}

$fincaNames = [];
foreach ($availableFincas as $finca) {
    $name = $finca['nombre'] ?? '';
    if ($name !== '') {
        $fincaNames[$name] = true;
    }
}
foreach ($availableTrabajadores as $trabajador) {
    $name = $trabajador['finca_nombre'] ?? '';
    if ($name !== '') {
        $fincaNames[$name] = true;
    }
}
ksort($fincaNames);

$totalTrabajadores = count($availableTrabajadores);
$withAssignedFarm = 0;
foreach ($availableTrabajadores as $trabajador) {
    if (!empty($trabajador['finca_nombre'])) {
        $withAssignedFarm++;
    }
}

$userName = $_SESSION['user_name'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trabajadores | Estate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <header class="admin-hero">
        <nav class="navbar navbar-dark admin-navbar">
            <div class="container-fluid py-2">
                <div class="d-flex align-items-center gap-2 gap-sm-3 flex-wrap">
                    <img src="assets/img/logo.png" class="admin-logo" alt="Estate" />
                    <div class="d-flex flex-column">
                        <span class="navbar-brand fs-6 mb-0">Estate · Panel Administrador</span>
                        <small class="text-white-50">Vista de trabajadores</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 gap-sm-3 text-white user-actions">
                    <a class="btn btn-outline-light btn-sm" href="panel-admin.php">
                        <i class="bi bi-grid me-1"></i>Panel principal
                    </a>
                    <span class="fw-semibold"><i class="bi bi-person-badge me-2"></i><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <a class="btn btn-outline-light btn-sm" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
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
                                <p class="text-muted mb-1">Trabajadores totales</p>
                                <h3 class="mb-0"><?php echo $totalTrabajadores; ?></h3>
                            </div>
                            <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
                        </div>
                        <small class="text-success">Mostrando datos actualizados</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Cuadrilleros activos</p>
                                <h3 class="mb-0"><?php echo $roleTotals['cuadrillero'] ?? 0; ?></h3>
                            </div>
                            <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-person-workspace"></i></div>
                        </div>
                        <small class="text-warning">Con finca asignada</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Supervisores</p>
                                <h3 class="mb-0"><?php echo $roleTotals['supervisor'] ?? 0; ?></h3>
                            </div>
                            <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-person-check"></i></div>
                        </div>
                        <small class="text-success">Incluye jefes de campo</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Con finca asignada</p>
                                <h3 class="mb-0"><?php echo $withAssignedFarm; ?></h3>
                            </div>
                            <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-map"></i></div>
                        </div>
                        <small class="text-info">Trabajadores vinculados a un predio</small>
                    </div>
                </div>
            </div>

            <div class="card form-section p-4 mb-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Filtro de trabajadores</h2>
                        <small class="text-muted">Busca por nombre, documento, rol o finca.</small>
                    </div>
                    <p class="text-muted mb-0">Mostrando <span id="workersFilteredCount">0</span> de <?php echo $totalTrabajadores; ?> registros.</p>
                </div>
                <form class="row g-3" id="workersFilters" onsubmit="return false;">
                    <div class="col-md-4">
                        <label class="form-label" for="searchWorker">Buscar</label>
                        <input type="search" class="form-control" id="searchWorker" placeholder="Nombre o documento">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="filterRole">Rol</label>
                        <select class="form-select" id="filterRole">
                            <option value="">Todos</option>
                            <?php foreach ($orderedRoles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="filterFinca">Finca</label>
                        <select class="form-select" id="filterFinca">
                            <option value="">Todas</option>
                            <?php foreach (array_keys($fincaNames) as $fincaName): ?>
                                <option value="<?php echo htmlspecialchars($fincaName, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($fincaName, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-outline-secondary" id="clearWorkersFilters">
                            <i class="bi bi-eraser me-1"></i>Limpiar filtros
                        </button>
                    </div>
                </form>
            </div>

            <div class="card table-card border-0 shadow-sm mb-5">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Trabajadores registrados</h2>
                            <small class="text-muted">Listado completo disponible para consulta.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-primary" href="panel-admin.php#workerForm">
                                <i class="bi bi-plus-lg me-1"></i>Registrar nuevo
                            </a>
                            <button class="btn btn-outline-success" id="downloadWorkersCsv" type="button">
                                <i class="bi bi-filetype-csv me-1"></i>Exportar CSV
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="fullWorkersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Documento</th>
                                    <th>Rol</th>
                                    <th>Finca</th>
                                    <th>Inicio</th>
                                    <th>Especialidad</th>
                                    <th>Observaciones</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Cargando información...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="workersToast" class="toast align-items-center text-bg-primary border-0" role="status" aria-live="assertive">
            <div class="d-flex">
                <div class="toast-body" id="workersToastBody">Descarga generada.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="workerEditModal" tabindex="-1" aria-labelledby="workerEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="workerEditForm" method="post" action="actualizar_trabajador.php">
                    <div class="modal-header border-0 pb-0">
                        <h1 class="modal-title fs-5" id="workerEditModalLabel">Editar trabajador</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editWorkerId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" name="nombre" id="editWorkerName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Documento</label>
                                <input type="text" class="form-control" name="documento" id="editWorkerDocument" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="rol" id="editWorkerRole" required>
                                    <option value="admin">Administrador</option>
                                    <option value="cuadrillero">Cuadrillero</option>
                                    <option value="colaborador">Colaborador (Cosechador)</option>
                                    <option value="supervisor">Supervisor</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="editWorkerFincaWrapper">
                                <label class="form-label">Finca asignada</label>
                                <select class="form-select" name="finca_id" id="editWorkerFinca">
                                    <option value="">Selecciona una finca</option>
                                </select>
                                <small class="text-muted">Requerida solo para cuadrilleros.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Especialidad</label>
                                <input type="text" class="form-control" name="especialidad" id="editWorkerEspecialidad" placeholder="cosechador, encargado, etc.">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Inicio de actividades</label>
                                <input type="date" class="form-control" name="inicio" id="editWorkerInicio" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" rows="2" name="observaciones" id="editWorkerObservaciones"></textarea>
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
                    <h1 class="modal-title fs-5" id="confirmDeleteModalLabel">Eliminar trabajador</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="confirmDeleteBody">
                    ¿Seguro que deseas eliminar este registro?
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.__TrabajadoresData = <?php echo json_encode($availableTrabajadores, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window.__FincasData = <?php echo json_encode($availableFincas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.querySelector('#fullWorkersTable tbody');
            const searchInput = document.getElementById('searchWorker');
            const roleFilter = document.getElementById('filterRole');
            const fincaFilter = document.getElementById('filterFinca');
            const clearBtn = document.getElementById('clearWorkersFilters');
            const filteredCount = document.getElementById('workersFilteredCount');
            const downloadCsvBtn = document.getElementById('downloadWorkersCsv');
            const toastEl = document.getElementById('workersToast');
            const toastBody = document.getElementById('workersToastBody');
            const toast = toastEl ? new bootstrap.Toast(toastEl) : null;
            const workerEditModalEl = document.getElementById('workerEditModal');
            const workerEditModal = workerEditModalEl ? new bootstrap.Modal(workerEditModalEl) : null;
            const workerEditForm = document.getElementById('workerEditForm');
            const workerEditRole = document.getElementById('editWorkerRole');
            const workerEditFincaWrapper = document.getElementById('editWorkerFincaWrapper');
            const workerEditFincaSelect = document.getElementById('editWorkerFinca');
            const workerEditEspecialidad = document.getElementById('editWorkerEspecialidad');
            const confirmDeleteModalEl = document.getElementById('confirmDeleteModal');
            const confirmDeleteModal = confirmDeleteModalEl ? new bootstrap.Modal(confirmDeleteModalEl) : null;
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const confirmDeleteBody = document.getElementById('confirmDeleteBody');

            const showToast = (message, variant = 'primary') => {
                if (!toastEl || !toastBody || !toast) return;
                toastEl.className = `toast align-items-center text-bg-${variant} border-0`;
                toastBody.textContent = message;
                toast.show();
            };

            const escapeHtml = value => {
                if (typeof value !== 'string') {
                    return value ?? '';
                }
                return value
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const normalizeWorker = worker => ({
                id: Number(worker.id) || 0,
                nombre: worker.nombre || 'Sin nombre',
                documento: worker.documento || '',
                rol: worker.rol || 'colaborador',
                finca_id: worker.finca_id !== null && worker.finca_id !== undefined ? Number(worker.finca_id) : null,
                finca_nombre: worker.finca_nombre || '',
                inicio_actividades: worker.inicio_actividades || '',
                especialidad: worker.especialidad || '',
                observaciones: worker.observaciones || '',
            });

            const normalizeFinca = finca => ({
                id: Number(finca.id) || 0,
                nombre: finca.nombre || 'Sin nombre',
            });

            let workers = Array.isArray(window.__TrabajadoresData)
                ? window.__TrabajadoresData.map(normalizeWorker)
                : [];

            const fincas = Array.isArray(window.__FincasData)
                ? window.__FincasData.map(normalizeFinca)
                : [];

            let filteredWorkers = [...workers];
            let deleteContext = null;

            const formatDate = value => {
                if (!value) return '-';
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return value;
                }
                return date.toLocaleDateString('es-UY', { day: '2-digit', month: 'short', year: 'numeric' });
            };

            const setFincaFieldState = (role, wrapper, select) => {
                if (!wrapper || !select) return;
                const needsFinca = role === 'cuadrillero';
                wrapper.classList.toggle('d-none', !needsFinca);
                select.disabled = !needsFinca;
                select.required = needsFinca;
                if (!needsFinca) {
                    select.value = '';
                }
            };

            const setEspecialidadValue = (role, input, currentValue = '') => {
                if (!input) return;
                if (role === 'colaborador') {
                    input.value = currentValue || 'cosechador';
                } else if (!currentValue) {
                    input.value = '';
                }
            };

            const fillFincaSelect = (select, selectedId = null) => {
                if (!select) return;
                select.innerHTML = '<option value="">Selecciona una finca</option>';
                fincas.forEach(finca => {
                    const option = document.createElement('option');
                    option.value = String(finca.id);
                    option.textContent = finca.nombre;
                    select.appendChild(option);
                });
                if (selectedId) {
                    select.value = String(selectedId);
                }
            };

            const renderRows = data => {
                tableBody.innerHTML = '';
                if (!data.length) {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="8" class="text-center text-muted">No hay trabajadores para los filtros seleccionados.</td>';
                    tableBody.appendChild(row);
                    return;
                }

                data.forEach(worker => {
                    const row = document.createElement('tr');
                    row.dataset.id = worker.id;
                    row.innerHTML = `
                        <td class="fw-semibold">${escapeHtml(worker.nombre)}</td>
                        <td>${escapeHtml(worker.documento)}</td>
                        <td class="text-capitalize">${escapeHtml(worker.rol)}</td>
                        <td>${worker.finca_nombre ? escapeHtml(worker.finca_nombre) : '-'}</td>
                        <td>${formatDate(worker.inicio_actividades)}</td>
                        <td>${worker.especialidad ? escapeHtml(worker.especialidad) : '-'}</td>
                        <td>${worker.observaciones ? escapeHtml(worker.observaciones) : '-'}</td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-link text-primary" data-action="edit-worker" data-id="${worker.id}" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-link text-danger" data-action="delete-worker" data-id="${worker.id}" data-name="${escapeHtml(worker.nombre)}" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>`;
                    tableBody.appendChild(row);
                });
            };

            const filterWorkers = () => {
                const term = searchInput.value.trim().toLowerCase();
                const role = roleFilter.value;
                const finca = fincaFilter.value;
                filteredWorkers = workers.filter(worker => {
                    const matchesRole = role ? worker.rol === role : true;
                    const matchesFinca = finca ? worker.finca_nombre === finca : true;
                    const haystack = `${worker.nombre} ${worker.documento} ${worker.especialidad} ${worker.observaciones} ${worker.finca_nombre}`.toLowerCase();
                    const matchesTerm = term ? haystack.includes(term) : true;
                    return matchesRole && matchesFinca && matchesTerm;
                });

                filteredWorkers.sort((a, b) => a.nombre.localeCompare(b.nombre, 'es', { sensitivity: 'base' }));
                filteredCount.textContent = filteredWorkers.length;
                renderRows(filteredWorkers);
                return filteredWorkers;
            };

            const resetFilters = () => {
                searchInput.value = '';
                roleFilter.value = '';
                fincaFilter.value = '';
                filterWorkers();
            };

            const downloadCsv = rows => {
                if (!rows.length) {
                    showToast('No hay datos para exportar.', 'danger');
                    return;
                }
                const header = ['Nombre', 'Documento', 'Rol', 'Finca', 'Inicio', 'Especialidad', 'Observaciones'];
                const csvRows = [header.join(',')];
                rows.forEach(worker => {
                    csvRows.push([
                        worker.nombre,
                        worker.documento,
                        worker.rol,
                        worker.finca_nombre || '-',
                        worker.inicio_actividades || '-',
                        worker.especialidad || '-',
                        worker.observaciones ? worker.observaciones.replace(/\n/g, ' ') : '-',
                    ].map(value => `"${(value || '').replace(/"/g, '""')}"`).join(','));
                });

                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `trabajadores_${Date.now()}.csv`;
                link.click();
                URL.revokeObjectURL(url);

                showToast('Descarga generada correctamente.', 'primary');
            };

            const sendForm = async (url, formData) => {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });

                const expectsJson = response.headers.get('Content-Type')?.includes('application/json');
                const payload = expectsJson ? await response.json() : {};

                if (!response.ok || payload.success === false) {
                    throw new Error(payload.message || 'Ocurrió un error en la operación.');
                }

                return payload;
            };

            const toggleButtonState = (button, isLoading, loadingText = 'Procesando...') => {
                if (!button) return;
                if (isLoading) {
                    button.dataset.originalText = button.textContent;
                    button.textContent = loadingText;
                    button.disabled = true;
                } else {
                    button.textContent = button.dataset.originalText || button.textContent;
                    button.disabled = false;
                }
            };

            const openEditModal = worker => {
                if (!workerEditForm || !workerEditModal) return;
                workerEditForm.reset();
                workerEditForm.querySelector('#editWorkerId').value = worker.id;
                workerEditForm.querySelector('#editWorkerName').value = worker.nombre;
                workerEditForm.querySelector('#editWorkerDocument').value = worker.documento;
                workerEditRole.value = worker.rol;
                fillFincaSelect(workerEditFincaSelect, worker.finca_id);
                workerEditFincaSelect.value = worker.finca_id ? String(worker.finca_id) : '';
                workerEditForm.querySelector('#editWorkerInicio').value = worker.inicio_actividades || '';
                workerEditForm.querySelector('#editWorkerObservaciones').value = worker.observaciones || '';
                workerEditEspecialidad.value = worker.especialidad || '';
                setFincaFieldState(worker.rol, workerEditFincaWrapper, workerEditFincaSelect);
                setEspecialidadValue(worker.rol, workerEditEspecialidad, worker.especialidad || '');
                workerEditModal.show();
            };

            const openDeleteModal = worker => {
                if (!confirmDeleteModal || !confirmDeleteBody) return;
                deleteContext = worker;
                confirmDeleteBody.textContent = `¿Eliminar al trabajador "${worker.nombre}"?`;
                confirmDeleteModal.show();
            };

            searchInput.addEventListener('input', filterWorkers);
            roleFilter.addEventListener('change', filterWorkers);
            fincaFilter.addEventListener('change', filterWorkers);
            clearBtn.addEventListener('click', resetFilters);
            downloadCsvBtn.addEventListener('click', () => downloadCsv(filteredWorkers));

            if (workerEditRole) {
                workerEditRole.addEventListener('change', () => {
                    const role = workerEditRole.value;
                    setFincaFieldState(role, workerEditFincaWrapper, workerEditFincaSelect);
                    setEspecialidadValue(role, workerEditEspecialidad, workerEditEspecialidad.value);
                });
            }

            if (workerEditForm) {
                workerEditForm.addEventListener('submit', async event => {
                    event.preventDefault();
                    const submitBtn = workerEditForm.querySelector('[type="submit"]');
                    toggleButtonState(submitBtn, true, 'Guardando...');

                    try {
                        const payload = await sendForm(workerEditForm.action, new FormData(workerEditForm));
                        if (payload.trabajador) {
                            const updated = normalizeWorker(payload.trabajador);
                            workers = workers.map(item => (item.id === updated.id ? updated : item));
                            filterWorkers();
                        }
                        workerEditModal?.hide();
                        showToast(payload.message || 'Trabajador actualizado correctamente.', 'success');
                    } catch (error) {
                        showToast(error.message || 'No se pudo actualizar el trabajador.', 'danger');
                    } finally {
                        toggleButtonState(submitBtn, false);
                    }
                });
            }

            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', async () => {
                    if (!deleteContext) return;
                    const formData = new FormData();
                    formData.append('id', String(deleteContext.id));
                    toggleButtonState(confirmDeleteBtn, true, 'Eliminando...');
                    try {
                        const payload = await sendForm('eliminar_trabajador.php', formData);
                        workers = workers.filter(worker => worker.id !== deleteContext.id);
                        filterWorkers();
                        confirmDeleteModal?.hide();
                        showToast(payload.message || 'Trabajador eliminado correctamente.', 'success');
                        deleteContext = null;
                    } catch (error) {
                        showToast(error.message || 'No se pudo eliminar el trabajador.', 'danger');
                    } finally {
                        toggleButtonState(confirmDeleteBtn, false);
                    }
                });
            }

            if (tableBody) {
                tableBody.addEventListener('click', event => {
                    const actionBtn = event.target.closest('[data-action]');
                    if (!actionBtn) return;
                    const workerId = Number(actionBtn.dataset.id);
                    const worker = workers.find(item => item.id === workerId);
                    if (!worker) return;

                    if (actionBtn.dataset.action === 'edit-worker') {
                        openEditModal(worker);
                    }

                    if (actionBtn.dataset.action === 'delete-worker') {
                        openDeleteModal(worker);
                    }
                });
            }

            fillFincaSelect(workerEditFincaSelect);
            filterWorkers();
        });
    </script>
</body>
</html>
