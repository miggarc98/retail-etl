<!-- resources/views/partials/_report.blade.php -->
<div id="reportContainer" class="mt-8 hidden">
    <div class="bg-white rounded-lg shadow-lg border-2 border-blue-400">
        <div class="bg-blue-600 text-white rounded-t-lg px-6 py-4 flex justify-between items-center">
            <h5 class="text-lg font-semibold flex items-center gap-2">
                📈 Reporte de Inteligencia de Negocio
            </h5>
            <button id="closeReportBtn"
                class="px-3 py-1 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm">
                ✕ Cerrar
            </button>
        </div>
        <div class="p-6" id="reportContent">
            <!-- ⚡ JavaScript inyecta los datos AQUÍ -->
            <!-- Solo los valores, no el HTML completo -->
        </div>
    </div>
</div>