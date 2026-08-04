
// Cargar el historial al iniciar la página
document.addEventListener('DOMContentLoaded', loadImports);

// Lógica de carga masiva
document.getElementById('uploadForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const fileInput = document.getElementById('csvFile');
    const alertBox = document.getElementById('uploadAlert');
    const btn = document.getElementById('btnUpload');

    if (fileInput.files.length === 0) return;

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    btn.disabled = true;
    btn.innerHTML = '⏳ Procesando...';

    try {
        const response = await fetch('/api/imports', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });

        const data = await response.json();

        if (response.status === 202) {
            alertBox.innerHTML = `<div class="alert alert-success">✅ Archivo en cola. ID: ${data.import_id}. Actualiza el historial en unos segundos.</div>`;
            fileInput.value = '';

            // Esperar unos segundos y cargar el historial
            setTimeout(() => {
                loadImports();
            }, 3000);
        } else {
            alertBox.innerHTML = `<div class="alert alert-danger">❌ Error al subir el archivo: ${data.message || 'Error desconocido'}</div>`;
        }
    } catch (error) {
        alertBox.innerHTML = `<div class="alert alert-danger">❌ Error de conexión: ${error.message}</div>`;
    }

    btn.disabled = false;
    btn.innerHTML = '📤 Cargar Archivo';
});

// Lógica para traer el CRUD
async function loadImports() {
    try {
        const response = await fetch('/api/imports');
        const imports = await response.json();

        const tbody = document.getElementById('importsTableBody');
        tbody.innerHTML = '';

        // Variables para el resumen
        let totalImports = 0;
        let totalSuccess = 0;
        let totalErrors = 0;

        if (imports.length === 0) {
            tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-muted">No hay importaciones registradas</td>
                        </tr>
                    `;
            document.getElementById('totalImports').textContent = '0';
            document.getElementById('totalSuccess').textContent = '0';
            document.getElementById('totalErrors').textContent = '0';
            return;
        }

        imports.forEach(imp => {
            let statusBadge = 'bg-secondary';
            let statusText = '⏳ Pendiente';

            if (imp.status === 'completed') {
                statusBadge = 'bg-success';
                statusText = '✅ Completado';
            } else if (imp.status === 'processing') {
                statusBadge = 'bg-warning text-dark';
                statusText = '⏳ Procesando';
            } else if (imp.status === 'failed') {
                statusBadge = 'bg-danger';
                statusText = '❌ Fallido';
            } else if (imp.status === 'pending') {
                statusBadge = 'bg-secondary';
                statusText = '⏳ Pendiente';
            } else if (imp.status === 'completed_with_errors') {
                statusBadge = 'bg-warning text-dark';
                statusText = '⚠️ Con Errores';
            }

            // ✅ CORRECCIÓN: Usar campos separados para exitosos y errores
            const successfulRecords = imp.successful_records ?? 0;
            const errorCount = imp.error_count ?? 0;
            const totalProcessed = imp.total_records ?? (successfulRecords + errorCount);

            // Acumular para el resumen
            totalImports++;
            totalSuccess += successfulRecords;
            totalErrors += errorCount;

            // Verificar si hay mensaje de error
            let errorTooltip = '';
            if (imp.error_message) {
                errorTooltip = ` title="Error: ${imp.error_message}" data-bs-toggle="tooltip"`;
            }

            tbody.innerHTML += `
                        <tr>
                            <td><strong>#${imp.id}</strong></td>
                            <td><span class="text-truncate d-inline-block" style="max-width: 150px;">${imp.file_name}</span></td>
                            <td><span class="badge ${statusBadge}"${errorTooltip}>${statusText}</span></td>
                            <td>
                                <span class="badge bg-success">${successfulRecords}</span>
                                <small class="text-muted">registros</small>
                            </td>
                            <td>
                                ${errorCount > 0
                    ? `<span class="badge bg-danger">${errorCount}</span> 
                                       <small class="text-muted">errores</small>
                                       <button class="btn btn-sm btn-outline-danger ms-1" onclick="viewErrors(${imp.id})">🔍 Ver</button>`
                    : `<span class="badge bg-secondary">0</span> <small class="text-muted">errores</small>`
                }
                            </td>
                            <td>${new Date(imp.created_at).toLocaleString()}</td>
                            <td>
                                <button class="btn btn-sm btn-info text-white" onclick="viewReport(${imp.id})" ${imp.status !== 'completed' && imp.status !== 'completed_with_errors' ? 'disabled' : ''}>
                                    📊 Reporte
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteImport(${imp.id})">
                                    🗑️ Eliminar
                                </button>
                            </td>
                        </tr>
                    `;
        });

        // Actualizar el resumen
        document.getElementById('totalImports').textContent = totalImports;
        document.getElementById('totalSuccess').textContent = totalSuccess;
        document.getElementById('totalErrors').textContent = totalErrors;

        // Inicializar tooltips si hay errores
        if (document.querySelector('[data-bs-toggle="tooltip"]')) {
            // Si tienes Bootstrap JS cargado
            // var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            // tooltipTriggerList.map(function (tooltipTriggerEl) {
            //     return new bootstrap.Tooltip(tooltipTriggerEl);
            // });
        }
    } catch (error) {
        console.error("Error cargando historial", error);
        document.getElementById('importsTableBody').innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger">❌ Error al cargar el historial: ${error.message}</td>
                    </tr>
                `;
    }
}

// Función para ver errores de una importación
async function viewErrors(id) {
    try {
        const response = await fetch(`/api/imports/${id}/errors`);
        const data = await response.json();

        let errorList = '';
        if (data.data && data.data.length > 0) {
            errorList = '<ul class="list-group">';
            data.data.forEach(err => {
                errorList += `
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-secondary me-2">Fila ${err.row_number}</span>
                                    ${err.error_message || err.reason || 'Error desconocido'}
                                </div>
                            </li>
                        `;
            });
            errorList += '</ul>';

            if (data.links) {
                errorList += `
                            <div class="mt-3">
                                <nav aria-label="Paginación de errores">
                                    <ul class="pagination pagination-sm">
                                        ${data.links.map(link => `
                                            <li class="page-item ${link.active ? 'active' : ''} ${link.url ? '' : 'disabled'}">
                                                <a class="page-link" href="#" onclick="loadErrorsPage('${link.url}')">${link.label}</a>
                                            </li>
                                        `).join('')}
                                    </ul>
                                </nav>
                            </div>
                        `;
            }
        } else {
            errorList = '<p class="text-muted">✅ No hay errores registrados para esta importación.</p>';
        }

        // Mostrar en un modal simple
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">🔍 Errores de Importación #${id}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                ${errorList}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                `;
        document.body.appendChild(modal);
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');

        // Cerrar al hacer click fuera
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.remove();
                document.body.classList.remove('modal-open');
            }
        });

        // Cerrar con el botón
        modal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', function () {
                modal.remove();
                document.body.classList.remove('modal-open');
            });
        });
    } catch (error) {
        alert('❌ Error al cargar los errores: ' + error.message);
    }
}

