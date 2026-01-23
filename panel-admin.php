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
$attendanceData = [];
$fincasJson = '[]';
$peonesJson = '[]';
$cuadrillerosJson = '[]';
$attendanceJson = '[]';

try {
	$stmtFincas = $pdo->query('SELECT id, nombre, link_ubicacion, descripcion, tarea_asignada, observacion FROM fincas ORDER BY nombre ASC');
	$availableFincas = $stmtFincas->fetchAll(PDO::FETCH_ASSOC) ?: [];

	$stmtPeones = $pdo->query('SELECT p.*, u.nombre AS cuadrilla_nombre FROM peones p LEFT JOIN usuarios u ON u.id = p.cuadrilla_id ORDER BY p.nombre ASC, p.apellido ASC');
	$availablePeones = $stmtPeones->fetchAll(PDO::FETCH_ASSOC) ?: [];

	$stmtCuadrilleros = $pdo->prepare('SELECT id, nombre FROM usuarios WHERE rol = :rol ORDER BY nombre ASC');
	$stmtCuadrilleros->execute([':rol' => 'cuadrillero']);
	$cuadrilleros = $stmtCuadrilleros->fetchAll(PDO::FETCH_ASSOC) ?: [];

	// Asistencias últimos 30 días (placeholder sin horas de entrada/salida)
	try {
		$attStmt = $pdo->query("SELECT a.id, a.fecha, a.presente, p.id AS peon_id, p.nombre, p.apellido, p.dni, f.id AS finca_id, f.nombre AS finca_nombre, u.id AS cuadrillero_id, u.nombre AS cuadrillero_nombre\n			FROM asistencias_peones a\n			JOIN peones p ON p.id = a.peon_id\n			JOIN fincas f ON f.id = a.finca_id\n			LEFT JOIN usuarios u ON u.id = f.cuadrillero_id\n			WHERE a.fecha >= (CURDATE() - INTERVAL 30 DAY)\n			ORDER BY a.fecha DESC, p.nombre ASC");
		$attendanceData = $attStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	} catch (Throwable $eAtt) {
		error_log('Error cargando asistencias: ' . $eAtt->getMessage());
	}
} catch (Throwable $e) {
	error_log('Error cargando datos en panel admin: ' . $e->getMessage());
}

