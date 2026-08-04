<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\ImportError;
use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $importId;
    public $filePath;
    public $tries = 3;
    public $timeout = 3600;

    public function __construct(int $importId, string $filePath)
    {
        $this->importId = $importId;
        $this->filePath = $filePath;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('===== JOB FALLIDO DEFINITIVAMENTE =====');
        Log::error('Import ID: ' . $this->importId);
        Log::error('File Path: ' . $this->filePath);
        Log::error('Mensaje de error: ' . $exception->getMessage());

        $import = Import::find($this->importId);
        if ($import) {
            $import->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage()
            ]);
        }
    }

    public function handle(): void
    {
        Log::info('🚀 INICIANDO PROCESAMIENTO DE IMPORTACIÓN', [
            'import_id' => $this->importId,
            'file_path' => $this->filePath,
            'job_attempt' => $this->attempts(),
            'job_id' => $this->job ? $this->job->getJobId() : 'manual-execution'
        ]);

        try {
            $import = Import::find($this->importId);
            if (!$import) {
                throw new \Exception("Importación con ID {$this->importId} no encontrada");
            }

            Log::info('📦 Importación encontrada', [
                'file_name' => $import->file_name,
                'current_status' => $import->status
            ]);

            $import->update(['status' => 'processing']);
            Log::info('📊 Estado actualizado a: processing');

            // Buscar la ruta relativa al storage que sea válida
            $storagePath = $this->findFileDiskPath($this->filePath);

            Log::info('📂 Verificando archivo en storage:', [
                'storage_path' => $storagePath,
                'exists' => Storage::disk('local')->exists($storagePath) ? '✅ Sí' : '❌ No'
            ]);

            if (!Storage::disk('local')->exists($storagePath)) {
                throw new \Exception("El archivo no existe en el almacenamiento: {$this->filePath}");
            }

            $fileSize = Storage::disk('local')->size($storagePath);
            Log::info('📄 Archivo encontrado', [
                'size_bytes' => $fileSize,
                'size_mb' => round($fileSize / 1024 / 1024, 2)
            ]);

            $result = $this->processFile($import, $storagePath);

            Log::info('✅ PROCESAMIENTO COMPLETADO EXITOSAMENTE', [
                'import_id' => $import->id,
                'total_records' => $result['total'],
                'successful' => $result['successful'],
                'errors' => $result['errors']
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ERROR EN EL PROCESAMIENTO', [
                'import_id' => $this->importId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);

            $import = Import::find($this->importId);
            if ($import) {
                $import->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Busca la ruta relativa del archivo en el storage local
     */
    private function findFileDiskPath(string $filePath): string
    {
        $pathsToTry = [
            'private/' . $filePath,
            $filePath,
            'private/imports/' . basename($filePath),
            'imports/' . basename($filePath),
        ];

        Log::info('🔍 Buscando archivo en las siguientes rutas del storage:', [
            'paths' => $pathsToTry
        ]);

        foreach ($pathsToTry as $path) {
            if (Storage::disk('local')->exists($path)) {
                Log::info('✅ Archivo encontrado en:', ['path' => $path]);
                return $path;
            }
        }

        return $filePath;
    }

    private function processFile($import, $storagePath): array
    {
        Log::info('📝 Iniciando lectura del archivo CSV');

        // Usar stream compatible con cualquier driver (s3, local, etc)
        $file = Storage::disk('local')->readStream($storagePath);
        if (!$file) {
            throw new \Exception("No se pudo abrir el archivo en el storage: {$storagePath}");
        }

        $header = fgetcsv($file, 0, ',', '"', '\\');
        Log::info('📋 Header del CSV: ' . json_encode($header));

        if (!$header || count($header) < 10) {
            fclose($file);
            throw new \Exception("El CSV no tiene un header válido. Columnas encontradas: " . count($header));
        }

        $salesBatch = [];
        $errorsBatch = [];
        $batchSize = 500;
        $rowNumber = 1;
        $totalProcessed = 0;
        $totalErrors = 0;

        while (($row = fgetcsv($file, 0, ',', '"', '\\')) !== false) {
            $rowNumber++;

            if ($rowNumber % 1000 === 0) {
                Log::info('📊 Progreso del procesamiento', [
                    'row_number' => $rowNumber,
                    'processed' => $totalProcessed,
                    'errors' => $totalErrors
                ]);
            }

            $row = array_pad($row, 11, null);

            $validation = $this->validateRow($row, $rowNumber);

            if (!$validation['valid']) {
                $errorsBatch[] = [
                    'import_id' => $import->id,
                    'row_number' => $rowNumber,
                    'error_message' => $validation['error'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $totalErrors++;

                if (count($errorsBatch) >= $batchSize) {
                    DB::transaction(function () use ($errorsBatch) {
                        ImportError::insert($errorsBatch);
                    });
                    Log::info('💾 Insertados errores en lote', ['count' => count($errorsBatch)]);
                    $errorsBatch = [];
                }
                continue;
            }

            $quantity = (float) $row[7];
            $unitPrice = (float) $row[8];
            $discount = (float) ($row[9] ?? 0);
            $totalAmount = $quantity * $unitPrice * (1 - $discount);

            $salesBatch[] = [
                'import_id' => $import->id,
                'order_id' => $row[0],
                'date' => $row[1],
                'customer_id' => $row[2],
                'customer_name' => $row[3],
                'product_id' => $row[4],
                'product_name' => $row[5],
                'category' => $row[6],
                'quantity' => (int) $row[7],
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'country' => $row[10] ?? 'Desconocido',
                'total' => $totalAmount,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $totalProcessed++;

            if (count($salesBatch) >= $batchSize) {
                DB::transaction(function () use ($salesBatch) {
                    Sale::insert($salesBatch);
                });
                Log::info('💾 Insertadas ventas en lote', ['count' => count($salesBatch)]);
                $salesBatch = [];
            }
        }

        fclose($file);

        if (!empty($salesBatch)) {
            DB::transaction(function () use ($salesBatch) {
                Sale::insert($salesBatch);
            });
            Log::info('💾 Insertadas ventas finales', ['count' => count($salesBatch)]);
        }
        if (!empty($errorsBatch)) {
            DB::transaction(function () use ($errorsBatch) {
                ImportError::insert($errorsBatch);
            });
            Log::info('💾 Insertados errores finales', ['count' => count($errorsBatch)]);
        }

        // Eliminar el archivo del almacenamiento
        Storage::disk('local')->delete($storagePath);
        Log::info('🗑️ Archivo eliminado: ' . $storagePath);

        $import->update([
            'status' => 'completed',
            'total_records' => $totalProcessed + $totalErrors,
            'successful_records' => $totalProcessed,
            'error_count' => $totalErrors,
            'completed_at' => now()
        ]);

        return [
            'total' => $totalProcessed + $totalErrors,
            'successful' => $totalProcessed,
            'errors' => $totalErrors
        ];
    }

    private function validateRow($row, $rowNumber): array
    {
        $requiredFields = ['order_id', 'date', 'customer_id', 'customer_name', 'product_id', 'product_name'];

        foreach ($requiredFields as $index => $field) {
            if (empty(trim($row[$index] ?? ''))) {
                return [
                    'valid' => false,
                    'error' => "Campo '{$field}' está vacío en la fila {$rowNumber}"
                ];
            }
        }

        if (empty($row[7]) || !is_numeric($row[7]) || (int) $row[7] <= 0) {
            return [
                'valid' => false,
                'error' => "Cantidad inválida: '{$row[7]}' en la fila {$rowNumber}"
            ];
        }

        if (empty($row[8]) || !is_numeric($row[8]) || (float) $row[8] < 0) {
            return [
                'valid' => false,
                'error' => "Precio unitario inválido: '{$row[8]}' en la fila {$rowNumber}"
            ];
        }

        if (!empty($row[9]) && (!is_numeric($row[9]) || (float) $row[9] < 0 || (float) $row[9] > 1)) {
            return [
                'valid' => false,
                'error' => "Descuento inválido: '{$row[9]}' en la fila {$rowNumber}"
            ];
        }

        $date = trim($row[1]);
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
            return [
                'valid' => false,
                'error' => "Fecha inválida: '{$date}' en la fila {$rowNumber}. Formato esperado: YYYY-MM-DD"
            ];
        }

        return ['valid' => true, 'error' => null];
    }
}