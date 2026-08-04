<!-- resources/views/partials/_summary.blade.php -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
        <div class="text-sm font-medium text-blue-800">📦 Total Importaciones</div>
        <div id="totalImports" class="text-2xl font-bold text-blue-600">0</div>
    </div>
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
        <div class="text-sm font-medium text-green-800">✅ Registros Exitosos</div>
        <div id="totalSuccess" class="text-2xl font-bold text-green-600">0</div>
    </div>
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
        <div class="text-sm font-medium text-red-800">❌ Total Errores</div>
        <div id="totalErrors" class="text-2xl font-bold text-red-600">0</div>
    </div>
</div>