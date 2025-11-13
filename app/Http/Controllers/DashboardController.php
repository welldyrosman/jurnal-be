<?php

namespace App\Http\Controllers;

use App\Services\Jurnal\BalanceSheetService;
use App\Services\Jurnal\GeneralLedgerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            
           
            $startDateApi = Carbon::createFromFormat('d/m/Y', $request->start_date)->format('Y-m-d');
            $endDateApi = Carbon::createFromFormat('d/m/Y', $request->end_date)->format('Y-m-d');

            $summaryData = $this->ledgerService->getSummary(
                $startDateApi,
                $endDateApi
            );

           
            $reportData = $this->balanceSheetService->getReport(['end_date' => $endDateApi]);

            $startDateInThisYear = Carbon::parse($endDateApi)->startOfYear()->format('Y-m-d');
            $endDateInThisYear = Carbon::parse($endDateApi)->endOfYear()->format('Y-m-d');
            $ledgerInYear = $this->ledgerService->getSummary($startDateInThisYear, $endDateInThisYear);
            $chartData = $this->ledgerService->calculateMonthlySales($ledgerInYear);
            $dashboardData = [
                'ledger_summary' => $summaryData,
                'balance_sheet'  => $reportData,
                'year'           => $endDateApi,
                'chart_sales'    => $chartData,
            ];
            
            return $this->successResponse($dashboardData, 'Laporan berhasil diambil');

        } catch (ValidationException $e) {
            return $this->errorResponse('Data yang diberikan tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil laporan dari Jurnal', 500, ['detail' => $e->getMessage()]);
        }
    }
   
}