// Función para cargar página de errores (paginación)
async function loadErrorsPage(url) {
    // Implementar si es necesario
    console.log('Cargar página:', url);
}

// Lógica para la disposición clara de la información del reporte
async function viewReport(id) {
    try {
        const response = await fetch(`/api/reports/summary?import_id=${id}`);
        const data = await response.json();

        const container = document.getElementById('reportContainer');
        const content = document.getElementById('reportContent');

        // Formateador de moneda
        const formatter = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP' });

        // Top 5 Productos
        let topProductsHtml = '<ul class="list-group">';
        if (data.top_products && data.top_products.length > 0) {
            data.top_products.forEach(p => {
                topProductsHtml += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ${p.product_name || p.product_id || 'Producto'}
                                <span class="badge bg-primary rounded-pill">${formatter.format(p.revenue || p.total || 0)}</span>
                            </li>
                        `;
            });
        } else {
            topProductsHtml += '<li class="list-group-item text-muted">No hay productos registrados</li>';
        }
        topProductsHtml += '</ul>';

        // Categorías
        let categoriesHtml = '<ul class="list-group">';
        if (data.revenue_by_category && data.revenue_by_category.length > 0) {
            data.revenue_by_category.forEach(cat => {
                categoriesHtml += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ${cat.category}
                                <span class="badge bg-success rounded-pill">${formatter.format(cat.revenue || cat.total || 0)}</span>
                            </li>
                        `;
            });
        } else {
            categoriesHtml += '<li class="list-group-item text-muted">No hay categorías registradas</li>';
        }
        categoriesHtml += '</ul>';

        // Países
        let countriesHtml = '<ul class="list-group">';
        if (data.revenue_by_country && data.revenue_by_country.length > 0) {
            data.revenue_by_country.forEach(pais => {
                countriesHtml += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ${pais.country || pais.pais || 'País'}
                                <span class="badge bg-info rounded-pill">${formatter.format(pais.revenue || pais.total || 0)}</span>
                            </li>
                        `;
            });
        } else {
            countriesHtml += '<li class="list-group-item text-muted">No hay países registrados</li>';
        }
        countriesHtml += '</ul>';

        content.innerHTML = `
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="p-3 bg-light border rounded text-center">
                                <h3>💰 Ingresos Totales</h3>
                                <h2 class="text-success">${formatter.format(data.total_revenue || 0)}</h2>
                                <small class="text-muted">Importación #${id}</small>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">🏆 Top 5 Productos</h6>
                                </div>
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    ${topProductsHtml}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">📂 Distribución por Categoría</h6>
                                </div>
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    ${categoriesHtml}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">🌍 Distribución Geográfica</h6>
                                </div>
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    ${countriesHtml}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

        container.style.display = 'block';
        container.scrollIntoView({ behavior: 'smooth' });
    } catch (error) {
        alert("❌ Error cargando el reporte: " + error.message);
    }
}

// Lógica de eliminación
async function deleteImport(id) {
    if (!confirm('⚠️ ¿Seguro que deseas eliminar esta importación y todos sus datos vinculados?')) return;

    try {
        const response = await fetch(`/api/imports/${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json' }
        });

        if (response.ok) {
            alert('✅ Importación eliminada correctamente');
            loadImports();
        } else {
            const data = await response.json();
            alert('❌ Error al eliminar: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        alert('❌ Error de conexión: ' + error.message);
    }
}

// Auto-refresh cada 30 segundos (opcional)
setInterval(loadImports, 30000);

