<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Import;
use App\Jobs\ProcessCsvImport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\StoreImportRequest;

class ImportController extends Controller
{
    public function store(StoreImportRequest $request)
    {
        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();

            Log::info('📤 Recibiendo archivo', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]);

            // Guardar el archivo usando la abstracción de Storage
            $path = $file->storeAs('imports', $fileName, 'local');

            // Verificar la existencia utilizando la fachada Storage
            if (!Storage::disk('local')->exists($path)) {
                // Fallback: verificar rutas alternativas usando Storage
                $pathsToCheck = [
                    'private/imports/' . $fileName,
                    'imports/' . $fileName,
                    'private/' . $fileName,
                    $fileName,
                ];
                $found = false;
                foreach ($pathsToCheck as $checkPath) {
                    if (Storage::disk('local')->exists($checkPath)) {
                        $path = $checkPath;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    throw new \Exception("El archivo no se guardó correctamente en el disco local");
                }
            }

            $import = Import::create([
                'file_name' => $file->getClientOriginalName(),
                'status' => 'pending',
            ]);

            Log::info('📦 Importación creada', [
                'import_id' => $import->id,
                'file_path' => $path
            ]);

            ProcessCsvImport::dispatch($import->id, $path);

            return response()->json([
                'message' => 'Archivo en cola para procesamiento masivo.',
                'import_id' => $import->id,
                'status' => $import->status,
                'file_path' => $path
            ], 202);

        } catch (\Exception $e) {
            Log::error('❌ Error en store:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // B. Listado: Consulta cronológica con estadísticas
    public function index()
    {
        $imports = Import::orderBy('created_at', 'desc')->get();
        return response()->json($imports);
    }

    // B. Detalle de Errores: Listado paginado de inconsistencias
    public function errors(Import $import)
    {
        return response()->json($import->errors()->paginate(15));
    }

    // B. Eliminación: Suprimir importación, ventas y errores
    public function destroy(Import $import)
    {
        Cache::forget("import_report_{$import->id}");
        $import->delete();

        return response()->json(['message' => 'Importación y registros asociados eliminados correctamente.']);
    }
}