<?php

namespace App\Http\Controllers;

use App\Models\QontakDeal;
use App\Models\QontakDealProduct;
use App\Services\MekariApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function pipelineReport(Request $request)
    {
        $stageChart = QontakDeal::query()
            ->select('crm_stage_name', DB::raw('COUNT(*) as total'))
            ->whereNotNull('crm_stage_name')
            ->groupBy('crm_stage_name')
            ->orderByDesc('total')
            ->get();

        $sourceChart = QontakDeal::query()
            ->join('qontak_sources', 'qontak_sources.id', '=', 'qontak_deals.qontak_source_id')
            ->select(
                'qontak_sources.crm_source_name',
                DB::raw('COUNT(qontak_deals.id) as total')
            )
            ->groupBy('qontak_sources.crm_source_name')
            ->orderByDesc('total')
            ->get();

        $topProducts = QontakDealProduct::query()
            ->select('product_name')
            ->selectRaw('SUM(total_price) as amount')
            ->whereNotNull('product_name')
            ->groupBy('product_name')
            ->orderByDesc('amount')
            ->limit(3)
            ->get();

        return $this->successResponse([
            'stage_chart'  => $stageChart,
            'source_chart' => $sourceChart,
            'top_products' => $topProducts,
        ], 'Pipeline Report fetched successfully');
    }

    public function testMekariApi(MekariApiService $mekari)
    {
        $method = 'GET';
        $path   = '/qontak/crm/tasks';
        $query  = '?per_page=25';
        $response = $mekari->request($method, $path, $query);
        return $response;
        // return response()->json($response);
    }
}
