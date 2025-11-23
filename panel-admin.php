<?php
declare(strict_types=1);

                <div class="col-lg-6">
                    <div class="card form-section p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 mb-0">Registrar peón</h2>
                                <small class="text-muted">Carga rápida de personal de cuadrillas</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo</span>
                        </div>
                        <form id="workerForm" class="row g-3" method="post" action="guardar_trabajador.php" novalidate>
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido</label>
                                <input type="text" class="form-control" name="apellido" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">DNI</label>
                                <input type="text" class="form-control" name="dni" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" name="telefono" placeholder="099 123 456">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de ingreso</label>
                                <input type="date" class="form-control" name="fecha_ingreso" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado" required>
                                    <option value="activo" selected>Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Cuadrillero asignado</label>
                                <select class="form-select" name="cuadrilla_id">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($cuadrilleros as $cuadrillero): ?>
                                        <option value="<?php echo (int) $cuadrillero['id']; ?>">
                                            <?php echo htmlspecialchars($cuadrillero['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Define quién supervisa al peón.</small>
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-outline-secondary me-2" type="reset">Limpiar</button>
                                <button class="btn btn-primary" type="submit">Guardar peón</button>
                            </div>
                        </form>
                    </div>
                </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="dashboard-shell">
        <div class="container-fluid">
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
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Fincas registradas</p>
                                <h3 class="mb-0" id="statFincas">0</h3>
                            </div>
                            <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-map"></i></div>
                        </div>
                        <small class="text-warning">2 inspecciones pendientes</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Asistencias hoy</p>
                                <h3 class="mb-0" id="statAsistenciaHoy">0</h3>
                            </div>
                            <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></div>
                        </div>
                        <small class="text-success" id="statAsistenciaPct">0% del total</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Ausencias</p>
                                <h3 class="mb-0" id="statAusencias">0</h3>
                            </div>
                            <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-octagon"></i></div>
                        </div>
                        <small class="text-danger">Revisar justificativos</small>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card form-section p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 mb-0">Registrar peón</h2>
                                <small class="text-muted">Carga rápida de personal de cuadrillas</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo</span>
                        </div>
                        <form id="workerForm" class="row g-3" method="post" action="guardar_trabajador.php" novalidate>
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido</label>
                                <input type="text" class="form-control" name="apellido" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">DNI</label>
                                <input type="text" class="form-control" name="dni" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" name="telefono" placeholder="099 123 456">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de ingreso</label>
                                <input type="date" class="form-control" name="fecha_ingreso" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado" required>
                                    <option value="activo" selected>Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Cuadrillero asignado</label>
                                <select class="form-select" name="cuadrilla_id">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($cuadrilleros as $cuadrillero): ?>
                                        <option value="<?php echo (int) $cuadrillero['id']; ?>">
                                            <?php echo htmlspecialchars($cuadrillero['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Define quién supervisa al peón.</small>
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-outline-secondary me-2" type="reset">Limpiar</button>
                                <button class="btn btn-primary" type="submit">Guardar peón</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card form-section p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 mb-0">Registrar finca</h2>
                                <small class="text-muted">Administra predios y cuadrillas asignadas</small>
                            </div>
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-building-add me-1"></i>Infraestructura</span>
                        </div>
                        <form id="farmForm" class="row g-3" method="post" action="guardar_finca.php" novalidate>
                            <div class="col-12">
                                <label class="form-label">Nombre de la finca</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Link de ubicación</label>
                                <input type="url" class="form-control" name="link_ubicacion" placeholder="https://maps.google.com/..." required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" rows="2" name="descripcion"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tarea asignada</label>
                                <textarea class="form-control" rows="2" name="tarea_asignada"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observación</label>
                                <textarea class="form-control" rows="2" name="observacion"></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-outline-secondary me-2" type="reset">Limpiar</button>
                                <button class="btn btn-success" type="submit">Guardar finca</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card form-section p-4 h-100">
                        <h2 class="h5 mb-3">Acciones rápidas</h2>
                        <p class="info-text">Centraliza tareas frecuentes para reaccionar ante eventos diarios.</p>
                        <div class="list-group list-group-flush">
                            <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" type="button">
                                Programar capacitación
                                <span class="badge bg-primary rounded-pill"><i class="bi bi-calendar-event"></i></span>
                            </button>
                            <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" type="button">
                                Crear comunicado a cuadrillas
                                <span class="badge bg-info rounded-pill"><i class="bi bi-megaphone"></i></span>
                            </button>
                            <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" type="button">
                                Generar reporte mensual
                                <span class="badge bg-success rounded-pill"><i class="bi bi-clipboard-data"></i></span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card form-section p-4 h-100">
                        <h2 class="h5 mb-3">Alertas operativas</h2>
                        <p class="info-text">Mantente al tanto de incidencias y ausencias críticas.</p>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Ausencias consecutivas (3)
                                <span class="badge bg-danger rounded-pill">Revisar</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Equipos con mantenimiento pendiente
                                <span class="badge bg-warning text-dark rounded-pill">2</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Licencias a vencer
                                <span class="badge bg-secondary rounded-pill">5 días</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card table-card border-0 shadow-lg mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                        <div>
                            <h2 class="h5 mb-1">Control de asistencia</h2>
                            <small class="info-text">Filtra por rango y exporta a CSV/XLSX</small>
                        </div>
                        <form id="filtersForm" class="row g-3 align-items-end flex-grow-1 filters-form">
                            <div class="col-md-3">
                                <label class="form-label">Ver</label>
                                <select id="viewRange" class="form-select">
                                    <option value="hoy">Hoy</option>
                                    <option value="semana">Últimos 7 días</option>
                                    <option value="mes">Últimos 30 días</option>
                                    <option value="personalizado">Personalizado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Desde</label>
                                <input type="date" id="startDate" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hasta</label>
                                <input type="date" id="endDate" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Finca</label>
                                <select id="filterFinca" class="form-select">
                                    <option value="">Todas</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3 action-buttons">
                        <button class="btn btn-outline-primary" id="btnExportCsv"><i class="bi bi-filetype-csv me-1"></i>Exportar CSV</button>
                        <button class="btn btn-outline-success" id="btnExportXlsx"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar XLSX</button>
                        <button class="btn btn-outline-secondary" id="btnPrint"><i class="bi bi-printer me-1"></i>Imprimir</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover" id="attendanceTable">
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
    </div>

    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="actionToast" class="toast align-items-center text-bg-primary border-0" role="status" aria-live="assertive">
            <div class="d-flex">
                <div class="toast-body" id="toastBody">Guardado con éxito.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="workerSuccessModal" tabindex="-1" aria-labelledby="workerSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h1 class="modal-title fs-5" id="workerSuccessModalLabel">Peón registrado</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="workerSuccessModalBody">
                    El peón fue guardado correctamente.
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Listo</button>
                </div>
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
                                <input type="tel" class="form-control" name="telefono" id="editWorkerPhone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de ingreso</label>
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
                                <label class="form-label">Cuadrillero asignado</label>
                                <select class="form-select" name="cuadrilla_id" id="editWorkerCuadrilla">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($cuadrilleros as $cuadrillero): ?>
                                        <option value="<?php echo (int) $cuadrillero['id']; ?>">
                                            <?php echo htmlspecialchars($cuadrillero['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
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

    <div class="modal fade" id="farmSuccessModal" tabindex="-1" aria-labelledby="farmSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h1 class="modal-title fs-5" id="farmSuccessModalLabel">Finca registrada</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="farmSuccessModalBody">
                    La finca se guardó correctamente.
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Entendido</button>
                </div>
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
                                <input type="url" class="form-control" name="link_ubicacion" id="editFarmLink" required>
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
                    <h1 class="modal-title fs-5" id="confirmDeleteModalLabel">Confirmar eliminación</h1>
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
    <script src="https://cdn.jsdelivr.net/npm/sheetjs@0.20.0/dist/xlsx.full.min.js"></script>
    <script>
        window.__FincasData = <?php echo json_encode($availableFincas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window.__PeonesData = <?php echo json_encode($availablePeones, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        window.__CuadrillerosData = <?php echo json_encode($cuadrilleros, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    </script>
    <script src="assets/js/panel-admin.js?v=20241123"></script>
</body>
</html>
