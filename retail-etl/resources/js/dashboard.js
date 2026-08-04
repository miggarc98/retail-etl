// resources/js/dashboard.js
// ✅ TODO el HTML está en Blade
// ✅ Aquí SOLO hay lógica (fetch, eventos, manipulación de datos)

// ============================================
// UTILITIES
// ============================================

function showAlert(message, type = 'success') {
    const alertBox = document.getElementById('uploadAlert');
    const bgColor = type === 'success'
        ? 'bg-green-100 border-green-400 text-green-700'
        : 'bg-red-100 border-red-400 text-red-700';
    alertBox.innerHTML = `
        <div class="${bgColor} border px-4 py-3 rounded-lg">
            ${message}
        </div>
    `;
}

// ============================================
// CONSTANTES
// ============================================

const STATUS_MAP = {
    'completed': { badge: 'bg-green-500', text: '✅ Completado' },
    'processing': { badge: 'bg-yellow-500', text: '⏳ Procesando' },
    'failed': { badge: 'bg-red-500', text: '❌ Fallido' },
    'pending': { badge: 'bg-gray-500', text: '⏳ Pendiente' },
    'completed_with_errors': { badge: 'bg-yellow-500', text: '⚠️ Con Errores' }
};

const currencyFormatter = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP'
});

// ============================================
// FUNCIONES DE RENDERIZADO (Solo datos, no HTML complejo)
// ============================================

/**
 * ✅ SOLO genera la fila de la tabla (HTML mínimo)
 * El HTML base (estructura de tabla) está en Blade
 */
function createTableRow(imp) {
    const status = STATUS_MAP[imp.status] || STATUS_MAP.pending;
    const successfulRecords = imp.successful_records ?? 0;
    const errorCount = imp.error_count ?? 0;

    return `
        <tr class="hover:bg-gray-50 transition-colors duration-200">
            <td class="px-4 py-3 text-sm font-medium text-gray-900">#${imp.id}</td>
            <td class="px-4 py-3 text-sm text-gray-600 max-w-[150px] truncate">${imp.file_name}</td>
            <td class="px-4 py-3 text-sm">
                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${status.badge} text-white">
                    ${status.text}
                </span>
            </td>
            <td class="px-4 py-3 text-sm">
                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                    ${successfulRecords}
                </span>
                <span class="text-xs text-gray-500 ml-1">registros</span>
            </td>
            <td class="px-4 py-3 text-sm">
                ${errorCount > 0
            ? `<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">${errorCount}</span>
                       <span class="text-xs text-gray-500 ml-1">errores</span>
                       <button data-view-errors="${imp.id}" class="ml-2 px-2 py-1 text-xs text-red-600 hover:text-red-800 border border-red-300 rounded hover:bg-red-50 transition-colors duration-200">
                           🔍 Ver
                       </button>`
            : `<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600">0</span>
                       <span class="text-xs text-gray-500 ml-1">errores</span>`
        }
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">${new Date(imp.created_at).toLocaleString()}</td>
            <td class="px-4 py-3 text-sm">
                <button data-view-report="${imp.id}" 
                        class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition-colors duration-200 mr-1 ${imp.status !== 'completed' && imp.status !== 'completed_with_errors' ? 'opacity-50 cursor-not-allowed' : ''}"
                        ${imp.status !== 'completed' && imp.status !== 'completed_with_errors' ? 'disabled' : ''}>
                    📊 Reporte
                </button>
                <button data-delete-import="${imp.id}" 
                        class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 transition-colors duration-200">
                    🗑️ Eliminar
                </button>
            </td>
        </tr>
    `;
}

/**
 * ✅ Genera SOLO los valores del reporte (no el HTML completo)
 * La estructura HTML del reporte está en Blade (partials/_report.blade.php)
 */
function renderReportValues(data, id) {
    // Solo los valores que se insertarán en el HTML existente
    return {
        totalRevenue: currencyFormatter.format(data.total_revenue || 0),
        importId: id,
        topProducts: data.top_products || [],
        revenueByCategory: data.revenue_by_category || [],
        revenueByCountry: data.revenue_by_country || []
    };
}

// ============================================
// API FUNCTIONS
// ============================================

