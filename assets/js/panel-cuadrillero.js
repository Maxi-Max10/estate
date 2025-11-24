document.addEventListener('DOMContentLoaded', () => {
    const data = window.__CuadrilleroData || {};
    const stats = data.stats || {};
    const assignedFarms = Array.isArray(data.assignedFarms) ? data.assignedFarms : [];
    const assignedWorkers = Array.isArray(data.assignedWorkers) ? data.assignedWorkers : [];

    const setText = (id, value, fallback = '') => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value ?? fallback;
        }
    };

    setText('summaryAsistencia', stats.farms ?? 0);
    setText('summaryAsistenciaMeta', stats.farmsLabel ?? 'Fincas asignadas');
    setText('summaryTareas', stats.tasks ?? 0);
    setText('summaryTareasMeta', stats.tasksLabel ?? 'Con tareas registradas');
    setText('summaryAlertas', stats.alerts ?? 0);
    setText('summaryAlertasMeta', stats.alertsLabel ?? 'Observaciones pendientes');
    setText('summaryPeonesTotales', stats.workers ?? assignedWorkers.length);
    setText('summaryPeonesActivos', stats.workersActive ?? assignedWorkers.filter(w => (w.estado || '').toLowerCase() === 'activo').length);
    setText('summaryPeonesInactivos', stats.workersInactive ?? assignedWorkers.filter(w => (w.estado || '').toLowerCase() === 'inactivo').length);

    const fallbackTimeline = [
        { hora: '07:30', titulo: 'Check-in general', detalle: 'Registro biométrico en finca principal' },
        { hora: '09:30', titulo: 'Supervisión de riego', detalle: 'Revisión de válvulas y presión' },
        { hora: '14:00', titulo: 'Control sanitario', detalle: 'Checklist de EPP con la cuadrilla' },
    ];

    const timeline = assignedFarms.length
        ? assignedFarms.map((farm, index) => ({
              hora: `${String(7 + index * 2).padStart(2, '0')}:00`,
              titulo: farm.nombre || `Finca ${index + 1}`,
              detalle: farm.tarea ? `Tarea: ${farm.tarea}` : 'Seguimiento general del predio',
          }))
        : fallbackTimeline;

    const safetyChecks = [
        { titulo: 'Extintores al día', progreso: 80, estado: 'En revisión' },
        { titulo: 'Capacitación EPP', progreso: 55, estado: 'Pendiente' },
        { titulo: 'Botiquines completos', progreso: 100, estado: 'Completado' },
    ];

    const timelineList = document.getElementById('timelineList');
    if (timelineList) {
        timelineList.innerHTML = '';
        if (timeline.length === 0) {
            timelineList.innerHTML = '<p class="text-muted small mb-0">Sin hitos registrados aún.</p>';
        } else {
            timeline.forEach(item => {
                const wrapper = document.createElement('div');
                wrapper.className = 'timeline-item';
                wrapper.innerHTML = `
                    <div class="fw-semibold">${item.hora} · ${item.titulo}</div>
                    <p class="text-muted small mb-0">${item.detalle}</p>`;
                timelineList.appendChild(wrapper);
            });
        }
    }

    const derivedTasks = assignedFarms
        .filter(farm => typeof farm.tarea === 'string' && farm.tarea.trim() !== '')
        .map(farm => ({
            titulo: farm.tarea,
            finca: farm.nombre || 'Finca sin nombre',
            estado: farm.observacion ? 'Observación' : 'En curso',
            prioridad: farm.observacion ? 'alta' : 'media',
        }));

    // Posible futuro: render dinámico de peones (ya se muestra server-side)
    // Ejemplo placeholder si se quisiera insertar en un contenedor JS:
    const workersContainer = document.getElementById('workersAssignedList');
    if (workersContainer && !workersContainer.dataset.rendered) {
        workersContainer.dataset.rendered = 'true';
        if (!assignedWorkers.length) {
            workersContainer.innerHTML = '<p class="text-muted small mb-0">Sin peones asignados.</p>';
        } else {
            assignedWorkers.forEach(w => {
                const row = document.createElement('div');
                row.className = 'd-flex justify-content-between border-bottom py-2 small';
                row.innerHTML = `<span>${(w.nombre || '') + ' ' + (w.apellido || '')}</span><span>${(w.estado || 'activo')}</span>`;
                workersContainer.appendChild(row);
            });
        }
    }

    const taskList = document.getElementById('taskList');
    if (taskList) {
        taskList.innerHTML = '';
        if (!derivedTasks.length) {
            taskList.innerHTML = '<div class="list-group-item text-muted small">Aún no hay tareas registradas para tus fincas.</div>';
        } else {
            derivedTasks.forEach(task => {
                const item = document.createElement('div');
                item.className = 'list-group-item d-flex justify-content-between align-items-center';
                let badgeClass = 'bg-warning text-dark';
                if (task.estado === 'Observación') {
                    badgeClass = 'bg-danger-subtle text-danger';
                }
                item.innerHTML = `
                    <div>
                        <div class="fw-semibold">${task.titulo}</div>
                        <small class="text-muted">${task.finca}</small>
                    </div>
                    <span class="badge ${badgeClass} task-status">${task.estado}</span>`;
                taskList.appendChild(item);
            });
        }
    }

    const safetyContainer = document.getElementById('safetyChecks');
    safetyChecks.forEach(check => {
        const col = document.createElement('div');
        col.className = 'col-sm-6 col-lg-4';
        col.innerHTML = `
            <div class="border rounded-3 p-3 h-100">
                <div class="fw-semibold mb-1">${check.titulo}</div>
                <div class="text-muted small mb-2">${check.estado}</div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: ${check.progreso}%;"></div>
                </div>
                <small class="text-muted">${check.progreso}% completado</small>
            </div>`;
        safetyContainer.appendChild(col);
    });
});
