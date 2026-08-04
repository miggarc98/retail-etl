<!-- resources/views/partials/_imports_table.blade.php -->
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Archivo</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">✅ Exitosos
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">❌ Errores
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">📅 Fecha</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">⚙️ Acciones
                </th>
            </tr>
        </thead>
        <tbody id="importsTableBody" class="bg-white divide-y divide-gray-200">
            <!-- ⚡ JavaScript inserta las filas AQUÍ -->
            <!-- Solo inyecta datos, NO genera HTML complejo -->
        </tbody>
    </table>
</div>