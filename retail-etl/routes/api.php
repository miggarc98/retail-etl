<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;

// Endpoints de Importación y CRUD[cite: 1]
Route::post('/imports', [ImportController::class, 'store']);
Route::get('/imports', [ImportController::class, 'index']);
Route::get('/imports/{import}/errors', [ImportController::class, 'errors']);
Route::delete('/imports/{import}', [ImportController::class, 'destroy']);

// Endpoint de Reporte BI[cite: 1]
Route::get('/reports/summary', [ReportController::class, 'summary']);