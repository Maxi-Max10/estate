document.addEventListener('DOMContentLoaded', () => {
  const toastEl = document.getElementById('workersToast');
  const toastBody = document.getElementById('workersToastBody');
  const toast = toastEl ? new bootstrap.Toast(toastEl) : null;

  const tableBody = document.querySelector('#fullWorkersTable tbody');
  const searchInput = document.getElementById('searchWorker');
  const statusFilter = document.getElementById('filterStatus');
  const cuadrillaFilter = document.getElementById('filterCuadrilla');
  const clearBtn = document.getElementById('clearWorkersFilters');
  const filteredCount = document.getElementById('workersFilteredCount');
  const downloadCsvBtn = document.getElementById('downloadWorkersCsv');

  const workerEditModalEl = document.getElementById('workerEditModal');
  const workerEditModal = workerEditModalEl ? new bootstrap.Modal(workerEditModalEl) : null;
  const workerEditForm = document.getElementById('workerEditForm');
  const confirmDeleteModalEl = document.getElementById('confirmDeleteModal');
  const confirmDeleteModal = confirmDeleteModalEl ? new bootstrap.Modal(confirmDeleteModalEl) : null;
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  const confirmDeleteBody = document.getElementById('confirmDeleteBody');

  const showToast = (message, variant = 'primary') => {
    if (!toastEl || !toastBody || !toast) return;
    toastEl.className = `toast align-items-center text-bg-${variant} border-0`;
    toastBody.textContent = message;
    toast.show();
  };

  const escapeHtml = value => {
    if (typeof value !== 'string') return value ?? '';
    return value
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

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
      cuadrilla_id: peon.cuadrilla_id !== null && peon.cuadrilla_id !== undefined && peon.cuadrilla_id !== '' ? Number(peon.cuadrilla_id) : null,
      cuadrilla_nombre: peon.cuadrilla_nombre || null,
    };
  };

  let workers = Array.isArray(window.__PeonesData) ? window.__PeonesData.map(normalizePeon) : [];
  let filteredWorkers = [...workers];
  let deleteContext = null;

  const renderRows = data => {
    tableBody.innerHTML = '';
    if (!data.length) {
      const row = document.createElement('tr');
      row.innerHTML = '<td colspan="7" class="text-center text-muted">No hay peones para los filtros seleccionados.</td>';
      tableBody.appendChild(row);
      return;
    }

    data.forEach(worker => {
      const row = document.createElement('tr');
      row.dataset.id = worker.id;
      row.innerHTML = `
        <td class="fw-semibold">${escapeHtml(worker.fullName)}</td>
        <td>${escapeHtml(worker.dni)}</td>
        <td>${worker.cuadrilla_nombre ?? 'Sin asignar'}</td>
        <td>${escapeHtml(worker.estado)}</td>
        <td>${worker.fecha_ingreso || '-'}</td>
        <td>${worker.telefono || '-'}</td>
        <td class="text-nowrap">
          <button class="btn btn-sm btn-link text-primary" data-action="edit-worker" data-id="${worker.id}" title="Editar"><i class="bi bi-pencil-square"></i></button>
          <button class="btn btn-sm btn-link text-danger" data-action="delete-worker" data-id="${worker.id}" data-name="${escapeHtml(worker.fullName)}" title="Eliminar"><i class="bi bi-trash"></i></button>
        </td>`;
      tableBody.appendChild(row);
    });
  };

  const filterWorkers = () => {
    const term = searchInput.value.trim().toLowerCase();
    const status = statusFilter.value;
    const cuadrilla = cuadrillaFilter.value;

    filteredWorkers = workers.filter(worker => {
      const matchesStatus = status ? worker.estado === status : true;
      const matchesCuadrilla = cuadrilla ? (cuadrilla === 'sin' ? !worker.cuadrilla_nombre : worker.cuadrilla_nombre === cuadrilla) : true;
      const haystack = `${worker.fullName} ${worker.dni} ${worker.telefono} ${worker.cuadrilla_nombre ?? ''}`.toLowerCase();
      const matchesTerm = term ? haystack.includes(term) : true;
      return matchesStatus && matchesCuadrilla && matchesTerm;
    });

    filteredWorkers.sort((a, b) => a.fullName.localeCompare(b.fullName, 'es', { sensitivity: 'base' }));
    filteredCount.textContent = filteredWorkers.length;
    renderRows(filteredWorkers);
    return filteredWorkers;
  };

  const resetFilters = () => {
    searchInput.value = '';
    statusFilter.value = '';
    cuadrillaFilter.value = '';
    filterWorkers();
  };

  const downloadCsv = rows => {
    if (!rows.length) {
      showToast('No hay datos para exportar.', 'danger');
      return;
    }
    const header = ['Nombre', 'DNI', 'Cuadrilla', 'Estado', 'Ingreso', 'Telefono'];
    const csvRows = [header.join(',')];
    rows.forEach(worker => {
      csvRows.push([
        worker.fullName,
        worker.dni || '-',
        worker.cuadrilla_nombre ?? 'Sin asignar',
        worker.estado || '-',
        worker.fecha_ingreso || '-',
        worker.telefono || '-',
      ].map(v => `"${(v || '').replace(/"/g, '""')}"`).join(','));
    });

    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `peones_${Date.now()}.csv`;
    link.click();
    URL.revokeObjectURL(url);
    showToast('Descarga generada correctamente.', 'primary');
  };

  const sendForm = async (url, formData) => {
    const response = await fetch(url, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    });
    const expectsJson = response.headers.get('Content-Type')?.includes('application/json');
    const payload = expectsJson ? await response.json() : {};
    if (!response.ok || payload.success === false) {
      throw new Error(payload.message || 'Error en la operación.');
    }
    return payload;
  };

  const toggleButtonState = (btn, loading, text = 'Procesando...') => {
    if (!btn) return;
    if (loading) {
      btn.dataset.originalText = btn.textContent;
      btn.textContent = text;
      btn.disabled = true;
    } else {
      btn.textContent = btn.dataset.originalText || btn.textContent;
      btn.disabled = false;
    }
  };

  const openEditModal = worker => {
    if (!workerEditForm || !workerEditModal) return;
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

  const openDeleteModal = worker => {
    if (!confirmDeleteModal || !confirmDeleteBody) return;
    deleteContext = worker;
    confirmDeleteBody.textContent = `¿Eliminar al peón "${worker.fullName}"?`;
    confirmDeleteModal.show();
  };

  searchInput.addEventListener('input', filterWorkers);
  statusFilter.addEventListener('change', filterWorkers);
  cuadrillaFilter.addEventListener('change', filterWorkers);
  clearBtn.addEventListener('click', resetFilters);
  downloadCsvBtn.addEventListener('click', () => downloadCsv(filteredWorkers));

  if (workerEditForm) {
    workerEditForm.addEventListener('submit', async e => {
      e.preventDefault();
      const submitBtn = workerEditForm.querySelector('[type="submit"]');
      toggleButtonState(submitBtn, true, 'Guardando...');
      try {
        const payload = await sendForm(workerEditForm.action, new FormData(workerEditForm));
        if (payload.peon) {
          const updated = normalizePeon(payload.peon);
          workers = workers.map(w => (w.id === updated.id ? updated : w));
          filterWorkers();
        }
        workerEditModal?.hide();
        showToast(payload.message || 'Peón actualizado.', 'success');
      } catch (err) {
        showToast(err.message || 'No se pudo actualizar.', 'danger');
      } finally {
        toggleButtonState(submitBtn, false);
      }
    });
  }

  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', async () => {
      if (!deleteContext) return;
      const formData = new FormData();
      formData.append('id', String(deleteContext.id));
      toggleButtonState(confirmDeleteBtn, true, 'Eliminando...');
      try {
        const payload = await sendForm('eliminar_trabajador.php', formData);
        workers = workers.filter(w => w.id !== deleteContext.id);
        filterWorkers();
        confirmDeleteModal?.hide();
        showToast(payload.message || 'Peón eliminado.', 'success');
        deleteContext = null;
      } catch (err) {
        showToast(err.message || 'No se pudo eliminar.', 'danger');
      } finally {
        toggleButtonState(confirmDeleteBtn, false);
      }
    });
  }

  if (tableBody) {
    tableBody.addEventListener('click', e => {
      const actionBtn = e.target.closest('[data-action]');
      if (!actionBtn) return;
      const workerId = Number(actionBtn.dataset.id);
      const worker = workers.find(w => w.id === workerId);
      if (!worker) return;
      if (actionBtn.dataset.action === 'edit-worker') openEditModal(worker);
      if (actionBtn.dataset.action === 'delete-worker') openDeleteModal(worker);
    });
  }

  filterWorkers();
});
