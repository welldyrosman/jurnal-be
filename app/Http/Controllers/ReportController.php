<?php

namespace App\Http\Controllers;

use App\Models\QontakDeal;
use App\Models\QontakDealProduct;
use App\Services\Jurnal\GeneralLedgerService;
use App\Services\Jurnal\LabaRugiService;
use App\Services\MekariApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    protected GeneralLedgerService  $ledgerService;
    protected LabaRugiService $labaRugiService;
    public function __construct(GeneralLedgerService $ledgerService, LabaRugiService $labaRugiService)
    {
        $this->ledgerService = $ledgerService;
        $this->labaRugiService = $labaRugiService;
    }

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
    public function labaRugiReport(Request $request)
    {
        $startTime = microtime(true);

        $validator = Validator::make($request->all(), [
            'period_1' => 'required|yearmonth',
            'period_2' => 'required|yearmonth|yearmonth_after_or_equal:period_1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // =========================
        // PARSE PERIOD (YYYYMM)
        // =========================
        $p1 = Carbon::createFromFormat('Ym', $request->period_1);
        $p2 = Carbon::createFromFormat('Ym', $request->period_2);

        // =========================
        // YTD
        // =========================
        $ytd1Start = $p1->copy()->startOfYear()->toDateString();
        $ytd1End   = $p1->copy()->endOfMonth()->toDateString();

        $ytd2Start = $p2->copy()->startOfYear()->toDateString();
        $ytd2End   = $p2->copy()->endOfMonth()->toDateString();

        $Ytd_1 = $this->ledgerService->getSummary($ytd1Start, $ytd1End);
        $Ytd_2 = $this->ledgerService->getSummary($ytd2Start, $ytd2End);

        // =========================
        // CURRENT MONTH (CM)
        // =========================
        $cm1Start = $p1->copy()->startOfMonth()->toDateString();
        $cm1End   = $p1->copy()->endOfMonth()->toDateString();

        $cm2Start = $p2->copy()->startOfMonth()->toDateString();
        $cm2End   = $p2->copy()->endOfMonth()->toDateString();

        $cm_1 = $this->ledgerService->getSummary($cm1Start, $cm1End);
        $cm_2 = $this->ledgerService->getSummary($cm2Start, $cm2End);

        $executionTime = round(microtime(true) - $startTime, 3);

        return $this->successResponse([
            'ytd_1' => $Ytd_1,
            'ytd_2' => $Ytd_2,
            'cm_1'  => $cm_1,
            'cm_2'  => $cm_2,
            'execution_time_seconds' => $executionTime,
        ], 'Laba Rugi Report fetched successfully');
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
