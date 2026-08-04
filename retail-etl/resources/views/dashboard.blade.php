<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema ETL - Retail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/dashboard.js'])
</head>

<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4">📊 Sistema de Gestión de Ventas Masivas (ETL)</h2>

        <!-- Formulario de Carga -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">📤 Nueva Importación</h5>
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="file" class="form-control" id="csvFile" accept=".csv" required>
                        <button class="btn btn-primary" type="submit" id="btnUpload">📤 Cargar Archivo</button>
                    </div>
                </form>
                <div id="uploadAlert" class="mt-3"></div>
            </div>
        </div>

        <!-- Dashboard: Historial de Importaciones -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">📋 Historial de Ejecución</h5>

                <!-- Resumen rápido de estadísticas -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="alert alert-info text-center">
                            <strong>📦 Total Importaciones:</strong><br>
                            <span id="totalImports" class="h4">0</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success text-center">
                            <strong>✅ Registros Exitosos:</strong><br>
                            <span id="totalSuccess" class="h4">0</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-danger text-center">
                            <strong>❌ Total Errores:</strong><br>
                            <span id="totalErrors" class="h4">0</span>
                        </div>
                    </div>
                </div>

                <button class="btn btn-sm btn-outline-secondary mb-3" onclick="loadImports()">🔄 Actualizar
                    Historial</button>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Archivo</th>
                                <th>Estado</th>
                                <th>✅ Exitosos</th>
                                <th>❌ Errores</th>
                                <th>📅 Fecha</th>
                                <th>⚙️ Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="importsTableBody">
                            <!-- Los datos se cargarán aquí por JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Contenedor para el Reporte BI -->
        <div id="reportContainer" class="mt-4" style="display: none;">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📈 Reporte de Inteligencia de Negocio</h5>
                    <button class="btn btn-sm btn-light"
                        onclick="document.getElementById('reportContainer').style.display='none'">✕ Cerrar</button>
                </div>
                <div class="card-body" id="reportContent">
                    <!-- Los resultados del BI se inyectarán aquí -->
                </div>
            </div>
        </div>
    </div>


    <!-- Agregar Bootstrap JS para los modales -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>