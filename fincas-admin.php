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
$cuadrilleros = [];

try {
    $stmtFincas = $pdo->query('SELECT f.*, u.nombre AS cuadrillero_nombre FROM fincas f LEFT JOIN usuarios u ON u.id = f.cuadrillero_id ORDER BY f.nombre ASC');
    $availableFincas = $stmtFincas->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $stmtCuadrilleros = $pdo->prepare('SELECT id, nombre FROM usuarios WHERE rol = :rol ORDER BY nombre ASC');
    $stmtCuadrilleros->execute([':rol' => 'cuadrillero']);
    $cuadrilleros = $stmtCuadrilleros->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('Error cargando fincas admin: ' . $e->getMessage());
}

$totalFincas = count($availableFincas);
$withTask = 0;
$withLocation = 0;
$withObservation = 0;
$withDescription = 0;
$tareaOptions = [];

foreach ($availableFincas as $finca) {
    $task = trim((string) ($finca['tarea_asignada'] ?? ''));
    if ($task !== '') {
        $withTask++;
        $tareaOptions[$task] = true;
    }

    if (!empty($finca['link_ubicacion'])) {
        $withLocation++;
    }

    if (!empty($finca['observacion'])) {
        $withObservation++;
    }

    if (!empty($finca['descripcion'])) {
        $withDescription++;
    }
}

$uniqueTasks = array_keys($tareaOptions);
sort($uniqueTasks, SORT_NATURAL | SORT_FLAG_CASE);

