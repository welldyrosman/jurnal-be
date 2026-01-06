<?php

namespace App\Http\Controllers;

use App\Models\JurnalInvoice;
use App\Models\QontakDeal;
use App\Services\Jurnal\BalanceSheetService;
use App\Services\Jurnal\GeneralLedgerService;
use App\Services\Qontak\QontakDashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected GeneralLedgerService $ledgerService;
    protected BalanceSheetService $balanceSheetService;
    protected QontakDashboardService $qontakDashboardService;

    public function __construct(
        GeneralLedgerService $ledgerService,
        BalanceSheetService $balanceSheetService,
        QontakDashboardService $qontakDashboardService
    ) {
        $this->ledgerService = $ledgerService;
        $this->balanceSheetService = $balanceSheetService;
        $this->qontakDashboardService = $qontakDashboardService;
    }

    public function indexbu(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date_format:d/m/Y',
                'end_date'   => 'required|date_format:d/m/Y|after_or_equal:start_date',
                'metric' => 'nullable|in:qty,amount',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            $startDateApi = Carbon::createFromFormat('d/m/Y', $request->start_date)->format('Y-m-d');
            $endDateApi = Carbon::createFromFormat('d/m/Y', $request->end_date)->format('Y-m-d');
            $metric = $request->metric ?? 'qty';
            $cacheKey     = "dashboards:{$startDateApi}:{$endDateApi}:{$metric}";
            $dashboardData = Cache::remember($cacheKey, 300, function () use ($startDateApi, $endDateApi, $metric) {

                $dealwonchart = $this->qontakDashboardService->getChartWonByActor($metric, $startDateApi, $endDateApi);
                $reportData = $this->balanceSheetService->getReport(['end_date' => $endDateApi]);
                $startDateInThisYear = Carbon::parse($endDateApi)->startOfYear()->format('Y-m-d');
                $endDateInThisYear = Carbon::parse($endDateApi)->endOfYear()->format('Y-m-d');
                $year = Carbon::parse($endDateApi)->endOfYear()->format('Y');
                $ledgerInYear = $this->ledgerService->getSummary($startDateInThisYear, $endDateInThisYear);
                $chartData = $this->ledgerService->calculateMonthlySales($ledgerInYear, $year);
                $lineChartInOUt = $this->ledgerService->lineInOutData($ledgerInYear, $year);
                $data = QontakDeal::select('crm_stage_name', DB::raw('COUNT(*) as total'))
                    ->groupBy('crm_stage_name')
                    ->orderBy('total', 'DESC')
                    ->get();
                return [
                    'ledger_summary' => $ledgerInYear,
                    'balance_sheet'  => $reportData,
                    'year'           => $endDateApi,
                    'chart_sales'    => $chartData,
                    'chart_in_out'   => $lineChartInOUt,
                    'deal_by_stage'  => $data,
                    'sumpipeline'     => QontakDeal::where('crm_stage_name', 'Won')->sum('amount'),
                    'dealwonchart'    => $dealwonchart,
                ];
            });
            return $this->successResponse($dashboardData, 'Laporan berhasil diambil');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data yang diberikan tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil laporan dari Jurnal', 500, ['detail' => $e->getMessage()]);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date_format:d/m/Y',
                'end_date'   => 'required|date_format:d/m/Y|after_or_equal:start_date',
                'metric'     => 'nullable|in:qty,amount',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $startDateApi = Carbon::createFromFormat('d/m/Y', $request->start_date)->format('Y-m-d');
            $endDateApi   = Carbon::createFromFormat('d/m/Y', $request->end_date)->format('Y-m-d');
            $metric       = $request->metric ?? 'qty';

            $cacheKey = "dashboards:{$startDateApi}:{$endDateApi}:{$metric}";
            $dashboardData = Cache::remember($cacheKey, 10, function () use ($startDateApi, $endDateApi, $metric) {

                $dealwonchart          = $this->qontakDashboardService->getChartWonByActor($metric, $startDateApi, $endDateApi);
                $reportData            = $this->balanceSheetService->getReport(['end_date' => $endDateApi]);
                $startDateInThisYear   = Carbon::parse($endDateApi)->startOfYear()->format('Y-m-d');
                $endDateInThisYear     = Carbon::parse($endDateApi)->endOfYear()->format('Y-m-d');
                $year                  = Carbon::parse($endDateApi)->endOfYear()->format('Y');
                $ledgerInYear          = $this->ledgerService->getSummary($startDateInThisYear, $endDateInThisYear);
                $chartData             = $this->ledgerService->calculateMonthlySales($ledgerInYear, $year);
                $lineChartInOUt        = $this->ledgerService->lineInOutData($ledgerInYear, $year);
                $data                  = QontakDeal::select('crm_stage_name', DB::raw('COUNT(*) as total'))
                    ->groupBy('crm_stage_name')
                    ->orderBy('total', 'DESC')
                    ->get();

                // === Tambahan 3 properti baru (logika sama dengan FE) ===
                $jasaincome = collect($ledgerInYear['accounts'] ?? [])
                    ->firstWhere('account_name', '(4100.0001) Pendapatan Jasa') ?? (object)[];

                $currentAssets = $reportData['current_assets']['accounts']['array'] ?? [];
                $asset1111     = collect($currentAssets)->firstWhere('number', '1111.0000');
                $balanceAsset  = $asset1111['data'][0]['balance_raw'] ?? 0;

                $piutang1142       = collect($currentAssets)->firstWhere('number', '1142.0000');
                $balancePiutang1142 = $piutang1142['data'][0]['balance_raw'] ?? 0;
                // ======================================================

                return [
                    'ledger_summary' => $ledgerInYear,
                    'balance_sheet'  => $reportData,
                    'year'           => $endDateApi,
                    'chart_sales'    => $chartData,
                    'chart_in_out'   => $lineChartInOUt,
                    'deal_by_stage'  => $data,
                    'sumpipeline'    => QontakDeal::where('crm_stage_name', 'Won')->sum('amount'),
                    'dealwonchart'   => $dealwonchart,

                    // Properti baru
                    'jasaincome'      => $jasaincome,
                    'balance_asset'   => $balanceAsset,
                    'balance_piutang' => $balancePiutang1142,
                ];
            });

            return $this->successResponse($dashboardData, 'Laporan berhasil diambil');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data yang diberikan tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil laporan dari Jurnal', 500, ['detail' => $e->getMessage()]);
        }
    }
}
