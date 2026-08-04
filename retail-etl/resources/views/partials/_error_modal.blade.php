<!-- resources/views/partials/_error_modal.blade.php -->
<div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">

        <!-- Header -->
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="errorModalTitle" class="text-lg font-semibold text-gray-900">
                🔍 Errores de Importación
            </h3>
            <button id="closeErrorModalBtn" class="text-gray-400 hover:text-gray-600">
                ✕
            </button>
        </div>

        <!-- Body -->
        <div id="errorModalBody" class="p-6">
            <!-- ⚡ JavaScript inyecta los errores AQUÍ -->
            <!-- Solo los datos, no el HTML completo -->
        </div>

        <!-- Footer -->
        <div class="sticky bottom-0 bg-gray-50 px-6 py-4 border-t border-gray-200">
            <button id="closeErrorModalFooterBtn"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors duration-200">
                Cerrar
            </button>
        </div>

    </div>
</div>