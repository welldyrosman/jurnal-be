<?php

namespace App\Http\Controllers;

use App\Models\JurnalInvoice;
use App\Models\QontakDeal;
use App\Services\Jurnal\BalanceSheetService;
use App\Services\Jurnal\GeneralLedgerService;
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

    public function __construct(
        GeneralLedgerService $ledgerService,
        BalanceSheetService $balanceSheetService
    ) {
        $this->ledgerService = $ledgerService;
        $this->balanceSheetService = $balanceSheetService;
    }

    public function index(Request $request): JsonResponse
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
                $aggregate = $metric === 'amount' ? 'SUM(amount)' : 'COUNT(id)';
                $rawData = DB::table('qontak_deals')
                    ->select(
                        'creator_name',
                        DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
                        DB::raw("$aggregate as total")
                    )
                    ->whereBetween('created_at', [$startDateApi, $endDateApi])
                    ->groupBy('creator_name', DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"))
                    ->orderBy('date', 'asc')
                    ->get();

                // Siapkan range tanggal lengkap untuk sumbu X (agar grafik tidak bolong)
                $period = \Carbon\CarbonPeriod::create($startDateApi, $endDateApi);
                $allDates = [];
                foreach ($period as $date) {
                    $allDates[] = $date->format('Y-m-d');
                }
                $creators = $rawData->pluck('creator_name')->unique()->values()->toArray();

                $series = [];
                // Simbol agar mirip gambar (lingkaran, wajik, kotak, segitiga)
                $symbols = ['circle', 'diamond', 'rect', 'triangle'];

                foreach ($creators as $index => $creator) {
                    $dataPoints = [];

                    foreach ($allDates as $date) {
                        // Cari data yang cocok user & tanggal
                        $record = $rawData->where('creator_name', $creator)
                            ->where('date', $date)
                            ->first();

                        $dataPoints[] = $record ? (float) $record->total : 0;
                    }

                    $series[] = [
                        'name' => $creator,
                        'type' => 'line',
                        'data' => $dataPoints,
                        'smooth' => false, // Gambar terlihat garis lurus (false), set true jika ingin melengkung
                        'symbol' => $symbols[$index % count($symbols)], // Rotasi simbol
                        'symbolSize' => 8,
                        'lineStyle' => ['width' => 2]
                    ];
                }

                $dealwonchart = [
                    'categories' => $allDates,
                    'series' => $series,
                    'legend' => $creators
                ];
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
                //    $agingData= JurnalInvoice::
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

                // return $this->successResponse($dashboardData, 'Laporan berhasil diambil');
            });
            return $this->successResponse($dashboardData, 'Laporan berhasil diambil');
        } catch (ValidationException $e) {
            return $this->errorResponse('Data yang diberikan tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil laporan dari Jurnal', 500, ['detail' => $e->getMessage()]);
        }
    }
}