$userName = $_SESSION['user_name'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fincas | Estate</title>
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
                        <small class="text-white-50" style="font-size: 0.75rem;">Vista de fincas</small>
                    </div>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="adminNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-2">
                        <li class="nav-item">
                            <a class="nav-link" href="panel-admin.php">
                                <i class="bi bi-grid-fill me-1"></i>Panel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="peones-admin.php">
                                <i class="bi bi-people-fill me-1"></i>Peones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cuadrilleros-admin.php">
                                <i class="bi bi-person-lines-fill me-1"></i>Cuadrilleros
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="fincas-admin.php">
                                <i class="bi bi-map-fill me-1"></i>Fincas
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-block">
                            <span class="navbar-text px-3 py-1 rounded-pill user-badge">
                                <i class="bi bi-person-badge me-1"></i><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-danger btn-sm" href="logout.php">
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
                                <p class="text-muted mb-1">Fincas registradas</p>
                                <h3 class="mb-0"><?php echo $totalFincas; ?></h3>
                            </div>
                            <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-map"></i></div>
                        </div>
                        <small class="text-success">Catálogo actualizado</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Con tarea asignada</p>
                                <h3 class="mb-0"><?php echo $withTask; ?></h3>
                            </div>
                            <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-clipboard-check"></i></div>
                        </div>
                        <small class="text-warning">Planes operativos activos</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Con ubicación</p>
                                <h3 class="mb-0"><?php echo $withLocation; ?></h3>
                            </div>
                            <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-geo-alt"></i></div>
                        </div>
                        <small class="text-info">Links para visitas</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Con observaciones</p>
                                <h3 class="mb-0"><?php echo $withObservation; ?></h3>
                            </div>
                            <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-journal-text"></i></div>
                        </div>
                        <small class="text-primary">Seguimiento documental</small>
                    </div>
                </div>
            </div>

            <div class="card form-section p-4 mb-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Filtro de fincas</h2>
                        <small class="text-muted">Aplica búsqueda por nombre, tarea o estado.</small>
                    </div>
                    <p class="text-muted mb-0">Mostrando <span id="farmsFilteredCount">0</span> de <?php echo $totalFincas; ?> registros.</p>
                </div>
                <form class="row g-3" id="farmsFilters" onsubmit="return false;">
                    <div class="col-md-4">
                        <label class="form-label" for="searchFarm">Buscar</label>
                        <input type="search" class="form-control" id="searchFarm" placeholder="Nombre o palabra clave">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="filterTask">Tarea asignada</label>
                        <select class="form-select" id="filterTask">
                            <option value="">Todas</option>
                            <?php foreach ($uniqueTasks as $task): ?>
                                <option value="<?php echo htmlspecialchars($task, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($task, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filterLocation">Ubicación</label>
                        <select class="form-select" id="filterLocation">
                            <option value="all">Todas</option>
                            <option value="with">Con link</option>
                            <option value="without">Sin link</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filterObservation">Observaciones</label>
                        <select class="form-select" id="filterObservation">
                            <option value="all">Todas</option>
                            <option value="with">Con obs.</option>
                            <option value="without">Sin obs.</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-outline-secondary" id="clearFarmsFilters">
                            <i class="bi bi-eraser me-1"></i>Limpiar filtros
                        </button>
                    </div>
                </form>
            </div>

            <div class="card table-card border-0 shadow-sm mb-5">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1">Fincas registradas</h2>
                            <small class="text-muted">Consulta, edita o elimina registros existentes.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-success" href="panel-admin.php#farmForm">
                                <i class="bi bi-building-add me-1"></i>Registrar nueva
                            </a>
                            <button class="btn btn-outline-primary" id="downloadFarmsCsv" type="button">
                                <i class="bi bi-filetype-csv me-1"></i>Exportar CSV
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="fullFarmsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Cuadrillero</th>
                                    <th>Link ubicación</th>
                                    <th>Descripción</th>
                                    <th>Tarea asignada</th>
                                    <th>Observación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Cargando información...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="farmsToast" class="toast align-items-center text-bg-primary border-0" role="status" aria-live="assertive">
            <div class="d-flex">
                <div class="toast-body" id="farmsToastBody">Acción completada.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="farmEditModal" tabindex="-1" aria-labelledby="farmEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="farmEditForm" method="post" action="actualizar_finca.php">
                    <div class="modal-header border-0 pb-0">
                        <h1 class="modal-title fs-5" id="farmEditModalLabel">Editar finca</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editFarmId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" name="nombre" id="editFarmNombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Link de ubicación</label>
                                <input type="url" class="form-control" name="link_ubicacion" id="editFarmLink" placeholder="https://maps.google.com/..." required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Cuadrillero responsable</label>
                                <select class="form-select" name="cuadrillero_id" id="editFarmCuadrillero">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($cuadrilleros as $c): ?>
                                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" rows="2" name="descripcion" id="editFarmDescripcion"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tarea asignada</label>
                                <textarea class="form-control" rows="2" name="tarea_asignada" id="editFarmTarea"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observación</label>
                                <textarea class="form-control" rows="2" name="observacion" id="editFarmObservacion"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h1 class="modal-title fs-5" id="confirmDeleteModalLabel">Eliminar finca</h1>
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
        window.__FincasData = <?php echo json_encode($availableFincas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window.__ForemenData = <?php echo json_encode($cuadrilleros, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.querySelector('#fullFarmsTable tbody');
            const searchInput = document.getElementById('searchFarm');
            const taskFilter = document.getElementById('filterTask');
            const locationFilter = document.getElementById('filterLocation');
            const observationFilter = document.getElementById('filterObservation');
            const clearBtn = document.getElementById('clearFarmsFilters');
            const filteredCount = document.getElementById('farmsFilteredCount');
            const downloadCsvBtn = document.getElementById('downloadFarmsCsv');
            const toastEl = document.getElementById('farmsToast');
            const toastBody = document.getElementById('farmsToastBody');
            const toast = toastEl ? new bootstrap.Toast(toastEl) : null;
            const farmEditModalEl = document.getElementById('farmEditModal');
            const farmEditModal = farmEditModalEl ? new bootstrap.Modal(farmEditModalEl) : null;
            const farmEditForm = document.getElementById('farmEditForm');
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

            const normalizeFarm = farm => ({
                id: Number(farm.id) || 0,
                nombre: farm.nombre || 'Sin nombre',
                cuadrillero_id: farm.cuadrillero_id ? Number(farm.cuadrillero_id) : null,
                cuadrillero_nombre: farm.cuadrillero_nombre || '',
                link_ubicacion: farm.link_ubicacion || '',
                descripcion: farm.descripcion || '',
                tarea_asignada: farm.tarea_asignada || '',
                observacion: farm.observacion || '',
            });

            let farms = Array.isArray(window.__FincasData)
                ? window.__FincasData.map(normalizeFarm)
                : [];

            let filteredFarms = [...farms];
            let deleteContext = null;

            const renderRows = data => {
                tableBody.innerHTML = '';
                if (!data.length) {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="6" class="text-center text-muted">No hay fincas para los filtros seleccionados.</td>';
                    tableBody.appendChild(row);
                    return;
                }

                data.forEach(farm => {
                    const row = document.createElement('tr');
                    row.dataset.id = farm.id;
                    row.innerHTML = `
                        <td class="fw-semibold">${escapeHtml(farm.nombre)}</td>
                        <td>${farm.cuadrillero_nombre ? escapeHtml(farm.cuadrillero_nombre) : '<span class="text-muted">Sin asignar</span>'}</td>
                        <td>
                            ${farm.link_ubicacion ? `<a href="${escapeHtml(farm.link_ubicacion)}" target="_blank" rel="noopener">Ver ubicación</a>` : '-'}
                        </td>
                        <td>${farm.descripcion ? escapeHtml(farm.descripcion) : '-'}</td>
                        <td>${farm.tarea_asignada ? escapeHtml(farm.tarea_asignada) : '-'}</td>
                        <td>${farm.observacion ? escapeHtml(farm.observacion) : '-'}</td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-link text-success" data-action="edit-farm" data-id="${farm.id}" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-link text-danger" data-action="delete-farm" data-id="${farm.id}" data-name="${escapeHtml(farm.nombre)}" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>`;
                    tableBody.appendChild(row);
                });
            };

            const filterFarms = () => {
                const term = searchInput.value.trim().toLowerCase();
                const task = taskFilter.value;
                const location = locationFilter.value;
                const observation = observationFilter.value;

                filteredFarms = farms.filter(farm => {
                    const matchesTask = task ? farm.tarea_asignada === task : true;
                    const matchesLocation = location === 'with'
                        ? Boolean(farm.link_ubicacion)
                        : location === 'without'
                            ? !farm.link_ubicacion
                            : true;
                    const matchesObservation = observation === 'with'
                        ? Boolean(farm.observacion)
                        : observation === 'without'
                            ? !farm.observacion
                            : true;
                    const haystack = `${farm.nombre} ${farm.cuadrillero_nombre} ${farm.descripcion} ${farm.tarea_asignada} ${farm.observacion}`.toLowerCase();
                    const matchesTerm = term ? haystack.includes(term) : true;
                    return matchesTask && matchesLocation && matchesObservation && matchesTerm;
                });

                filteredFarms.sort((a, b) => a.nombre.localeCompare(b.nombre, 'es', { sensitivity: 'base' }));
                filteredCount.textContent = filteredFarms.length;
                renderRows(filteredFarms);
                return filteredFarms;
            };

            const resetFilters = () => {
                searchInput.value = '';
                taskFilter.value = '';
                locationFilter.value = 'all';
                observationFilter.value = 'all';
                filterFarms();
            };

            const downloadCsv = rows => {
                if (!rows.length) {
                    showToast('No hay datos para exportar.', 'danger');
                    return;
                }
                const header = ['Nombre', 'Link ubicación', 'Descripción', 'Tarea asignada', 'Observación'];
                const csvRows = [header.join(',')];
                rows.forEach(farm => {
                    csvRows.push([
                        farm.nombre,
                        farm.link_ubicacion || '-',
                        farm.descripcion || '-',
                        farm.tarea_asignada || '-',
                        farm.observacion ? farm.observacion.replace(/\n/g, ' ') : '-',
                    ].map(value => `"${(value || '').replace(/"/g, '""')}"`).join(','));
                });

                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `fincas_${Date.now()}.csv`;
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

            const openEditModal = farm => {
                if (!farmEditForm || !farmEditModal) return;
                farmEditForm.reset();
                farmEditForm.querySelector('#editFarmId').value = farm.id;
                farmEditForm.querySelector('#editFarmNombre').value = farm.nombre;
                farmEditForm.querySelector('#editFarmLink').value = farm.link_ubicacion;
                farmEditForm.querySelector('#editFarmDescripcion').value = farm.descripcion;
                const foremanSelect = farmEditForm.querySelector('#editFarmCuadrillero');
                if (foremanSelect) {
                    foremanSelect.value = farm.cuadrillero_id ?? '';
                }
                farmEditForm.querySelector('#editFarmTarea').value = farm.tarea_asignada;
                farmEditForm.querySelector('#editFarmObservacion').value = farm.observacion;
                farmEditModal.show();
            };

            const openDeleteModal = farm => {
                if (!confirmDeleteModal || !confirmDeleteBody) return;
                deleteContext = farm;
                confirmDeleteBody.textContent = `¿Eliminar la finca "${farm.nombre}"?`;
                confirmDeleteModal.show();
            };

            searchInput.addEventListener('input', filterFarms);
            taskFilter.addEventListener('change', filterFarms);
            locationFilter.addEventListener('change', filterFarms);
            observationFilter.addEventListener('change', filterFarms);
            clearBtn.addEventListener('click', resetFilters);
            downloadCsvBtn.addEventListener('click', () => downloadCsv(filteredFarms));

            if (farmEditForm) {
                farmEditForm.addEventListener('submit', async event => {
                    event.preventDefault();
                    const submitBtn = farmEditForm.querySelector('[type="submit"]');
                    toggleButtonState(submitBtn, true, 'Guardando...');

                    try {
                        const payload = await sendForm(farmEditForm.action, new FormData(farmEditForm));
                        if (payload.finca) {
                            const updated = normalizeFarm(payload.finca);
                            farms = farms.map(item => (item.id === updated.id ? updated : item));
                            filterFarms();
                        }
                        farmEditModal?.hide();
                        showToast(payload.message || 'Finca actualizada correctamente.', 'success');
                    } catch (error) {
                        showToast(error.message || 'No se pudo actualizar la finca.', 'danger');
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
                        const payload = await sendForm('eliminar_finca.php', formData);
                        farms = farms.filter(farm => farm.id !== deleteContext.id);
                        filterFarms();
                        confirmDeleteModal?.hide();
                        showToast(payload.message || 'Finca eliminada correctamente.', 'success');
                        deleteContext = null;
                    } catch (error) {
                        showToast(error.message || 'No se pudo eliminar la finca.', 'danger');
                    } finally {
                        toggleButtonState(confirmDeleteBtn, false);
                    }
                });
            }

            if (tableBody) {
                tableBody.addEventListener('click', event => {
                    const actionBtn = event.target.closest('[data-action]');
                    if (!actionBtn) return;
                    const farmId = Number(actionBtn.dataset.id);
                    const farm = farms.find(item => item.id === farmId);
                    if (!farm) return;

                    if (actionBtn.dataset.action === 'edit-farm') {
                        openEditModal(farm);
                    }

                    if (actionBtn.dataset.action === 'delete-farm') {
                        openDeleteModal(farm);
                    }
                });
            }

            filterFarms();
        });
    </script>
    <script src="assets/js/admin-nav.js"></script>
</body>
</html>
