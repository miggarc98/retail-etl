@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gray-100 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">

            <!-- Header con logo -->
            <div class="mb-8">
                <div class="flex items-center gap-3">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                            Sistema de Gestión de Ventas Masivas
                        </h1>
                        <p class="text-sm text-gray-600 mt-0.5">
                            ETL & Analytics • Procesamiento de archivos CSV
                        </p>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- FORMULARIO DE CARGA -->
            <!-- ========================================== -->
            <div class="bg-white rounded-xl shadow-lg mb-8 overflow-hidden border border-gray-100">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-3.5">
                    <h5 class="text-base font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Nueva Importación
                    </h5>
                </div>

                <div class="p-5 sm:p-6">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <input type="file"
                                    class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 hover:bg-white transition-all duration-200 file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                                    id="csvFile" accept=".csv" required>
                                <div class="mt-2 flex flex-wrap gap-4 text-xs text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        CSV
                                    </span>

                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Procesamiento en cola
                                    </span>

                                </div>
                            </div>

                            <button type="submit" id="btnUpload"
                                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-800 transition-all duration-200 flex items-center gap-2 shadow-md hover:shadow-blue-500/25 whitespace-nowrap flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                Cargar
                            </button>
                        </div>
                    </form>

                    <div id="uploadAlert" class="mt-4"></div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- DASHBOARD -->
            <!-- ========================================== -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                <div class="p-5 sm:p-6">
                    <!-- Header del Dashboard -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                        <h5 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                            <span class="text-xl">📋</span>
                            Historial de Ejecución
                        </h5>
                        <button id="refreshButton"
                            class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200 flex items-center gap-2 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            Actualizar
                        </button>
                    </div>

                    <!-- Resumen -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                            <div class="text-xs font-medium text-blue-800 uppercase tracking-wider">Total Importaciones
                            </div>
                            <div id="totalImports" class="text-2xl font-bold text-blue-600 mt-1">0</div>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                            <div class="text-xs font-medium text-green-800 uppercase tracking-wider">Registros Exitosos
                            </div>
                            <div id="totalSuccess" class="text-2xl font-bold text-green-600 mt-1">0</div>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                            <div class="text-xs font-medium text-red-800 uppercase tracking-wider">Total Errores</div>
                            <div id="totalErrors" class="text-2xl font-bold text-red-600 mt-1">0</div>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="overflow-x-auto -mx-5 sm:mx-0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ID</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Archivo</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Exitosos</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Errores</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="importsTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- JS carga aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- REPORTE BI -->
            <!-- ========================================== -->
            <div id="reportContainer" class="mt-8 hidden">
                <div class="bg-white rounded-xl shadow-lg border-2 border-blue-400 overflow-hidden">
                    <div
                        class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-6 py-4 flex justify-between items-center">
                        <h5 class="text-base font-semibold flex items-center gap-2">
                            📈 Reporte de Inteligencia de Negocio
                        </h5>
                        <button id="closeReportBtn"
                            class="px-3 py-1 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors duration-200 text-sm">
                            ✕ Cerrar
                        </button>
                    </div>
                    <div class="p-6" id="reportContent">
                        <!-- JS carga aquí -->
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL DE ERRORES -->
    <!-- ========================================== -->
    <div id="errorModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[85vh] overflow-y-auto">
            <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 id="errorModalTitle" class="text-lg font-semibold text-gray-900">🔍 Errores de Importación</h3>
                <button id="closeErrorModalBtn" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div id="errorModalBody" class="p-6">
                <!-- JS carga aquí -->
            </div>
            <div class="sticky bottom-0 bg-gray-50 px-6 py-4 border-t border-gray-200">
                <button id="closeErrorModalFooterBtn"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
@endsection