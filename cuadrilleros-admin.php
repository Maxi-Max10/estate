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

$cuadrilleros = [];
try {
    $stmt = $pdo->prepare('SELECT id, nombre, email, creado_en, actualizado_en FROM usuarios WHERE rol = :rol ORDER BY nombre ASC');
    $stmt->execute([':rol' => 'cuadrillero']);
    $cuadrilleros = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('Error cargando cuadrilleros: ' . $e->getMessage());
}

$total = count($cuadrilleros);
$hoy = date('Y-m-d');
$nuevosHoy = 0;
foreach ($cuadrilleros as $c) {
    if (isset($c['creado_en']) && str_starts_with((string)$c['creado_en'], $hoy)) {
        $nuevosHoy++;
    }
}

$userName = trim((string)($_SESSION['user_name'] ?? 'Administrador'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuadrilleros | Estate</title>
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
                    <small class="text-muted" style="font-size: 0.75rem;">Gestión de cuadrilleros</small>
                </div>
            </a>
            
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="d-none d-lg-flex align-items-center gap-2 ms-auto w-100 justify-content-end">
                <ul class="navbar-nav mb-0 align-items-center gap-2">
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
                        <a class="nav-link active" href="cuadrilleros-admin.php">
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
                        <a class="nav-link" href="peones-admin.php">
                            <i class="bi bi-people-fill me-1"></i>Peones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cuadrilleros-admin.php">
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
                            <p class="text-muted mb-1">Cuadrilleros totales</p>
                            <h3 class="mb-0"><?php echo $total; ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-person-lines-fill"></i></div>
                    </div>
                    <small class="text-primary">Usuarios rol cuadrillero</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Nuevos hoy</p>
                            <h3 class="mb-0"><?php echo $nuevosHoy; ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-plus-circle"></i></div>
                    </div>
                    <small class="text-success">Altas recientes</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stat-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Última actualización</p>
                            <h3 class="mb-0" style="font-size:1.15rem;">
                                <?php echo $total ? htmlspecialchars((string)$cuadrilleros[array_key_last($cuadrilleros)]['actualizado_en'], ENT_QUOTES, 'UTF-8') : '-'; ?>
                            </h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-clock-history"></i></div>
                    </div>
                    <small class="text-warning">Marca temporal del último cambio</small>
                </div>
            </div>
        </div>

        <div class="card form-section p-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Registrar nuevo cuadrillero</h2>
                    <small class="text-muted">Crea cuentas para asignar y administrar cuadrillas.</small>
                </div>
            </div>
            <form id="newForemanForm" class="row g-3" method="post" action="guardar_cuadrillero.php">
                <div class="col-md-4">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" minlength="6" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="password_confirm" class="form-control" minlength="6" required>
                </div>
                <div class="col-12 text-end">
                    <button type="reset" class="btn btn-outline-secondary">Limpiar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>

        <div class="card table-card border-0 shadow-sm mb-5">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Cuadrilleros registrados</h2>
                        <small class="text-muted">Edita datos o elimina cuentas.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-success" id="exportForemenCsv" type="button"><i class="bi bi-filetype-csv me-1"></i>Exportar CSV</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle" id="foremenTable">
                        <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Creado</th>
                            <th>Actualizado</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$cuadrilleros): ?>
                            <tr><td colspan="5" class="text-center text-muted">Sin registros.</td></tr>
                        <?php else: ?>
                            <?php foreach ($cuadrilleros as $c): ?>
                                <tr data-id="<?php echo (int)$c['id']; ?>" data-email="<?php echo htmlspecialchars((string)$c['email'], ENT_QUOTES, 'UTF-8'); ?>" data-nombre="<?php echo htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="fw-semibold"><?php echo htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$c['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($c['creado_en'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($c['actualizado_en'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-link text-success" data-action="edit" title="Editar"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-link text-danger" data-action="delete" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <div id="foremenToast" class="toast align-items-center text-bg-primary border-0" role="status" aria-live="assertive">
        <div class="d-flex">
            <div class="toast-body" id="foremenToastBody">Acción realizada.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    </div>
</div>

<!-- Modal editar -->
<div class="modal fade" id="foremanEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="foremanEditForm" method="post" action="actualizar_cuadrillero.php">
                <div class="modal-header border-0 pb-0">
                    <h1 class="modal-title fs-5">Editar cuadrillero</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editForemanId">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="editForemanNombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="editForemanEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña (opcional)</label>
                        <input type="password" class="form-control" name="password" id="editForemanPassword" minlength="6" placeholder="Dejar vacío para no cambiar">
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

<!-- Modal eliminar -->
<div class="modal fade" id="foremanDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h1 class="modal-title fs-5">Eliminar cuadrillero</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="foremanDeleteBody">¿Seguro que deseas eliminar este registro?</div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmForemanDeleteBtn">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const toastEl = document.getElementById('foremenToast');
    const toastBody = document.getElementById('foremenToastBody');
    const toast = toastEl ? new bootstrap.Toast(toastEl) : null;
    const showToast = (msg, variant='primary') => {
        if (!toastEl || !toastBody || !toast) return;
        toastEl.className = `toast align-items-center text-bg-${variant} border-0`;
        toastBody.textContent = msg;
        toast.show();
    };

    const tableBody = document.querySelector('#foremenTable tbody');
    const editModalEl = document.getElementById('foremanEditModal');
    const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    const editForm = document.getElementById('foremanEditForm');

    const deleteModalEl = document.getElementById('foremanDeleteModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const deleteBody = document.getElementById('foremanDeleteBody');
    const deleteBtn = document.getElementById('confirmForemanDeleteBtn');

    let deleteContext = null;

    const sendForm = async (url, formData) => {
        const resp = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const isJson = resp.headers.get('Content-Type')?.includes('application/json');
        const data = isJson ? await resp.json() : {};
        if (!resp.ok || data.success === false) {
            throw new Error(data.message || 'Error en la operación');
        }
        return data;
    };

    // Crear cuadrillero
    const newForemanForm = document.getElementById('newForemanForm');
    if (newForemanForm) {
        newForemanForm.addEventListener('submit', async e => {
            e.preventDefault();
            const submitBtn = newForemanForm.querySelector('[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            try {
                const data = await sendForm(newForemanForm.action, new FormData(newForemanForm));
                if (data.foreman) {
                    const f = data.foreman;
                    const row = document.createElement('tr');
                    row.dataset.id = f.id;
                    row.dataset.email = f.email;
                    row.dataset.nombre = f.nombre;
                    row.innerHTML = `
                        <td class="fw-semibold">${f.nombre}</td>
                        <td>${f.email}</td>
                        <td>${f.creado_en || '-'}</td>
                        <td>${f.actualizado_en || '-'}</td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-link text-success" data-action="edit"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn btn-sm btn-link text-danger" data-action="delete"><i class="bi bi-trash"></i></button>
                        </td>`;
                    if (tableBody.querySelector('td.text-muted')) tableBody.innerHTML='';
                    tableBody.appendChild(row);
                }
                newForemanForm.reset();
                showToast(data.message || 'Cuadrillero creado correctamente', 'success');
            } catch (err) {
                showToast(err.message, 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar';
            }
        });
    }

    // Editar y eliminar
    if (tableBody) {
        tableBody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const row = btn.closest('tr');
            const id = row?.dataset.id;
            const nombre = row?.dataset.nombre;
            const email = row?.dataset.email;
            if (!id) return;
            if (btn.dataset.action === 'edit' && editForm && editModal) {
                editForm.reset();
                editForm.querySelector('#editForemanId').value = id;
                editForm.querySelector('#editForemanNombre').value = nombre || '';
                editForm.querySelector('#editForemanEmail').value = email || '';
                editModal.show();
            }
            if (btn.dataset.action === 'delete' && deleteModal && deleteBody) {
                deleteContext = { id, nombre };
                deleteBody.textContent = `¿Eliminar cuadrillero "${nombre}"?`;
                deleteModal.show();
            }
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', async e => {
            e.preventDefault();
            const submitBtn = editForm.querySelector('[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            try {
                const data = await sendForm(editForm.action, new FormData(editForm));
                if (data.foreman) {
                    const f = data.foreman;
                    const row = tableBody.querySelector(`tr[data-id='${f.id}']`);
                    if (row) {
                        row.dataset.email = f.email;
                        row.dataset.nombre = f.nombre;
                        row.children[0].textContent = f.nombre;
                        row.children[1].textContent = f.email;
                        row.children[3].textContent = f.actualizado_en || '-';
                    }
                }
                showToast(data.message || 'Actualizado correctamente', 'success');
                editModal?.hide();
            } catch (err) {
                showToast(err.message, 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Guardar cambios';
            }
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', async () => {
            if (!deleteContext) return;
            deleteBtn.disabled = true;
            deleteBtn.textContent = 'Eliminando...';
            const fd = new FormData();
            fd.append('id', deleteContext.id);
            try {
                const data = await sendForm('eliminar_cuadrillero.php', fd);
                const row = tableBody.querySelector(`tr[data-id='${deleteContext.id}']`);
                if (row) row.remove();
                if (!tableBody.children.length) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin registros.</td></tr>';
                }
                showToast(data.message || 'Eliminado correctamente', 'success');
                deleteModal?.hide();
                deleteContext = null;
            } catch (err) {
                showToast(err.message, 'danger');
            } finally {
                deleteBtn.disabled = false;
                deleteBtn.textContent = 'Eliminar';
            }
        });
    }

    const exportBtn = document.getElementById('exportForemenCsv');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const rows = [...tableBody.querySelectorAll('tr[data-id]')];
            if (!rows.length) {
                showToast('No hay datos para exportar', 'danger');
                return;
            }
            const header = ['Nombre','Email','Creado','Actualizado'];
            const lines = [header.join(',')];
            rows.forEach(r => {
                const cols = [...r.children].slice(0,4).map(td => '"' + td.textContent.replace(/"/g,'""') + '"');
                lines.push(cols.join(','));
            });
            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'cuadrilleros_'+Date.now()+'.csv'; a.click();
            URL.revokeObjectURL(url);
            showToast('CSV generado', 'primary');
        });
    }
})();
</script>
<script src="assets/js/admin-nav.js"></script>
</body>
</html>