async function loadImports() {
    try {
        const response = await fetch('/api/imports');
        const imports = await response.json();

        const tbody = document.getElementById('importsTableBody');
        tbody.innerHTML = '';

        let totalImports = 0;
        let totalSuccess = 0;
        let totalErrors = 0;

        if (imports.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                        No hay importaciones registradas
                    </td>
                </tr>
            `;
        } else {
            imports.forEach(imp => {
                tbody.innerHTML += createTableRow(imp);
                totalImports++;
                totalSuccess += imp.successful_records ?? 0;
                totalErrors += imp.error_count ?? 0;
            });
        }

        document.getElementById('totalImports').textContent = totalImports;
        document.getElementById('totalSuccess').textContent = totalSuccess;
        document.getElementById('totalErrors').textContent = totalErrors;

        attachDynamicEvents();

    } catch (error) {
        console.error("Error cargando historial", error);
    }
}

async function viewErrors(id) {
    try {
        const response = await fetch(`/api/imports/${id}/errors`);
        const data = await response.json();

        const modalBody = document.getElementById('errorModalBody');
        const modalTitle = document.getElementById('errorModalTitle');
        modalTitle.textContent = `🔍 Errores de Importación #${id}`;

        // ✅ Solo inyecta los datos, NO el HTML completo
        // El HTML de la lista de errores está en Blade
        if (data.data && data.data.length > 0) {
            let html = '<div class="space-y-2">';
            data.data.forEach(err => {
                html += `
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs font-semibold rounded">
                            Fila ${err.row_number}
                        </span>
                        <span class="text-gray-600 text-sm">${err.error_message || err.reason || 'Error desconocido'}</span>
                    </div>
                `;
            });
            html += '</div>';
            modalBody.innerHTML = html;
        } else {
            modalBody.innerHTML = '<p class="text-gray-500 text-center py-4">✅ No hay errores registrados para esta importación.</p>';
        }

        document.getElementById('errorModal').classList.remove('hidden');
    } catch (error) {
        alert('❌ Error al cargar los errores: ' + error.message);
    }
}

async function viewReport(id) {
    try {
        const response = await fetch(`/api/reports/summary?import_id=${id}`);
        const data = await response.json();

        const container = document.getElementById('reportContainer');
        const content = document.getElementById('reportContent');

        // ✅ Obtener solo los valores
        const values = renderReportValues(data, id);

        // ✅ Construir el HTML con los valores
        let html = `
            <div class="grid grid-cols-1 gap-6">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                    <h3 class="text-lg font-medium text-gray-700">💰 Ingresos Totales</h3>
                    <p class="text-3xl font-bold text-green-600">${values.totalRevenue}</p>
                    <p class="text-sm text-gray-500 mt-1">Importación #${values.importId}</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">🏆 Top 5 Productos</h4>
                        <div class="space-y-2">`;

        if (values.topProducts.length > 0) {
            values.topProducts.forEach(p => {
                html += `
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="text-gray-700">${p.product_name || p.product_id}</span>
                        <span class="font-semibold text-blue-600">${currencyFormatter.format(p.revenue || 0)}</span>
                    </div>
                `;
            });
        } else {
            html += '<p class="text-gray-500">No hay productos registrados</p>';
        }

        html += `      </div>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">📂 Distribución por Categoría</h4>
                        <div class="space-y-2">`;

        if (values.revenueByCategory.length > 0) {
            values.revenueByCategory.forEach(cat => {
                html += `
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="text-gray-700">${cat.category}</span>
                        <span class="font-semibold text-green-600">${currencyFormatter.format(cat.revenue || 0)}</span>
                    </div>
                `;
            });
        } else {
            html += '<p class="text-gray-500">No hay categorías registradas</p>';
        }

        html += `      </div>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">🌍 Distribución Geográfica</h4>
                        <div class="space-y-2">`;

        if (values.revenueByCountry.length > 0) {
            values.revenueByCountry.forEach(pais => {
                html += `
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="text-gray-700">${pais.country || pais.pais}</span>
                        <span class="font-semibold text-purple-600">${currencyFormatter.format(pais.revenue || 0)}</span>
                    </div>
                `;
            });
        } else {
            html += '<p class="text-gray-500">No hay países registrados</p>';
        }

        html += `      </div>
                    </div>
                </div>
            </div>
        `;

        content.innerHTML = html;
        container.classList.remove('hidden');
        container.scrollIntoView({ behavior: 'smooth' });

    } catch (error) {
        alert("❌ Error cargando el reporte: " + error.message);
    }
}

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

// ============================================
// EVENTOS
// ============================================

function attachDynamicEvents() {
    document.querySelectorAll('[data-view-errors]').forEach(btn => {
        btn.onclick = () => viewErrors(parseInt(btn.dataset.viewErrors));
    });

    document.querySelectorAll('[data-view-report]').forEach(btn => {
        btn.onclick = () => viewReport(parseInt(btn.dataset.viewReport));
    });

    document.querySelectorAll('[data-delete-import]').forEach(btn => {
        btn.onclick = () => deleteImport(parseInt(btn.dataset.deleteImport));
    });
}

// ============================================
// INICIALIZACIÓN
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    loadImports();

    document.getElementById('uploadForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const fileInput = document.getElementById('csvFile');
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
                showAlert(`✅ Archivo en cola. ID: ${data.import_id}. Actualiza el historial en unos segundos.`, 'success');
                fileInput.value = '';
                setTimeout(() => loadImports(), 3000);
            } else {
                showAlert(`❌ Error al subir el archivo: ${data.message || 'Error desconocido'}`, 'error');
            }
        } catch (error) {
            showAlert(`❌ Error de conexión: ${error.message}`, 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '📤 Cargar Archivo';
    });

    document.getElementById('refreshButton').addEventListener('click', loadImports);

    document.getElementById('closeReportBtn').addEventListener('click', function () {
        document.getElementById('reportContainer').classList.add('hidden');
    });

    ['closeErrorModalBtn', 'closeErrorModalFooterBtn'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function () {
            document.getElementById('errorModal').classList.add('hidden');
        });
    });

    document.getElementById('errorModal')?.addEventListener('click', function (e) {
        if (e.target === this) this.classList.add('hidden');
    });

    setInterval(loadImports, 30000);
});