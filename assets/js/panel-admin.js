document.addEventListener('DOMContentLoaded', () => {
    const fallbackFincas = [
        { id: 1, nombre: 'Finca Norte', link_ubicacion: '#', descripcion: '', tarea_asignada: '', observacion: '' },
        { id: 2, nombre: 'Finca Sur', link_ubicacion: '#', descripcion: '', tarea_asignada: '', observacion: '' },
        { id: 3, nombre: 'Finca Central', link_ubicacion: '#', descripcion: '', tarea_asignada: '', observacion: '' },
    ];

    const normalizeFinca = finca => ({
        id: Number(finca.id) || 0,
        nombre: finca.nombre || 'Sin nombre',
        link_ubicacion: finca.link_ubicacion || '',
        descripcion: finca.descripcion || '',
        tarea_asignada: finca.tarea_asignada || '',
        observacion: finca.observacion || '',
    });

    const normalizeWorker = worker => ({
        id: Number(worker.id) || 0,
        nombre: worker.nombre || 'Sin nombre',
        documento: worker.documento || '',
        rol: worker.rol || 'colaborador',
        finca_id: worker.finca_id !== null && worker.finca_id !== undefined ? Number(worker.finca_id) : null,
        finca_nombre: worker.finca_nombre || null,
        especialidad: worker.especialidad || null,
        inicio_actividades: worker.inicio_actividades || '',
        observaciones: worker.observaciones || '',
    });

    let fincasData = Array.isArray(window.__FincasData) && window.__FincasData.length ? window.__FincasData.map(normalizeFinca) : fallbackFincas.map(normalizeFinca);
    let workersData = Array.isArray(window.__TrabajadoresData) ? window.__TrabajadoresData.map(normalizeWorker) : [];

    const attendanceData = [
        { fecha: '2025-11-22', trabajador: 'Juan Pérez', finca: 'Finca Norte', horaEntrada: '07:55', horaSalida: '17:10', horas: 9.25, estado: 'Presente' },
        { fecha: '2025-11-22', trabajador: 'María López', finca: 'Finca Central', horaEntrada: '08:05', horaSalida: '16:45', horas: 8.7, estado: 'Presente' },
        { fecha: '2025-11-22', trabajador: 'Carlos Silva', finca: 'Finca Sur', horaEntrada: '08:30', horaSalida: '15:00', horas: 6.5, estado: 'Licencia' },
        { fecha: '2025-11-21', trabajador: 'Lucía Torres', finca: 'Finca Norte', horaEntrada: '08:00', horaSalida: '17:00', horas: 9, estado: 'Presente' },
        { fecha: '2025-11-20', trabajador: 'Juan Pérez', finca: 'Finca Norte', horaEntrada: '08:10', horaSalida: '16:40', horas: 8.5, estado: 'Presente' },
        { fecha: '2025-11-19', trabajador: 'María López', finca: 'Finca Central', horaEntrada: '08:20', horaSalida: '16:20', horas: 8, estado: 'Ausente' },
    ];

    const toastEl = document.getElementById('actionToast');
    if (!toastEl) {
        return;
    }

    const toast = new bootstrap.Toast(toastEl);
    const toastBody = document.getElementById('toastBody');
    const attendanceTableBody = document.querySelector('#attendanceTable tbody');
    const viewRange = document.getElementById('viewRange');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const filterFinca = document.getElementById('filterFinca');
    const fincaDatalist = document.getElementById('fincaList');
    const workerForm = document.getElementById('workerForm');
    const workerRoleField = document.getElementById('workerRole');
    const workerFincaWrapper = document.getElementById('workerFincaWrapper');
    const workerFincaSelect = document.getElementById('workerFincaSelect');
    const workerEspecialidadInput = document.getElementById('workerEspecialidad');
    const workerSuccessModal = document.getElementById('workerSuccessModal') ? new bootstrap.Modal(document.getElementById('workerSuccessModal')) : null;
    const farmForm = document.getElementById('farmForm');
    const farmSuccessModal = document.getElementById('farmSuccessModal') ? new bootstrap.Modal(document.getElementById('farmSuccessModal')) : null;
    const farmSuccessModalBody = document.getElementById('farmSuccessModalBody');
    const workerSuccessModalBody = document.getElementById('workerSuccessModalBody');
    const workersTableBody = document.querySelector('#workersTable tbody');
    const farmsTableBody = document.querySelector('#farmsTable tbody');
    const workerEditModalEl = document.getElementById('workerEditModal');
    const workerEditModal = workerEditModalEl ? new bootstrap.Modal(workerEditModalEl) : null;
    const farmEditModalEl = document.getElementById('farmEditModal');
    const farmEditModal = farmEditModalEl ? new bootstrap.Modal(farmEditModalEl) : null;
    const workerEditForm = document.getElementById('workerEditForm');
    const farmEditForm = document.getElementById('farmEditForm');
    const editWorkerRoleField = document.getElementById('editWorkerRole');
    const editWorkerFincaWrapper = document.getElementById('editWorkerFincaWrapper');
    const editWorkerFincaSelect = document.getElementById('editWorkerFinca');
    const editWorkerEspecialidadInput = document.getElementById('editWorkerEspecialidad');
    const confirmDeleteModalEl = document.getElementById('confirmDeleteModal');
    const confirmDeleteModal = confirmDeleteModalEl ? new bootstrap.Modal(confirmDeleteModalEl) : null;
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const confirmDeleteBody = document.getElementById('confirmDeleteBody');

    let filteredData = [...attendanceData];
    let deleteContext = null;

    const showToast = (message, variant = 'primary') => {
        toastEl.className = `toast text-bg-${variant} align-items-center border-0`;
        toastBody.textContent = message;
        toast.show();
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
            throw new Error(payload.message || 'La operación no pudo completarse.');
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
        } else {
            input.value = '';
        }
    };

    const fillFincaSelect = (selectEl, selectedId = '') => {
        if (!selectEl) return;
        const previous = selectedId !== undefined ? String(selectedId ?? '') : selectEl.value;
        selectEl.innerHTML = '<option value="">Selecciona una finca</option>';
        fincasData.forEach(finca => {
            const option = document.createElement('option');
            option.value = String(finca.id);
            option.textContent = finca.nombre;
            selectEl.appendChild(option);
        });
        if (previous) {
            selectEl.value = String(previous);
        }
    };

    const populateFincaSelectors = () => {
        if (fincaDatalist) {
            fincaDatalist.innerHTML = '';
            fincasData.forEach(finca => {
                const option = document.createElement('option');
                option.value = finca.nombre;
                fincaDatalist.appendChild(option);
            });
        }

        if (filterFinca) {
            filterFinca.innerHTML = '<option value="">Todas</option>';
            fincasData.forEach(finca => {
                const option = document.createElement('option');
                option.value = finca.nombre;
                option.textContent = finca.nombre;
                filterFinca.appendChild(option);
            });
        }

        fillFincaSelect(workerFincaSelect);
        fillFincaSelect(editWorkerFincaSelect);
    };

    const renderWorkersTable = () => {
        if (!workersTableBody) return;
        workersTableBody.innerHTML = '';

        if (!workersData.length) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="text-center text-muted">Sin trabajadores registrados.</td>';
            workersTableBody.appendChild(row);
            return;
        }

        workersData.forEach(worker => {
            const row = document.createElement('tr');
            row.dataset.id = worker.id;
            row.innerHTML = `
                <td>
                    <span class="fw-semibold">${worker.nombre}</span><br>
                    <small class="text-muted">${worker.especialidad || ''}</small>
                </td>
                <td>${worker.documento}</td>
                <td class="text-capitalize">${worker.rol}</td>
                <td>${worker.finca_nombre ?? '-'}</td>
                <td>${worker.inicio_actividades || '-'}
                </td>
                <td>
                    <button class="btn btn-sm btn-link text-primary" data-action="edit-worker" data-id="${worker.id}"><i class="bi bi-pencil-square"></i></button>
                    <button class="btn btn-sm btn-link text-danger" data-action="delete-worker" data-id="${worker.id}" data-name="${worker.nombre}"><i class="bi bi-trash"></i></button>
                </td>`;
            workersTableBody.appendChild(row);
        });
    };

    const renderFincasTable = () => {
        if (!farmsTableBody) return;
        farmsTableBody.innerHTML = '';

        if (!fincasData.length) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="5" class="text-center text-muted">Sin fincas registradas.</td>';
            farmsTableBody.appendChild(row);
            return;
        }

        fincasData.forEach(finca => {
            const row = document.createElement('tr');
            row.dataset.id = finca.id;
            row.innerHTML = `
                <td>${finca.nombre}</td>
                <td><a href="${finca.link_ubicacion}" target="_blank" rel="noopener">Ver ubicación</a></td>
                <td>${finca.tarea_asignada || '-'}</td>
                <td>${finca.observacion || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-link text-success" data-action="edit-farm" data-id="${finca.id}"><i class="bi bi-pencil-square"></i></button>
                    <button class="btn btn-sm btn-link text-danger" data-action="delete-farm" data-id="${finca.id}" data-name="${finca.nombre}"><i class="bi bi-trash"></i></button>
                </td>`;
            farmsTableBody.appendChild(row);
        });
    };

    const updateStats = () => {
        document.getElementById('statTrabajadores').textContent = workersData.length;
        document.getElementById('statFincas').textContent = fincasData.length;
        const today = new Date().toISOString().slice(0, 10);
        const todayRecords = attendanceData.filter(item => item.fecha === today);
        document.getElementById('statAsistenciaHoy').textContent = todayRecords.length;
        const presentes = todayRecords.filter(item => item.estado === 'Presente').length;
        document.getElementById('statAsistenciaPct').textContent = todayRecords.length ? `${Math.round((presentes / todayRecords.length) * 100)}% presentes` : 'Sin registros.';
        const ausencias = attendanceData.filter(item => item.estado === 'Ausente').length;
        document.getElementById('statAusencias').textContent = ausencias;
    };

    const renderTable = data => {
        attendanceTableBody.innerHTML = '';
        if (!data.length) {
            const noRow = document.createElement('tr');
            noRow.innerHTML = '<td colspan="7" class="text-center text-muted">Sin registros para los filtros seleccionados.</td>';
            attendanceTableBody.appendChild(noRow);
            return;
        }

        data.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.fecha}</td>
                <td>${item.trabajador}</td>
                <td>${item.finca}</td>
                <td>${item.horaEntrada}</td>
                <td>${item.horaSalida}</td>
                <td>${item.horas}</td>
                <td><span class="badge badge-status ${getStatusClass(item.estado)}">${item.estado}</span></td>`;
            attendanceTableBody.appendChild(row);
        });
    };

    const getStatusClass = status => {
        if (status === 'Presente') return 'bg-success-subtle text-success';
        if (status === 'Ausente') return 'bg-danger-subtle text-danger';
        return 'bg-warning-subtle text-warning';
    };

    const applyFilters = () => {
        const selectedView = viewRange.value;
        const startValue = startDateInput.value;
        const endValue = endDateInput.value;
        const fincaValue = filterFinca.value;
        const today = new Date();
        let start = null;
        let end = null;

        if (selectedView === 'hoy') {
            start = end = today;
        } else if (selectedView === 'semana') {
            end = today;
            start = new Date();
            start.setDate(end.getDate() - 6);
        } else if (selectedView === 'mes') {
            end = today;
            start = new Date();
            start.setDate(end.getDate() - 29);
        }

        if (startValue) start = new Date(startValue);
        if (endValue) end = new Date(endValue);

        filteredData = attendanceData.filter(item => {
            const recordDate = new Date(item.fecha);
            const matchStart = start ? recordDate >= start : true;
            const matchEnd = end ? recordDate <= end : true;
            const matchFinca = fincaValue ? item.finca === fincaValue : true;
            return matchStart && matchEnd && matchFinca;
        });

        renderTable(filteredData);
    };

    const exportCsv = () => {
        if (!filteredData.length) {
            showToast('No hay datos para exportar.', 'danger');
            return;
        }
        const header = 'Fecha,Trabajador,Finca,Entrada,Salida,Horas,Estado\n';
        const rows = filteredData.map(item => [item.fecha, item.trabajador, item.finca, item.horaEntrada, item.horaSalida, item.horas, item.estado].join(','));
        const csvContent = header + rows.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `asistencia_${Date.now()}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    };

    const exportXlsx = () => {
        if (!filteredData.length) {
            showToast('No hay datos para exportar.', 'danger');
            return;
        }
        const ws = XLSX.utils.json_to_sheet(filteredData.map(item => ({
            Fecha: item.fecha,
            Trabajador: item.trabajador,
            Finca: item.finca,
            Entrada: item.horaEntrada,
            Salida: item.horaSalida,
            Horas: item.horas,
            Estado: item.estado,
        })));
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Asistencia');
        XLSX.writeFile(wb, `asistencia_${Date.now()}.xlsx`);
    };

    const openWorkerEditModal = worker => {
        if (!workerEditModal || !workerEditForm) return;
        workerEditForm.reset();
        workerEditForm.querySelector('#editWorkerId').value = worker.id;
        workerEditForm.querySelector('#editWorkerName').value = worker.nombre;
        workerEditForm.querySelector('#editWorkerDocument').value = worker.documento;
        editWorkerRoleField.value = worker.rol;
        fillFincaSelect(editWorkerFincaSelect, worker.finca_id);
        editWorkerFincaSelect.value = worker.finca_id ? String(worker.finca_id) : '';
        workerEditForm.querySelector('#editWorkerInicio').value = worker.inicio_actividades || '';
        workerEditForm.querySelector('#editWorkerObservaciones').value = worker.observaciones || '';
        editWorkerEspecialidadInput.value = worker.especialidad || '';
        setFincaFieldState(worker.rol, editWorkerFincaWrapper, editWorkerFincaSelect);
        setEspecialidadValue(worker.rol, editWorkerEspecialidadInput, worker.especialidad || '');
        workerEditModal.show();
    };

    const openFarmEditModal = finca => {
        if (!farmEditModal || !farmEditForm) return;
        farmEditForm.reset();
        farmEditForm.querySelector('#editFarmId').value = finca.id;
        farmEditForm.querySelector('#editFarmNombre').value = finca.nombre;
        farmEditForm.querySelector('#editFarmLink').value = finca.link_ubicacion;
        farmEditForm.querySelector('#editFarmDescripcion').value = finca.descripcion || '';
        farmEditForm.querySelector('#editFarmTarea').value = finca.tarea_asignada || '';
        farmEditForm.querySelector('#editFarmObservacion').value = finca.observacion || '';
        farmEditModal.show();
    };

    const openDeleteModal = (type, entity) => {
        if (!confirmDeleteModal || !confirmDeleteBtn || !confirmDeleteBody) return;
        deleteContext = { type, id: entity.id };
        confirmDeleteBody.textContent = `¿Eliminar ${type === 'worker' ? 'al trabajador' : 'la finca'} "${entity.nombre}"?`;
        confirmDeleteModal.show();
    };

    const syncWorkersFincaName = finca => {
        if (!finca) return;
        workersData = workersData.map(worker => {
            if (worker.finca_id === finca.id) {
                return { ...worker, finca_nombre: finca.nombre };
            }
            return worker;
        });
    };

    const clearWorkersFincaReference = fincaId => {
        workersData = workersData.map(worker => {
            if (worker.finca_id === fincaId) {
                return { ...worker, finca_id: null, finca_nombre: null };
            }
            return worker;
        });
    };

    const handleWorkerRoleChange = () => {
        if (!workerRoleField) return;
        const role = workerRoleField.value;
        setFincaFieldState(role, workerFincaWrapper, workerFincaSelect);
        setEspecialidadValue(role, workerEspecialidadInput);
    };

    if (workerRoleField) {
        workerRoleField.addEventListener('change', handleWorkerRoleChange);
    }

    if (editWorkerRoleField) {
        editWorkerRoleField.addEventListener('change', () => {
            const role = editWorkerRoleField.value;
            setFincaFieldState(role, editWorkerFincaWrapper, editWorkerFincaSelect);
            setEspecialidadValue(role, editWorkerEspecialidadInput, editWorkerEspecialidadInput.value);
        });
    }

    if (workerForm) {
        workerForm.addEventListener('submit', async event => {
            event.preventDefault();
            const submitBtn = workerForm.querySelector('[type="submit"]');
            toggleButtonState(submitBtn, true, 'Guardando...');

            try {
                const payload = await sendForm(workerForm.action, new FormData(workerForm));
                if (payload.trabajador) {
                    workersData.push(normalizeWorker(payload.trabajador));
                    renderWorkersTable();
                    updateStats();
                }
                workerForm.reset();
                handleWorkerRoleChange();
                if (workerSuccessModal) {
                    if (workerSuccessModalBody && payload.message) {
                        workerSuccessModalBody.textContent = payload.message;
                    }
                    workerSuccessModal.show();
                } else {
                    showToast('Trabajador guardado correctamente.', 'success');
                }
            } catch (error) {
                showToast(error.message || 'Ocurrió un error al guardar el trabajador.', 'danger');
            } finally {
                toggleButtonState(submitBtn, false);
            }
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
                    workersData = workersData.map(worker => (worker.id === updated.id ? updated : worker));
                    renderWorkersTable();
                    updateStats();
                }
                workerEditModal?.hide();
                showToast(payload.message || 'Trabajador actualizado correctamente.', 'success');
            } catch (error) {
                showToast(error.message || 'Ocurrió un error al actualizar el trabajador.', 'danger');
            } finally {
                toggleButtonState(submitBtn, false);
            }
        });
    }

    if (farmForm) {
        farmForm.addEventListener('submit', async event => {
            event.preventDefault();
            const submitBtn = farmForm.querySelector('[type="submit"]');
            toggleButtonState(submitBtn, true, 'Guardando...');

            try {
                const payload = await sendForm(farmForm.action, new FormData(farmForm));
                if (payload.finca) {
                    fincasData.push(normalizeFinca(payload.finca));
                    renderFincasTable();
                    populateFincaSelectors();
                    updateStats();
                }
                farmForm.reset();
                if (farmSuccessModal) {
                    if (farmSuccessModalBody && payload.message) {
                        farmSuccessModalBody.textContent = payload.message;
                    }
                    farmSuccessModal.show();
                } else {
                    showToast('Finca guardada correctamente.', 'success');
                }
            } catch (error) {
                showToast(error.message || 'Ocurrió un error al guardar la finca.', 'danger');
            } finally {
                toggleButtonState(submitBtn, false);
            }
        });
    }

    if (farmEditForm) {
        farmEditForm.addEventListener('submit', async event => {
            event.preventDefault();
            const submitBtn = farmEditForm.querySelector('[type="submit"]');
            toggleButtonState(submitBtn, true, 'Guardando...');

            try {
                const payload = await sendForm(farmEditForm.action, new FormData(farmEditForm));
                if (payload.finca) {
                    const updated = normalizeFinca(payload.finca);
                    fincasData = fincasData.map(finca => (finca.id === updated.id ? updated : finca));
                    syncWorkersFincaName(updated);
                    renderFincasTable();
                    renderWorkersTable();
                    populateFincaSelectors();
                    updateStats();
                }
                farmEditModal?.hide();
                showToast(payload.message || 'Finca actualizada correctamente.', 'success');
            } catch (error) {
                showToast(error.message || 'Ocurrió un error al actualizar la finca.', 'danger');
            } finally {
                toggleButtonState(submitBtn, false);
            }
        });
    }

    if (workersTableBody) {
        workersTableBody.addEventListener('click', event => {
            const actionBtn = event.target.closest('[data-action]');
            if (!actionBtn) return;
            const workerId = Number(actionBtn.dataset.id);
            const worker = workersData.find(item => item.id === workerId);
            if (!worker) return;

            if (actionBtn.dataset.action === 'edit-worker') {
                openWorkerEditModal(worker);
            } else if (actionBtn.dataset.action === 'delete-worker') {
                openDeleteModal('worker', worker);
            }
        });
    }

    if (farmsTableBody) {
        farmsTableBody.addEventListener('click', event => {
            const actionBtn = event.target.closest('[data-action]');
            if (!actionBtn) return;
            const fincaId = Number(actionBtn.dataset.id);
            const finca = fincasData.find(item => item.id === fincaId);
            if (!finca) return;

            if (actionBtn.dataset.action === 'edit-farm') {
                openFarmEditModal(finca);
            } else if (actionBtn.dataset.action === 'delete-farm') {
                openDeleteModal('farm', finca);
            }
        });
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async () => {
            if (!deleteContext) return;
            const formData = new FormData();
            formData.append('id', String(deleteContext.id));
            const url = deleteContext.type === 'worker' ? 'eliminar_trabajador.php' : 'eliminar_finca.php';

            toggleButtonState(confirmDeleteBtn, true, 'Eliminando...');
            try {
                const payload = await sendForm(url, formData);
                if (deleteContext.type === 'worker') {
                    workersData = workersData.filter(worker => worker.id !== deleteContext.id);
                    renderWorkersTable();
                } else {
                    fincasData = fincasData.filter(finca => finca.id !== deleteContext.id);
                    clearWorkersFincaReference(deleteContext.id);
                    renderFincasTable();
                    renderWorkersTable();
                    populateFincaSelectors();
                }
                updateStats();
                confirmDeleteModal?.hide();
                showToast(payload.message || 'Registro eliminado.', 'success');
                deleteContext = null;
            } catch (error) {
                showToast(error.message || 'No se pudo eliminar el registro.', 'danger');
            } finally {
                toggleButtonState(confirmDeleteBtn, false);
            }
        });
    }

    viewRange.addEventListener('change', () => {
        if (viewRange.value !== 'personalizado') {
            startDateInput.value = '';
            endDateInput.value = '';
        }
        applyFilters();
    });

    [startDateInput, endDateInput, filterFinca].forEach(input => input.addEventListener('change', applyFilters));

    document.getElementById('btnExportCsv').addEventListener('click', event => {
        event.preventDefault();
        exportCsv();
    });

    document.getElementById('btnExportXlsx').addEventListener('click', event => {
        event.preventDefault();
        exportXlsx();
    });

    document.getElementById('btnPrint').addEventListener('click', () => window.print());

    populateFincaSelectors();
    renderWorkersTable();
    renderFincasTable();
    updateStats();
    renderTable(filteredData);
    handleWorkerRoleChange();
    applyFilters();
});
