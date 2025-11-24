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

    const normalizePeon = peon => {
        const nombre = peon.nombre || '';
        const apellido = peon.apellido || '';

        return {
            id: Number(peon.id) || 0,
            nombre,
            apellido,
            fullName: `${nombre} ${apellido}`.trim() || 'Sin nombre',
            dni: peon.dni || '',
            telefono: peon.telefono || '',
            fecha_ingreso: peon.fecha_ingreso || '',
            estado: peon.estado || 'activo',
            cuadrilla_id: peon.cuadrilla_id !== null && peon.cuadrilla_id !== undefined && peon.cuadrilla_id !== ''
                ? Number(peon.cuadrilla_id)
                : null,
            cuadrilla_nombre: peon.cuadrilla_nombre || null,
        };
    };

    let fincasData = Array.isArray(window.__FincasData) && window.__FincasData.length ? window.__FincasData.map(normalizeFinca) : fallbackFincas.map(normalizeFinca);
    let workersData = Array.isArray(window.__PeonesData) ? window.__PeonesData.map(normalizePeon) : [];

    const normalizeAttendance = r => {
        const nombre = (r.nombre || '').trim();
        const apellido = (r.apellido || '').trim();
        const fullName = `${nombre} ${apellido}`.trim();
        const presente = Number(r.presente) === 1;
        return {
            id: Number(r.id) || 0,
            fecha: r.fecha || '',
            trabajador: fullName || 'Sin nombre',
            dni: r.dni || '',
            finca: r.finca_nombre || 'Sin finca',
            finca_id: Number(r.finca_id) || 0,
            cuadrillero: r.cuadrillero_nombre || 'Sin cuadrillero',
            cuadrillero_id: r.cuadrillero_id ? Number(r.cuadrillero_id) : null,
            presente,
            estado: presente ? 'Presente' : 'Ausente',
            horaEntrada: '-', // Placeholder hasta que exista en esquema
            horaSalida: '-',
            horas: '-',
        };
    };

    const rawAttendance = Array.isArray(window.__AttendanceData) ? window.__AttendanceData : [];
    let attendanceData = rawAttendance.map(normalizeAttendance);

    const toastEl = document.getElementById('actionToast');
    if (!toastEl) {
        return;
    }

    const toast = new bootstrap.Toast(toastEl);
    const toastBody = document.getElementById('toastBody');
    const attendanceTableBody = document.querySelector('#attendanceTable tbody');
    const filterCuadrillero = document.getElementById('filterCuadrillero');
    const filterSearch = document.getElementById('filterSearch');
    const viewRange = document.getElementById('viewRange');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const filterFinca = document.getElementById('filterFinca');
    const workerForm = document.getElementById('workerForm');
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
    const confirmDeleteModalEl = document.getElementById('confirmDeleteModal');
    const confirmDeleteModal = confirmDeleteModalEl ? new bootstrap.Modal(confirmDeleteModalEl) : null;
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const confirmDeleteBody = document.getElementById('confirmDeleteBody');

    let filteredData = [...attendanceData];
    let sortField = 'fecha';
    let sortDir = 'desc';
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
            credentials: 'same-origin',
        });

        const contentType = response.headers.get('Content-Type') || '';
        const expectsJson = contentType.includes('application/json');
        const payload = expectsJson ? await response.json() : null;

        if (!response.ok) {
            const errorMessage = payload?.message || 'La operación no pudo completarse.';
            throw new Error(errorMessage);
        }

        if (!payload || typeof payload.success === 'undefined') {
            throw new Error('No se pudo validar la respuesta del servidor. Verifica tu sesión e intenta nuevamente.');
        }

        if (payload.success === false) {
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

    const populateFincaSelectors = () => {
        const fincaSelect = filterFinca || document.getElementById('filterFinca');
        if (!fincaSelect) return;
        fincaSelect.innerHTML = '<option value="">Todas</option>';
        fincasData.forEach(finca => {
            const option = document.createElement('option');
            option.value = finca.nombre;
            option.textContent = finca.nombre;
            fincaSelect.appendChild(option);
        });
    };

    const renderWorkersTable = () => {
        if (!workersTableBody) return;
        workersTableBody.innerHTML = '';

        if (!workersData.length) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="text-center text-muted">Sin peones registrados.</td>';
            workersTableBody.appendChild(row);
            return;
        }

        workersData.forEach(worker => {
            const row = document.createElement('tr');
            row.dataset.id = worker.id;
            row.innerHTML = `
                <td>
                    <span class="fw-semibold">${worker.fullName}</span><br>
                    <small class="text-muted">${formatPeonState(worker.estado)}</small>
                </td>
                <td>${worker.dni || '-'}</td>
                <td>${worker.telefono || '-'}</td>
                <td>${worker.cuadrilla_nombre ?? 'Sin asignar'}</td>
                <td>${worker.fecha_ingreso || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-link text-primary" data-action="edit-worker" data-id="${worker.id}"><i class="bi bi-pencil-square"></i></button>
                    <button class="btn btn-sm btn-link text-danger" data-action="delete-worker" data-id="${worker.id}" data-name="${worker.fullName}"><i class="bi bi-trash"></i></button>
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
        const now = new Date();
        const today = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
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
                <td>${item.cuadrillero}</td>
                <td>${item.dni}</td>
                <td><span class="badge badge-status ${getStatusClass(item.estado)}">${item.estado}</span></td>`;
            attendanceTableBody.appendChild(row);
        });
    };

    const getStatusClass = status => {
        if (status === 'Presente') return 'bg-success-subtle text-success';
        if (status === 'Ausente') return 'bg-danger-subtle text-danger';
        return 'bg-warning-subtle text-warning';
    };

    const formatPeonState = state => {
        if (!state) return '';
        if (state.toLowerCase() === 'inactivo') return 'Inactivo';
        return 'Activo';
    };

    // Helpers de fecha (inicio/fin de día)
    const startOfDay = d => new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const endOfDay = d => new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23, 59, 59, 999);

    const applyFilters = () => {
        const selectedView = viewRange.value;
        const startValue = startDateInput.value;
        const endValue = endDateInput.value;
        const fincaValue = filterFinca.value;
        const cuadrilleroVal = filterCuadrillero ? filterCuadrillero.value : '';
        const searchVal = (filterSearch ? filterSearch.value : '').trim().toLowerCase();
        const today = new Date();
        let start = null;
        let end = null;

        if (selectedView === 'hoy') {
            start = startOfDay(today);
            end = endOfDay(today);
        } else if (selectedView === 'semana') {
            end = endOfDay(today);
            start = startOfDay(new Date(today.getFullYear(), today.getMonth(), today.getDate() - 6));
        } else if (selectedView === 'mes') {
            end = endOfDay(today);
            start = startOfDay(new Date(today.getFullYear(), today.getMonth(), today.getDate() - 29));
        }

        if (startValue) start = startOfDay(new Date(startValue));
        if (endValue) end = endOfDay(new Date(endValue));

        filteredData = attendanceData.filter(item => {
            const recordDate = new Date(item.fecha);
            const matchStart = start ? recordDate >= start : true;
            const matchEnd = end ? recordDate <= end : true;
            const matchFinca = fincaValue ? item.finca === fincaValue : true;
            const matchCuadrillero = cuadrilleroVal ? String(item.cuadrillero_id) === cuadrilleroVal : true;
            const matchSearch = searchVal ? (item.trabajador.toLowerCase().includes(searchVal) || item.dni.toLowerCase().includes(searchVal)) : true;
            return matchStart && matchEnd && matchFinca && matchCuadrillero && matchSearch;
        });
        sortFiltered();
        renderTable(filteredData);
    };

    const sortFiltered = () => {
        filteredData.sort((a,b)=>{
            let va=a[sortField]; let vb=b[sortField];
            // fechas
            if(sortField==='fecha'){ va=new Date(va); vb=new Date(vb);} else {
                va = (va||'').toString().toLowerCase();
                vb = (vb||'').toString().toLowerCase();
            }
            if(va<vb) return sortDir==='asc'? -1:1;
            if(va>vb) return sortDir==='asc'? 1:-1;
            return 0;
        });
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
        workerEditForm.querySelector('#editWorkerLastName').value = worker.apellido;
        workerEditForm.querySelector('#editWorkerDocument').value = worker.dni;
        const phoneInput = workerEditForm.querySelector('#editWorkerPhone');
        if (phoneInput) phoneInput.value = worker.telefono || '';
        workerEditForm.querySelector('#editWorkerInicio').value = worker.fecha_ingreso || '';
        workerEditForm.querySelector('#editWorkerStatus').value = (worker.estado || 'activo').toLowerCase();
        const cuadrillaSelect = workerEditForm.querySelector('#editWorkerCuadrilla');
        if (cuadrillaSelect) {
            cuadrillaSelect.value = worker.cuadrilla_id ? String(worker.cuadrilla_id) : '';
        }
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
        const label = type === 'worker' ? 'al peón' : 'la finca';
        const entityName = entity.fullName || entity.nombre;
        confirmDeleteBody.textContent = `¿Eliminar ${label} "${entityName}"?`;
        confirmDeleteModal.show();
    };


    if (workerForm) {
        workerForm.addEventListener('submit', async event => {
            event.preventDefault();
            const submitBtn = workerForm.querySelector('[type="submit"]');
            toggleButtonState(submitBtn, true, 'Guardando...');

            try {
                const payload = await sendForm(workerForm.action, new FormData(workerForm));
                if (payload.peon) {
                    workersData.push(normalizePeon(payload.peon));
                    renderWorkersTable();
                    updateStats();
                }
                workerForm.reset();
                if (workerSuccessModal) {
                    if (workerSuccessModalBody && payload.message) {
                        workerSuccessModalBody.textContent = payload.message;
                    }
                    workerSuccessModal.show();
                } else {
                    showToast('Peón guardado correctamente.', 'success');
                }
            } catch (error) {
                showToast(error.message || 'Ocurrió un error al guardar el peón.', 'danger');
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
                if (payload.peon) {
                    const updated = normalizePeon(payload.peon);
                    workersData = workersData.map(worker => (worker.id === updated.id ? updated : worker));
                    renderWorkersTable();
                    updateStats();
                }
                workerEditModal?.hide();
                showToast(payload.message || 'Peón actualizado correctamente.', 'success');
            } catch (error) {
                showToast(error.message || 'Ocurrió un error al actualizar el peón.', 'danger');
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

    // Ordenamiento por click en cabeceras
    document.querySelectorAll('#attendanceTable thead th.sortable').forEach(th => {
        th.style.cursor='pointer';
        th.addEventListener('click', ()=>{
            const field = th.getAttribute('data-sort');
            if(field){
                if(sortField===field){ sortDir = sortDir==='asc' ? 'desc' : 'asc'; }
                else { sortField = field; sortDir = 'asc'; }
                sortFiltered();
                renderTable(filteredData);
            }
        });
    });

    if(filterCuadrillero){ filterCuadrillero.addEventListener('change', applyFilters); }
    if(filterSearch){ filterSearch.addEventListener('input', applyFilters); }

    populateFincaSelectors();
    renderWorkersTable();
    renderFincasTable();
    updateStats();
    sortFiltered();
    renderTable(filteredData);
    applyFilters();
});
