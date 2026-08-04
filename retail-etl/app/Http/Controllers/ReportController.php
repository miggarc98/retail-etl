<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\GetReportSummaryRequest;

class ReportController extends Controller
{
    public function summary(GetReportSummaryRequest $request)
    {
        $importId = $request->input('import_id');

        $report = Cache::remember("import_report_{$importId}", now()->addDays(7), function () use ($importId) {
            // 1. Ingresos Totales[cite: 1]
            $totalRevenue = Sale::where('import_id', $importId)->sum('total');

            // 2. Top 5 Productos con mayor generación de ingresos[cite: 1]
            $topProducts = Sale::where('import_id', $importId)
                ->select('product_name', DB::raw('SUM(total) as revenue'))
                ->groupBy('product_name')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get();

            // 3. Distribución por Categoría[cite: 1]
            $revenueByCategory = Sale::where('import_id', $importId)
                ->select('category', DB::raw('SUM(total) as revenue'))
                ->groupBy('category')
                ->orderByDesc('revenue')
                ->get();

            // 4. Distribución Geográfica[cite: 1]
            $revenueByCountry = Sale::where('import_id', $importId)
                ->select('country', DB::raw('SUM(total) as revenue'))
                ->groupBy('country')
                ->orderByDesc('revenue')
                ->get();

            return [
                'total_revenue' => $totalRevenue,
                'top_products' => $topProducts,
                'revenue_by_category' => $revenueByCategory,
                'revenue_by_country' => $revenueByCountry,
            ];
        });

        return response()->json($report);
    }
}