<?php

namespace App\Http\Controllers;

use App\Models\JurnalInvoice;
use App\Models\JurnalSalesInvoice;
use App\Models\QontakDeal;
use App\Models\ViewPaymentHistory;
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
    public function indexQontak(Request $request): JsonResponse
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
                $startDateInThisYear   = Carbon::parse($endDateApi)->startOfYear()->format('Y-m-d');
                $endDateInThisYear     = Carbon::parse($endDateApi)->endOfYear()->format('Y-m-d');
                $year                  = Carbon::parse($endDateApi)->endOfYear()->format('Y');
                $data                  = QontakDeal::select('crm_stage_name', DB::raw('COUNT(*) as total'))
                    ->groupBy('crm_stage_name')
                    ->orderBy('total', 'DESC')
                    ->get();
                return [

                    'year'           => $endDateApi,
                    'deal_by_stage'  => $data,
                    'sumpipeline'    => QontakDeal::where('crm_stage_name', 'Won')->sum('amount'),
                    'dealwonchart'   => $dealwonchart
                ];
            });

            return $this->successResponse($dashboardData, 'Laporan berhasil diambil');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data yang diberikan tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil laporan dari Jurnal', 500, ['detail' => $e->getMessage()]);
        }
    }

    private function topPiutangCustomer()
    {
        $sumpayment = DB::table('view_payment_history')
            ->select('invoice_no', DB::raw('SUM(amount) AS total_bayar'))
            ->groupBy('invoice_no');

        $data = DB::table('jurnal_invoices as ji')
            ->join('jurnal_sales_invoices as jsi', 'ji.transaction_no', '=', 'jsi.transaction_no')
            ->leftJoinSub($sumpayment, 's', function ($join) {
                $join->on('ji.transaction_no', '=', 's.invoice_no');
            })
            ->leftJoin('jurnal_persons as jp', 'ji.person_id', '=', 'jp.id')
            ->where('ji.transaction_status_name', '!=', 'Lunas')
            ->groupBy('jp.id', 'jp.display_name')
            ->select(
                'jp.id as customer_id',
                'jp.display_name as customer_name',
                DB::raw('SUM(jsi.original_amount - COALESCE(s.total_bayar,0)) AS total_piutang'),
                // Menghitung selisih hari dari invoice tertua yang belum lunas
                DB::raw('DATEDIFF(NOW(), MIN(ji.due_date)) as lama_hari_terlama'),
                DB::raw('ROUND(AVG(DATEDIFF(NOW(), ji.due_date))) as rata_rata_hari')
            )
            ->orderByDesc('total_piutang')
            ->limit(5)
            ->get();

        return response()->json($data);
    }

    private function getAgingPiutang($startDateApi, $endDateApi)
    {
        $paymentSub = DB::query()
            ->select([
                'jrpr.transaction_no as invoice_no',
                'jrpr.amount',
            ])
            ->from('jurnal_receive_payment_records as jrpr')
            ->join('jurnal_receive_payments as jrp', 'jrpr.jurnal_receive_payment_id', '=', 'jrp.id')
            ->whereBetween('jrp.transaction_date', [$startDateApi, $endDateApi])

            ->unionAll(
                DB::query()
                    ->select([
                        'ji.transaction_no as invoice_no',
                        'jcm.original_amount as amount',
                    ])
                    ->from('jurnal_payments as jp')
                    ->join('jurnal_credit_memos as jcm', 'jp.transaction_no', '=', 'jcm.transaction_no')
                    ->leftJoin('jurnal_invoices as ji', 'jp.invoice_id', '=', 'ji.id')
                    ->whereBetween('jp.transaction_date', [$startDateApi, $endDateApi])
            );

        $sumPaymentSub = DB::query()
            ->fromSub($paymentSub, 'payment')
            ->select([
                'invoice_no',
                DB::raw('SUM(amount) as total_bayar'),
            ])
            ->groupBy('invoice_no');

        return DB::table('jurnal_invoices as ji')
            ->join('jurnal_sales_invoices as jsi', 'ji.transaction_no', '=', 'jsi.transaction_no')
            ->leftJoinSub(
                $sumPaymentSub,
                's',
                fn($join) =>
                $join->on('ji.transaction_no', '=', 's.invoice_no')
            )
            ->where('ji.transaction_status_name', '!=', 'Lunas')
            ->whereBetween('ji.transaction_date', [$startDateApi, $endDateApi])
            ->selectRaw("
            COALESCE(SUM(CASE WHEN DATEDIFF(?, ji.transaction_date) <= 30 THEN (jsi.original_amount - IFNULL(s.total_bayar,0)) ELSE 0 END)) AS aging_lt_30,
            COALESCE(SUM(CASE WHEN DATEDIFF(?, ji.transaction_date) BETWEEN 31 AND 60 THEN (jsi.original_amount - IFNULL(s.total_bayar,0)) ELSE 0 END)) AS aging_31_60,
            COALESCE(SUM(CASE WHEN DATEDIFF(?, ji.transaction_date) BETWEEN 61 AND 90 THEN (jsi.original_amount - IFNULL(s.total_bayar,0)) ELSE 0 END)) AS aging_61_90,
            COALESCE(SUM(CASE WHEN DATEDIFF(?, ji.transaction_date) BETWEEN 91 AND 120 THEN (jsi.original_amount - IFNULL(s.total_bayar,0)) ELSE 0 END)) AS aging_91_120,
            COALESCE(SUM(CASE WHEN DATEDIFF(?, ji.transaction_date) > 120 THEN (jsi.original_amount - IFNULL(s.total_bayar,0)) ELSE 0 END)) AS aging_gt_120,
            COALESCE(SUM(jsi.original_amount - IFNULL(s.total_bayar,0))) AS total_piutang
        ", array_fill(0, 5, $endDateApi))
            ->first();
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

                $reportData            = $this->balanceSheetService->getReport(['end_date' => $endDateApi]);
                $startDateInThisYear   = Carbon::parse($endDateApi)->startOfYear()->format('Y-m-d');
                $endDateInThisYear     = Carbon::parse($endDateApi)->endOfYear()->format('Y-m-d');
                $year                  = Carbon::parse($endDateApi)->endOfYear()->format('Y');
                $ledgerInYear          = $this->ledgerService->getSummary($startDateInThisYear, $endDateInThisYear);
                $chartData             = $this->ledgerService->calculateMonthlySales($ledgerInYear, $year);
                $lineChartInOUt        = $this->ledgerService->lineInOutData($ledgerInYear, $year);

                // === Tambahan 3 properti baru (logika sama dengan FE) ===
                $jasaincome = collect($ledgerInYear['accounts'] ?? [])
                    ->firstWhere('account_name', '(4100.0001) Pendapatan Jasa') ?? (object)[];

                $currentAssets = $reportData['current_assets']['accounts']['array'] ?? [];
                $asset1111     = collect($currentAssets)->firstWhere('number', '1111.0000');
                $balanceAsset  = $asset1111['data'][0]['balance_raw'] ?? 0;

                $piutang1142       = collect($currentAssets)->firstWhere('number', '1142.0000');
                $balancePiutang1142 = $piutang1142['data'][0]['balance_raw'] ?? 0;

                $totalPiutang = JurnalSalesInvoice::where('transaction_status_name', '!=', 'Lunas')->sum('balance_due');
                $totalQtyPiutang = JurnalSalesInvoice::where('transaction_status_name', '!=', 'Lunas')->count();

                $telatBayarAmount = JurnalSalesInvoice::whereIn('transaction_status_name', ['Lewat Jatuh Tempo'])
                    ->sum('balance_due');
                $telatBayarCount = JurnalSalesInvoice::whereIn('transaction_status_name', ['Lewat Jatuh Tempo'])
                    ->count();
                $dibayar30Amount = ViewPaymentHistory::where('payment_date', '>=', Carbon::now()->subDays(30))
                    ->sum('amount');
                $dibayar30Count = ViewPaymentHistory::where('payment_date', '>=', Carbon::now()->subDays(30))
                    ->count();

                return [
                    // 'ledger_summary' => $ledgerInYear,
                    'balance_sheet'  => $reportData,
                    'year'           => $endDateApi,
                    'chart_sales'    => $chartData,
                    'chart_in_out'   => $lineChartInOUt,
                    'jasaincome'      => $jasaincome,
                    'balance_asset'   => $balanceAsset,
                    'balance_piutang' => $balancePiutang1142,
                    'aging_piutang'   => $this->getAgingPiutang($startDateApi, $endDateApi),
                    'top_piutang_customer' => $this->topPiutangCustomer()->getData(),
                    'piutang' => [
                        "total_piutang" => [
                            "amount" => $totalPiutang,
                            "count" => $totalQtyPiutang
                        ],
                        "telat_bayar" => [
                            "amount" => $telatBayarAmount,
                            "count" => $telatBayarCount
                        ],
                        "dibayar_30" => [
                            "amount" => $dibayar30Amount,
                            "count" => $dibayar30Count
                        ]
                    ]
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
