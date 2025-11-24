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

$userName = trim((string) ($_SESSION['user_name'] ?? 'Administrador'));

$availableFincas = [];
$availablePeones = [];
$cuadrilleros = [];
$fincasJson = '[]';
$peonesJson = '[]';
$cuadrillerosJson = '[]';

try {
	$stmtFincas = $pdo->query('SELECT id, nombre, link_ubicacion, descripcion, tarea_asignada, observacion FROM fincas ORDER BY nombre ASC');
	$availableFincas = $stmtFincas->fetchAll(PDO::FETCH_ASSOC) ?: [];

	$stmtPeones = $pdo->query('SELECT p.*, u.nombre AS cuadrilla_nombre FROM peones p LEFT JOIN usuarios u ON u.id = p.cuadrilla_id ORDER BY p.nombre ASC, p.apellido ASC');
	$availablePeones = $stmtPeones->fetchAll(PDO::FETCH_ASSOC) ?: [];

	$stmtCuadrilleros = $pdo->prepare('SELECT id, nombre FROM usuarios WHERE rol = :rol ORDER BY nombre ASC');
	$stmtCuadrilleros->execute([':rol' => 'cuadrillero']);
	$cuadrilleros = $stmtCuadrilleros->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
	error_log('Error cargando datos en panel admin: ' . $e->getMessage());
}

