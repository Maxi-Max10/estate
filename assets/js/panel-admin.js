document.addEventListener('DOMContentLoaded', () => {
    const trabajadoresDemo = [
        { nombre: 'Juan Pérez', documento: '30123456', rol: 'cuadrillero', finca: 'Finca Norte' },
        { nombre: 'María López', documento: '32111222', rol: 'cuadrillero', finca: 'Finca Central' },
        { nombre: 'Carlos Silva', documento: '30111999', rol: 'supervisor', finca: 'Finca Sur' },
        { nombre: 'Lucía Torres', documento: '28999888', rol: 'cuadrillero', finca: 'Finca Norte' }
    ];

    const fallbackFincas = [
        { id: 1, nombre: 'Finca Norte' },
        { id: 2, nombre: 'Finca Sur' },
        { id: 3, nombre: 'Finca Central' }
    ];

    const fincasData = Array.isArray(window.__FincasData) && window.__FincasData.length ? window.__FincasData : fallbackFincas;

    const attendanceData = [
        { fecha: '2025-11-22', trabajador: 'Juan Pérez', finca: 'Finca Norte', horaEntrada: '07:55', horaSalida: '17:10', horas: 9.25, estado: 'Presente' },
        { fecha: '2025-11-22', trabajador: 'María López', finca: 'Finca Central', horaEntrada: '08:05', horaSalida: '16:45', horas: 8.7, estado: 'Presente' },
        { fecha: '2025-11-22', trabajador: 'Carlos Silva', finca: 'Finca Sur', horaEntrada: '08:30', horaSalida: '15:00', horas: 6.5, estado: 'Licencia' },
        { fecha: '2025-11-21', trabajador: 'Lucía Torres', finca: 'Finca Norte', horaEntrada: '08:00', horaSalida: '17:00', horas: 9, estado: 'Presente' },
        { fecha: '2025-11-20', trabajador: 'Juan Pérez', finca: 'Finca Norte', horaEntrada: '08:10', horaSalida: '16:40', horas: 8.5, estado: 'Presente' },
        { fecha: '2025-11-19', trabajador: 'María López', finca: 'Finca Central', horaEntrada: '08:20', horaSalida: '16:20', horas: 8, estado: 'Ausente' }
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
    const farmForm = document.getElementById('farmForm');
    const farmSuccessModalEl = document.getElementById('farmSuccessModal');
    const farmSuccessModalBody = document.getElementById('farmSuccessModalBody');
    const farmSuccessModal = farmSuccessModalEl ? new bootstrap.Modal(farmSuccessModalEl) : null;
    const workerForm = document.getElementById('workerForm');
    const workerRoleField = document.getElementById('workerRole');
    const workerFincaWrapper = document.getElementById('workerFincaWrapper');
    const workerFincaSelect = document.getElementById('workerFincaSelect');
    const workerEspecialidadInput = document.getElementById('workerEspecialidad');
    const workerSuccessModalEl = document.getElementById('workerSuccessModal');
    const workerSuccessModalBody = document.getElementById('workerSuccessModalBody');
    const workerSuccessModal = workerSuccessModalEl ? new bootstrap.Modal(workerSuccessModalEl) : null;

    let filteredData = [...attendanceData];

    function initStats() {
        document.getElementById('statTrabajadores').textContent = trabajadoresDemo.length;
        document.getElementById('statFincas').textContent = fincasData.length;
        const today = new Date().toISOString().slice(0, 10);
        const todayRecords = attendanceData.filter(item => item.fecha === today);
        document.getElementById('statAsistenciaHoy').textContent = todayRecords.length;
        const presentes = todayRecords.filter(item => item.estado === 'Presente').length;
        document.getElementById('statAsistenciaPct').textContent = todayRecords.length ? `${Math.round((presentes / todayRecords.length) * 100)}% presentes` : 'Sin registros.';
        const ausencias = attendanceData.filter(item => item.estado === 'Ausente').length;
        document.getElementById('statAusencias').textContent = ausencias;
    }

    function populateFincasSelectors() {
        if (fincaDatalist) {
            fincaDatalist.innerHTML = '';
        }

        if (filterFinca) {
            filterFinca.innerHTML = '<option value="">Todas</option>';
        }

        fincasData.forEach(finca => {
            if (fincaDatalist) {
                const option = document.createElement('option');
                option.value = finca.nombre;
                fincaDatalist.appendChild(option);
            }

            if (filterFinca) {
                const selectOption = document.createElement('option');
                selectOption.value = finca.nombre;
                selectOption.textContent = finca.nombre;
                filterFinca.appendChild(selectOption);
            }
        });
    }

    function renderTable(data) {
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
    }

    function getStatusClass(status) {
        if (status === 'Presente') return 'bg-success-subtle text-success';
        if (status === 'Ausente') return 'bg-danger-subtle text-danger';
        return 'bg-warning-subtle text-warning';
    }

    function applyFilters() {
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
    }

    function showToast(message, variant = 'primary') {
        toastEl.className = `toast text-bg-${variant} align-items-center border-0`;
        toastBody.textContent = message;
        toast.show();
    }

    function exportCsv() {
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
    }

    function exportXlsx() {
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
    }

    function handleWorkerRoleChange() {
        if (!workerRoleField || !workerFincaWrapper || !workerFincaSelect) {
            return;
        }

        const role = workerRoleField.value;
        const needsFinca = role === 'cuadrillero';

        workerFincaWrapper.classList.toggle('d-none', !needsFinca);
        workerFincaSelect.disabled = !needsFinca;
        workerFincaSelect.required = needsFinca;

        if (!needsFinca) {
            workerFincaSelect.value = '';
        }

        if (workerEspecialidadInput) {
            workerEspecialidadInput.value = role === 'colaborador' ? 'cosechador' : '';
        }
    }

    if (workerRoleField) {
        workerRoleField.addEventListener('change', handleWorkerRoleChange);
    }

    if (workerForm) {
        workerForm.addEventListener('submit', async event => {
            event.preventDefault();
            const submitBtn = workerForm.querySelector('[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';
            }

            try {
                const response = await fetch(workerForm.action, {
                    method: 'POST',
                    body: new FormData(workerForm),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const expectsJson = response.headers.get('Content-Type')?.includes('application/json');
                const payload = expectsJson ? await response.json() : {};

                if (!response.ok || payload.success === false) {
                    throw new Error(payload.message || 'No se pudo guardar el trabajador.');
                }

                if (workerSuccessModalBody && payload.message) {
                    workerSuccessModalBody.textContent = payload.message;
                }

                workerForm.reset();
                handleWorkerRoleChange();
                if (workerSuccessModal) {
                    workerSuccessModal.show();
                } else {
                    showToast('Trabajador guardado correctamente.', 'success');
                }
            } catch (error) {
                showToast(error.message || 'Ocurrió un error al guardar el trabajador.', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }
        });
    }

    if (farmForm) {
        farmForm.addEventListener('submit', async event => {
            event.preventDefault();
            const submitBtn = farmForm.querySelector('[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.textContent : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';
            }

            try {
                const response = await fetch(farmForm.action, {
                    method: 'POST',
                    body: new FormData(farmForm),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const expectsJson = response.headers.get('Content-Type')?.includes('application/json');
                const payload = expectsJson ? await response.json() : {};

                if (!response.ok || payload.success === false) {
                    throw new Error(payload.message || 'No se pudo guardar la finca.');
                }

                if (farmSuccessModalBody && payload.message) {
                    farmSuccessModalBody.textContent = payload.message;
                }

                farmForm.reset();
                if (farmSuccessModal) {
                    farmSuccessModal.show();
                } else {
                    showToast('Finca guardada correctamente.', 'success');
                }
            } catch (error) {
                showToast(error.message || 'Ocurrió un error al guardar la finca.', 'danger');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
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

    initStats();
    populateFincasSelectors();
    handleWorkerRoleChange();
    applyFilters();
});
