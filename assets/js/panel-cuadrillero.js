document.addEventListener('DOMContentLoaded', () => {
    const summary = {
        asistencia: { valor: 18, meta: '90% de 20 programados' },
        tareas: { valor: 8, meta: '3 urgentes · 5 en curso' },
        alertas: 2,
    };

    const timeline = [
        { hora: '07:30', titulo: 'Check-in general', detalle: 'Registro biométrico en Finca Norte' },
        { hora: '09:00', titulo: 'Riego sector B', detalle: 'Equipo 2 completó 80% del turno' },
        { hora: '12:30', titulo: 'Pausa activa', detalle: 'Supervisión de seguridad realizada' },
        { hora: '15:00', titulo: 'Control de plagas', detalle: 'Aplicación eco en Finca Central' },
    ];

    const tasks = [
        { titulo: 'Verificar fumigación', finca: 'Finca Sur', estado: 'Pendiente', prioridad: 'alta' },
        { titulo: 'Subir reporte fotográfico', finca: 'Finca Norte', estado: 'En progreso', prioridad: 'media' },
        { titulo: 'Actualizar checklist de EPP', finca: 'Finca Central', estado: 'Completado', prioridad: 'baja' },
    ];

    const safetyChecks = [
        { titulo: 'Extintores al día', progreso: 80, estado: 'En revisión' },
        { titulo: 'Capacitación EPP', progreso: 55, estado: 'Pendiente' },
        { titulo: 'Botiquines completos', progreso: 100, estado: 'Completado' },
    ];

    document.getElementById('summaryAsistencia').textContent = summary.asistencia.valor;
    document.getElementById('summaryAsistenciaMeta').textContent = summary.asistencia.meta;
    document.getElementById('summaryTareas').textContent = summary.tareas.valor;
    document.getElementById('summaryTareasMeta').textContent = summary.tareas.meta;
    document.getElementById('summaryAlertas').textContent = summary.alertas;

    const timelineList = document.getElementById('timelineList');
    timeline.forEach(item => {
        const wrapper = document.createElement('div');
        wrapper.className = 'timeline-item';
        wrapper.innerHTML = `
            <div class="fw-semibold">${item.hora} · ${item.titulo}</div>
            <p class="text-muted small mb-0">${item.detalle}</p>`;
        timelineList.appendChild(wrapper);
    });

    const taskList = document.getElementById('taskList');
    tasks.forEach(task => {
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center';
        const badgeClass = task.estado === 'Completado' ? 'bg-success-subtle text-success' : task.estado === 'En progreso' ? 'bg-warning text-dark' : 'bg-danger-subtle text-danger';
        item.innerHTML = `
            <div>
                <div class="fw-semibold">${task.titulo}</div>
                <small class="text-muted">${task.finca} · Prioridad ${task.prioridad}</small>
            </div>
            <span class="badge ${badgeClass} task-status">${task.estado}</span>`;
        taskList.appendChild(item);
    });

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