$fincasJson = json_encode($availableFincas, JSON_UNESCAPED_UNICODE) ?: '[]';
$peonesJson = json_encode($availablePeones, JSON_UNESCAPED_UNICODE) ?: '[]';
$cuadrillerosJson = json_encode($cuadrilleros, JSON_UNESCAPED_UNICODE) ?: '[]';
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Panel Administrador | Estate</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
	<link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
	<header class="admin-hero">
		<nav class="navbar navbar-dark admin-navbar">
			<div class="container-fluid py-2">
				<div class="d-flex align-items-center gap-2 gap-sm-3 flex-wrap">
					<img src="assets/img/logo.png" class="admin-logo" alt="Estate">
					<div class="d-flex flex-column">
						<span class="navbar-brand fs-6 mb-0">Estate · Panel Administrador</span>
						<small class="text-white-50">Gestión integral de cuadrillas y fincas</small>
					</div>
				</div>
				<div class="d-flex align-items-center gap-2 gap-sm-3 text-white user-actions">
					<a class="btn btn-outline-light btn-sm" href="trabajadores-admin.php">
						<i class="bi bi-table me-1"></i>Vista detallada
					</a>
					<span class="fw-semibold"><i class="bi bi-person-badge me-2"></i><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
					<a class="btn btn-outline-light btn-sm" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
				</div>
			</div>
		</nav>
	</header>

	<div class="toast-container position-fixed top-0 end-0 p-3">
		<div id="actionToast" class="toast text-bg-primary align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="d-flex">
				<div class="toast-body" id="toastBody">Acción completada.</div>
				<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
			</div>
		</div>
	</div>

	<main class="dashboard-shell">
		<div class="container-fluid py-4">
			<div class="row g-3 mb-4 stats-grid">
				<div class="col-sm-6 col-lg-3">
					<div class="card stat-card p-3">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<p class="text-muted mb-1">Peones registrados</p>
								<h3 class="mb-0" id="statTrabajadores">0</h3>
							</div>
							<div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
						</div>
						<small class="text-success">Seguimiento diario</small>
					</div>
				</div>
				<div class="col-sm-6 col-lg-3">
					<div class="card stat-card p-3">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<p class="text-muted mb-1">Fincas activas</p>
								<h3 class="mb-0" id="statFincas">0</h3>
							</div>
							<div class="stat-icon bg-success-subtle text-success"><i class="bi bi-pin-map"></i></div>
						</div>
						<small class="text-success">Actualiza asignaciones</small>
					</div>
				</div>
				<div class="col-sm-6 col-lg-3">
					<div class="card stat-card p-3">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<p class="text-muted mb-1">Asistencias hoy</p>
								<h3 class="mb-0" id="statAsistenciaHoy">0</h3>
								<small class="text-muted" id="statAsistenciaPct">Sin registros</small>
							</div>
							<div class="stat-icon bg-info-subtle text-info"><i class="bi bi-clipboard-check"></i></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6 col-lg-3">
					<div class="card stat-card p-3">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<p class="text-muted mb-1">Ausencias</p>
								<h3 class="mb-0" id="statAusencias">0</h3>
							</div>
							<div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-octagon"></i></div>
						</div>
					</div>
				</div>
			</div>

			<div class="row g-4 mb-4">
				<div class="col-lg-6">
					<div class="card form-section p-4 h-100">
						<div class="d-flex justify-content-between align-items-center mb-3">
							<div>
								<h2 class="h5 mb-0">Registrar peón</h2>
								<small class="text-muted">Carga rápida de integrantes de cuadrilla</small>
							</div>
						</div>
						<form id="workerForm" action="guardar_trabajador.php" method="post" class="row g-3">
							<div class="col-sm-6">
								<label class="form-label">Nombre</label>
								<input type="text" name="nombre" class="form-control" required>
							</div>
							<div class="col-sm-6">
								<label class="form-label">Apellido</label>
								<input type="text" name="apellido" class="form-control" required>
							</div>
							<div class="col-sm-6">
								<label class="form-label">DNI</label>
								<input type="text" name="dni" class="form-control" required>
							</div>
							<div class="col-sm-6">
								<label class="form-label">Teléfono</label>
								<input type="tel" name="telefono" class="form-control" placeholder="Opcional">
							</div>
							<div class="col-sm-6">
								<label class="form-label">Fecha de ingreso</label>
								<input type="date" name="fecha_ingreso" class="form-control" required>
							</div>
							<div class="col-sm-6">
								<label class="form-label">Estado</label>
								<select name="estado" class="form-select">
									<option value="activo">Activo</option>
									<option value="inactivo">Inactivo</option>
								</select>
							</div>
							<div class="col-12">
								<label class="form-label">Cuadrillero responsable</label>
								<select name="cuadrilla_id" class="form-select" id="workerCuadrillaSelect">
									<option value="">Sin asignar</option>
									<?php foreach ($cuadrilleros as $cuadrillero): ?>
										<option value="<?php echo (int) $cuadrillero['id']; ?>"><?php echo htmlspecialchars($cuadrillero['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="col-12 text-end">
								<button type="submit" class="btn btn-primary">Guardar peón</button>
							</div>
						</form>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="card form-section p-4 h-100">
						<div class="d-flex justify-content-between align-items-center mb-3">
							<div>
								<h2 class="h5 mb-0">Registrar finca</h2>
								<small class="text-muted">Carga ubicaciones y tareas asignadas</small>
							</div>
						</div>
						<form id="farmForm" action="guardar_finca.php" method="post" class="row g-3">
							<div class="col-sm-6">
								<label class="form-label">Nombre</label>
								<input type="text" name="nombre" class="form-control" required>
							</div>
							<div class="col-sm-6">
								<label class="form-label">Link ubicación</label>
								<input type="url" name="link_ubicacion" class="form-control" placeholder="https://..." required>
							</div>
							<div class="col-12">
								<label class="form-label">Descripción</label>
								<textarea name="descripcion" class="form-control" rows="2" placeholder="Notas generales"></textarea>
							</div>
							<div class="col-sm-6">
								<label class="form-label">Tarea asignada</label>
								<input type="text" name="tarea_asignada" class="form-control" placeholder="Raleo, cosecha, etc.">
							</div>
							<div class="col-sm-6">
								<label class="form-label">Observación</label>
								<input type="text" name="observacion" class="form-control" placeholder="Observaciones opcionales">
							</div>
							<div class="col-12 text-end">
								<button type="submit" class="btn btn-success">Guardar finca</button>
							</div>
						</form>
					</div>
				</div>
			</div>

			<div class="row g-4 mb-4">
				<div class="col-xl-7">
					<div class="card table-card border-0 h-100">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
								<div>
									<h2 class="h5 mb-1">Peones registrados</h2>
									<small class="text-muted">Actualiza datos y asigna cuadrilleros.</small>
								</div>
								<span class="badge bg-primary-subtle text-primary"><?php echo count($availablePeones); ?> activos</span>
							</div>
							<div class="table-responsive">
								<table class="table align-middle table-hover" id="workersTable">
									<thead class="table-light">
										<tr>
											<th>Peón</th>
											<th>DNI</th>
											<th>Teléfono</th>
											<th>Cuadrillero</th>
											<th>Ingreso</th>
											<th></th>
										</tr>
									</thead>
									<tbody></tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-5">
					<div class="card table-card border-0 h-100">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
								<div>
									<h2 class="h5 mb-1">Fincas registradas</h2>
									<small class="text-muted">Controla enlaces y tareas activas.</small>
								</div>
								<span class="badge bg-success-subtle text-success"><?php echo count($availableFincas); ?> activas</span>
							</div>
							<div class="table-responsive">
								<table class="table align-middle" id="farmsTable">
									<thead class="table-light">
										<tr>
											<th>Finca</th>
											<th>Ubicación</th>
											<th>Tarea</th>
											<th>Observación</th>
											<th></th>
										</tr>
									</thead>
									<tbody></tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="card border-0 shadow-sm">
				<div class="card-body">
					<div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-between gap-3">
						<div class="row g-3 flex-grow-1">
							<div class="col-md-4">
								<label class="form-label">Vista rápida</label>
								<select id="viewRange" class="form-select">
									<option value="hoy">Hoy</option>
									<option value="semana" selected>Últimos 7 días</option>
									<option value="mes">Últimos 30 días</option>
									<option value="personalizado">Personalizado</option>
								</select>
							</div>
							<div class="col-md-4">
								<label class="form-label">Desde</label>
								<input type="date" id="startDate" class="form-control">
							</div>
							<div class="col-md-4">
								<label class="form-label">Hasta</label>
								<input type="date" id="endDate" class="form-control">
							</div>
							<div class="col-md-4">
								<label class="form-label">Filtrar por finca</label>
								<select id="filterFinca" class="form-select">
									<option value="">Todas</option>
								</select>
							</div>
						</div>
						<div class="d-flex gap-2">
							<button class="btn btn-outline-primary" id="btnExportCsv">Exportar CSV</button>
							<button class="btn btn-outline-success" id="btnExportXlsx">Exportar XLSX</button>
							<button class="btn btn-outline-secondary" id="btnPrint"><i class="bi bi-printer me-1"></i>Imprimir</button>
						</div>
					</div>
					<div class="table-responsive mt-4">
						<table class="table align-middle" id="attendanceTable">
							<thead class="table-light">
								<tr>
									<th>Fecha</th>
									<th>Trabajador</th>
									<th>Finca</th>
									<th>Entrada</th>
									<th>Salida</th>
									<th>Horas</th>
									<th>Estado</th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</main>

	<!-- Modales y componentes auxiliares -->
	<div class="modal fade" id="workerSuccessModal" tabindex="-1" aria-labelledby="workerSuccessModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header border-0 pb-0">
					<h1 class="modal-title fs-5" id="workerSuccessModalLabel">Peón registrado</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
				</div>
				<div class="modal-body" id="workerSuccessModalBody">Peón guardado correctamente.</div>
				<div class="modal-footer border-0 pt-0">
					<button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="farmSuccessModal" tabindex="-1" aria-labelledby="farmSuccessModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header border-0 pb-0">
					<h1 class="modal-title fs-5" id="farmSuccessModalLabel">Finca registrada</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
				</div>
				<div class="modal-body" id="farmSuccessModalBody">Finca guardada correctamente.</div>
				<div class="modal-footer border-0 pt-0">
					<button type="button" class="btn btn-success" data-bs-dismiss="modal">Listo</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="workerEditModal" tabindex="-1" aria-labelledby="workerEditModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="workerEditModalLabel">Editar peón</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
				</div>
				<form id="workerEditForm" action="actualizar_trabajador.php" method="post" class="modal-body row g-3">
					<input type="hidden" name="id" id="editWorkerId">
					<div class="col-sm-6">
						<label class="form-label">Nombre</label>
						<input type="text" name="nombre" id="editWorkerName" class="form-control" required>
					</div>
					<div class="col-sm-6">
						<label class="form-label">Apellido</label>
						<input type="text" name="apellido" id="editWorkerLastName" class="form-control" required>
					</div>
					<div class="col-sm-6">
						<label class="form-label">DNI</label>
						<input type="text" name="dni" id="editWorkerDocument" class="form-control" required>
					</div>
					<div class="col-sm-6">
						<label class="form-label">Teléfono</label>
						<input type="tel" name="telefono" id="editWorkerPhone" class="form-control">
					</div>
					<div class="col-sm-6">
						<label class="form-label">Fecha de ingreso</label>
						<input type="date" name="fecha_ingreso" id="editWorkerInicio" class="form-control" required>
					</div>
					<div class="col-sm-6">
						<label class="form-label">Estado</label>
						<select name="estado" id="editWorkerStatus" class="form-select">
							<option value="activo">Activo</option>
							<option value="inactivo">Inactivo</option>
						</select>
					</div>
					<div class="col-12">
						<label class="form-label">Cuadrillero responsable</label>
						<select name="cuadrilla_id" id="editWorkerCuadrilla" class="form-select">
							<option value="">Sin asignar</option>
							<?php foreach ($cuadrilleros as $cuadrillero): ?>
								<option value="<?php echo (int) $cuadrillero['id']; ?>"><?php echo htmlspecialchars($cuadrillero['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-12 text-end">
						<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
						<button type="submit" class="btn btn-primary">Guardar cambios</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<div class="modal fade" id="farmEditModal" tabindex="-1" aria-labelledby="farmEditModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="farmEditModalLabel">Editar finca</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
				</div>
				<form id="farmEditForm" action="actualizar_finca.php" method="post" class="modal-body row g-3">
					<input type="hidden" name="id" id="editFarmId">
					<div class="col-12">
						<label class="form-label">Nombre</label>
						<input type="text" name="nombre" id="editFarmNombre" class="form-control" required>
					</div>
					<div class="col-12">
						<label class="form-label">Link ubicación</label>
						<input type="url" name="link_ubicacion" id="editFarmLink" class="form-control" required>
					</div>
					<div class="col-12">
						<label class="form-label">Descripción</label>
						<textarea name="descripcion" id="editFarmDescripcion" class="form-control" rows="2"></textarea>
					</div>
					<div class="col-sm-6">
						<label class="form-label">Tarea asignada</label>
						<input type="text" name="tarea_asignada" id="editFarmTarea" class="form-control">
					</div>
					<div class="col-sm-6">
						<label class="form-label">Observación</label>
						<input type="text" name="observacion" id="editFarmObservacion" class="form-control">
					</div>
					<div class="col-12 text-end">
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
					<h1 class="modal-title fs-5" id="confirmDeleteModalLabel">Confirmar eliminación</h1>
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
	<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
	<script>
		window.__FincasData = <?php echo $fincasJson; ?>;
		window.__PeonesData = <?php echo $peonesJson; ?>;
		window.__CuadrillerosData = <?php echo $cuadrillerosJson; ?>;
	</script>
	<script src="assets/js/panel-admin.js"></script>
</body>
</html>
