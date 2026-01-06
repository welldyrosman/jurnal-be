<?php

namespace App\Http\Controllers;

use App\Models\AccountGrouping;
use App\Models\JurnalAccount;
use App\Models\QontakDeal;
use App\Models\QontakDealProduct;
use App\Services\Jurnal\GeneralLedgerService;
use App\Services\Jurnal\LabaRugiService;
use App\Services\MekariApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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


    private function applyBalanceSideFilter(array $accounts): array
    {
        return collect($accounts)->map(function ($acc) {
            $account = $acc['account_name'] ?? '';

            if (preg_match('/^\(([^)]+)\)/', $account, $m)) {
                $code = $m[1]; // "1141.3000"
            }
            $jurnalAccount = JurnalAccount::where('number', $code ?? null)->first();
            // dd($jurnalAccount, $acc);
            $balanceSide = null;
            if ($jurnalAccount && $jurnalAccount->budget_grouping_id) {
                $grouping = AccountGrouping::find($jurnalAccount->budget_grouping_id);
                $balanceSide = $grouping?->balance_side;
            }

            if ($balanceSide === 'credit') {
                $acc['debit']  = 0;
            } elseif ($balanceSide === 'debit') {
                $acc['credit'] = 0;
            }

            $acc['balance_side'] = $balanceSide; // simpan untuk dipakai saat agregasi
            $acc['transactions'] = [];           // kosongkan transaksi

            return $acc;
        })->all();
    }

    private function getSideValue(array $acc): float
    {
        if (($acc['balance_side'] ?? null) === 'credit') {
            return (float) ($acc['credit'] ?? 0);
        }
        if (($acc['balance_side'] ?? null) === 'debit') {
            return (float) ($acc['debit'] ?? 0);
        }
        // fallback bila balance_side tidak ada: gunakan net (credit - debit) atau debit, sesuaikan kebutuhan
        return (float) (($acc['credit'] ?? 0) - ($acc['debit'] ?? 0));
    }

    private function pctChange(float $new, float $old): ?float
    {
        if ($old == 0.0) {
            return null; // atau 0, sesuai preferensi
        }
        return ($new - $old) / $old;
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

        $p1 = Carbon::createFromFormat('Ym', $request->period_1);
        $p2 = Carbon::createFromFormat('Ym', $request->period_2);

        // YTD ranges
        $ytd1Start = $p1->copy()->startOfYear()->toDateString();
        $ytd1End   = $p1->copy()->endOfMonth()->toDateString();
        $ytd2Start = $p2->copy()->startOfYear()->toDateString();
        $ytd2End   = $p2->copy()->endOfMonth()->toDateString();

        // CM ranges
        $cm1Start = $p1->copy()->startOfMonth()->toDateString();
        $cm1End   = $p1->copy()->endOfMonth()->toDateString();
        $cm2Start = $p2->copy()->startOfMonth()->toDateString();
        $cm2End   = $p2->copy()->endOfMonth()->toDateString();

        $cacheKey = "labarugi:{$ytd1Start}:{$ytd1End}:{$ytd2Start}:{$ytd2End}:{$cm1Start}:{$cm1End}:{$cm2Start}:{$cm2End}";
        $reportData = cache()->remember($cacheKey, 300, function () use ($ytd1Start, $ytd1End, $ytd2Start, $ytd2End, $cm1Start, $cm1End, $cm2Start, $cm2End) {
            $Ytd_1 = $this->applyBalanceSideFilter($this->ledgerService->getSummary($ytd1Start, $ytd1End)['accounts']);
            $Ytd_2 = $this->applyBalanceSideFilter($this->ledgerService->getSummary($ytd2Start, $ytd2End)['accounts']);
            $cm_1  = $this->applyBalanceSideFilter($this->ledgerService->getSummary($cm1Start, $cm1End)['accounts']);
            $cm_2  = $this->applyBalanceSideFilter($this->ledgerService->getSummary($cm2Start, $cm2End)['accounts']);
            return compact('Ytd_1', 'Ytd_2', 'cm_1', 'cm_2');
        });


        // Gabung per account_name
        $byAccount = [];

        $pushVal = function (array $src, string $field) use (&$byAccount) {
            foreach ($src as $acc) {
                $name = $acc['account_name'];
                $byAccount[$name]['account_name'] = $name;

                $byAccount[$name]['balance_side'] = $byAccount[$name]['balance_side'] ?? ($acc['balance_side'] ?? null);
                $byAccount[$name][$field] = $this->getSideValue($acc);
            }
        };

        $pushVal($reportData['Ytd_1'], 'y1');
        $pushVal($reportData['Ytd_2'], 'y2');
        $pushVal($reportData['cm_1'],  'cm1');
        $pushVal($reportData['cm_2'],  'cm2');

        // Hitung persentase
        foreach ($byAccount as &$acc) {
            $y1  = $acc['y1']  ?? 0.0;
            $y2  = $acc['y2']  ?? 0.0;
            $cm1 = $acc['cm1'] ?? 0.0;
            $cm2 = $acc['cm2'] ?? 0.0;

            $acc['penurunan_y']  = $this->pctChange($y2, $y1);
            $acc['penurunan_cm'] = $this->pctChange($cm2, $cm1);
        }
        unset($acc);

        $executionTime = round(microtime(true) - $startTime, 3);

        return $this->successResponse([
            'accounts' => array_values($byAccount), // list akun dengan kolom yang diminta
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