$fincasJson = json_encode($availableFincas, JSON_UNESCAPED_UNICODE) ?: '[]';
$peonesJson = json_encode($availablePeones, JSON_UNESCAPED_UNICODE) ?: '[]';
$cuadrillerosJson = json_encode($cuadrilleros, JSON_UNESCAPED_UNICODE) ?: '[]';
$attendanceJson = json_encode($attendanceData, JSON_UNESCAPED_UNICODE) ?: '[]';
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Panel Administrador | Estate</title>
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
						<small class="text-white-50" style="font-size: 0.75rem;">Gestión integral</small>
					</div>
				</a>
				
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				
				<div class="collapse navbar-collapse" id="adminNavbar">
					<ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-2">
						<li class="nav-item">
							<a class="nav-link active" href="panel-admin.php">
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
							<a class="nav-link" href="fincas-admin.php">
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
							<div class="col-12">
								<div class="border rounded-3 p-3 bg-light-subtle d-flex gap-3 align-items-center">
									<div class="icon-circle bg-primary text-white"><i class="bi bi-person-plus"></i></div>
									<div>
										<strong>Datos personales</strong>
										<p class="text-muted mb-0">Completá la información tal como figura en el documento del trabajador.</p>
									</div>
								</div>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Nombre</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-person"></i></span>
									<input type="text" name="nombre" class="form-control" placeholder="Ej. Juan" required>
								</div>
								<small class="form-text text-muted">Nombre tal como figura en el DNI.</small>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Apellido</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
									<input type="text" name="apellido" class="form-control" placeholder="Ej. Pérez" required>
								</div>
								<small class="form-text text-muted">Apellidos completos.</small>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">DNI</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-credit-card"></i></span>
									<input type="text" name="dni" class="form-control" placeholder="Sin puntos" required>
								</div>
								<small class="form-text text-muted">Solo números, sin espacios ni guiones.</small>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Teléfono</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-telephone"></i></span>
									<input type="tel" name="telefono" class="form-control" placeholder="+54 9 ...">
								</div>
								<small class="form-text text-muted">Campo opcional para contacto inmediato.</small>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Fecha de ingreso</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
									<input type="date" name="fecha_ingreso" class="form-control" required>
								</div>
								<small class="form-text text-muted">Usa el formato calendario para evitar errores.</small>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Estado</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-activity"></i></span>
									<select name="estado" class="form-select">
										<option value="activo">Activo</option>
										<option value="inactivo">Inactivo</option>
									</select>
								</div>
								<small class="form-text text-muted">Define si puede ser asignado hoy.</small>
							</div>
							<div class="col-12">
								<label class="form-label fw-semibold">Cuadrillero responsable</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-people"></i></span>
									<select name="cuadrilla_id" class="form-select" id="workerCuadrillaSelect">
										<option value="">Sin asignar</option>
										<?php foreach ($cuadrilleros as $cuadrillero): ?>
											<option value="<?php echo (int) $cuadrillero['id']; ?>"><?php echo htmlspecialchars($cuadrillero['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<small class="form-text text-muted">Si aún no tiene cuadrilla dejalo "Sin asignar".</small>
							</div>
							<div class="col-12 text-end d-flex justify-content-end gap-2">
								<button type="reset" class="btn btn-outline-secondary">Limpiar</button>
								<button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar peón</button>
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
							<div class="col-12">
								<div class="border rounded-3 p-3 bg-success-subtle d-flex gap-3 align-items-center">
									<div class="icon-circle bg-success text-white"><i class="bi bi-geo-alt"></i></div>
									<div>
										<strong>Datos de la finca</strong>
										<p class="text-muted mb-0">Agregá referencias claras para que cuadrilleros y peones ubiquen la tarea.</p>
									</div>
								</div>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Nombre</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-signpost"></i></span>
									<input type="text" name="nombre" class="form-control" placeholder="Ej. Finca Norte" required>
								</div>
								<small class="form-text text-muted">Usá un nombre corto y reconocible.</small>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Link ubicación</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
									<input type="url" name="link_ubicacion" class="form-control" placeholder="https://maps.app.goo.gl/..." required>
								</div>
								<small class="form-text text-muted">Pegá el enlace de Google Maps u otra referencia.</small>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Cuadrillero responsable</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
									<select name="cuadrillero_id" class="form-select">
										<option value="">Sin asignar</option>
										<?php foreach ($cuadrilleros as $c): ?>
											<option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<small class="form-text text-muted">Asignar quién coordina la finca.</small>
							</div>
							<div class="col-12">
								<label class="form-label fw-semibold">Descripción general</label>
								<textarea name="descripcion" class="form-control" rows="3" placeholder="Notas generales, accesos, etc."></textarea>
								<small class="form-text text-muted">Información adicional que ayude a llegar o planificar.</small>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Tarea asignada</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-clipboard-data"></i></span>
									<input type="text" name="tarea_asignada" class="form-control" placeholder="Raleo, cosecha, etc.">
								</div>
								<small class="form-text text-muted">Describe la tarea principal a realizar.</small>
							</div>
							<div class="col-sm-6">
								<label class="form-label fw-semibold">Observación</label>
								<div class="input-group">
									<span class="input-group-text"><i class="bi bi-chat-left-text"></i></span>
									<input type="text" name="observacion" class="form-control" placeholder="Observaciones opcionales">
								</div>
								<small class="form-text text-muted">Ej. "Ingreso por portón lateral".</small>
							</div>
							<div class="col-12 text-end d-flex justify-content-end gap-2">
								<button type="reset" class="btn btn-outline-secondary">Limpiar</button>
								<button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Guardar finca</button>
							</div>
						</form>
					</div>
				</div>
			</div>

            <!-- Se removieron las tarjetas resumen de Peones y Fincas; navegación ahora está en el header -->

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
							<div class="col-md-4">
								<label class="form-label">Filtrar por cuadrillero</label>
								<select id="filterCuadrillero" class="form-select">
									<option value="">Todos</option>
									<?php foreach ($cuadrilleros as $c): ?>
									<option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="col-md-4">
								<label class="form-label">Buscar nombre / DNI</label>
								<input type="text" id="filterSearch" class="form-control" placeholder="Ej. Juan o 1234...">
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
									<th data-sort="fecha" class="sortable">Fecha</th>
									<th data-sort="trabajador" class="sortable">Trabajador</th>
									<th data-sort="finca" class="sortable">Finca</th>
									<th data-sort="cuadrillero" class="sortable">Cuadrillero</th>
									<th data-sort="dni" class="sortable">DNI</th>
									<th data-sort="estado" class="sortable">Estado</th>
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
					<div class="col-12">
						<label class="form-label">Cuadrillero responsable</label>
						<select name="cuadrillero_id" id="editFarmCuadrillero" class="form-select">
							<option value="">Sin asignar</option>
							<?php foreach ($cuadrilleros as $c): ?>
								<option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars((string)$c['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
							<?php endforeach; ?>
						</select>
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
		window.__AttendanceData = <?php echo $attendanceJson; ?>;
	</script>
	<script src="assets/js/panel-admin.js"></script>
	<script src="assets/js/admin-nav.js"></script>
</body>
</html>
