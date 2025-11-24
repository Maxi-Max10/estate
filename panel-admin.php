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

	<main class="dashboard-shell">
		<div class="container-fluid py-4">
			<div class="row g-3 mb-4 stats-grid">
				<div class="col-md-3">
					<div class="card stat-card p-3">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<p class="text-muted mb-1">Peones registrados</p>
								<h3 class="mb-0" id="statTrabajadores">0</h3>
							</div>
							<div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
						</div>
						<small class="text-success">+3 nuevos esta semana</small>
					</div>
				</div>
			</div>

			<div class="row g-4 mb-4">
				<div class="col-lg-8">
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

				<div class="col-lg-4">
					<div class="card border-0 shadow-sm h-100">
						<div class="card-body">
							<h2 class="h5 mb-3">Resumen de fincas</h2>
							<p class="text-muted">Supervisa asignaciones y observaciones clave.</p>
							<ul class="list-unstyled mb-0" id="farmsSummary"></ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		window.__FincasData = <?php echo $fincasJson; ?>;
		window.__PeonesData = <?php echo $peonesJson; ?>;
		window.__CuadrillerosData = <?php echo $cuadrillerosJson; ?>;
	</script>
	<script src="assets/js/panel-admin.js"></script>
</body>
</html>
