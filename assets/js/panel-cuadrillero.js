document.addEventListener('DOMContentLoaded', () => {
    const data = window.__CuadrilleroData || {};
    const stats = data.stats || {};
    const assignedWorkers = Array.isArray(data.assignedWorkers) ? data.assignedWorkers : [];

    const setText = (id, value, fallback = '') => {
        const el = document.getElementById(id);
        if (el) el.textContent = value ?? fallback;
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

    const uploadButtons = document.querySelectorAll('.js-upload-peon-photo');
    const peonPhotoForm = document.getElementById('peonPhotoForm');
    const peonPhotoInput = document.getElementById('peonPhotoFile');
    const peonPhotoId = document.getElementById('peonPhotoPeonId');
    const uploadStatus = document.getElementById('photoUploadStatus');

    if (peonPhotoForm && peonPhotoInput && peonPhotoId) {
        uploadButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const peonId = btn.getAttribute('data-peon-id');
                const peonName = btn.getAttribute('data-peon-name') || 'peón';
                peonPhotoId.value = peonId || '';
                if (uploadStatus) {
                    uploadStatus.textContent = `Cargando foto para ${peonName}...`;
                }
                peonPhotoInput.value = '';
                peonPhotoInput.click();
            });
        });

        peonPhotoInput.addEventListener('change', () => {
            if (!peonPhotoInput.files || peonPhotoInput.files.length === 0) {
                if (uploadStatus) {
                    uploadStatus.textContent = '';
                }
                return;
            }
            if (uploadStatus) {
                uploadStatus.textContent = 'Subiendo foto...';
            }
            peonPhotoForm.submit();
        });
    }
});
