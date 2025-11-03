<?php

namespace App\Http\Controllers;

use App\Services\Jurnal\BalanceSheetService;
use App\Services\Jurnal\GeneralLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    protected GeneralLedgerService $ledgerService;
    protected BalanceSheetService $balanceSheetService;
    public function __construct(GeneralLedgerService $ledgerService, BalanceSheetService $balanceSheetService)
    {
        $this->balanceSheetService = $balanceSheetService;
        $this->ledgerService = $ledgerService;
    }
    public function index(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:d/m/Y',
            'end_date'   => 'required|date_format:d/m/Y|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $summaryData = $this->ledgerService->getSummary(
                $request->start_date,
                $request->end_date
            );
            $reportData = $this->balanceSheetService->getReport($validator->validated());
            $dashboarddata=[
                'ledger_summary'=>$summaryData,
                'balance_sheet'=>$reportData
            ];
            return $this->successResponse($dashboarddata, 'Laporan berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil laporan: ' . $e->getMessage(), 500);
        }
    }
}
