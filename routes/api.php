<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JurnalLocalApiController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountGroupingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\QontakDealController;
use App\Http\Controllers\QontakDealReportController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dashboard', [DashboardController::class, 'index']);
});


Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/local/sales_invoices', [JurnalLocalApiController::class, 'getSalesInvoices']);
    Route::prefix('budgets')->group(function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::get('/grouping-options', [AccountController::class, 'getGroupingOptions']);
        Route::post('/grouping', [AccountController::class, 'updateGrouping']);
        Route::post('/save', [AccountController::class, 'save']);
        Route::get('/import-template', [AccountController::class, 'downloadtemplate']);
        Route::get('/export', [AccountController::class, 'exportexcel']);
        Route::post('/import', [AccountController::class, 'importPreview']);
        Route::post('/import-save', [AccountController::class, 'importSave']);
    });
    Route::prefix('budgets/grouping')->group(function () {
        Route::post('/', [AccountGroupingController::class, 'store']);
        Route::put('/{id}', [AccountGroupingController::class, 'update']);
        Route::delete('/{id}', [AccountGroupingController::class, 'destroy']);
    });

    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountController::class, 'getallccoa']);
    });
});
Route::middleware('auth:sanctum')->get('/test-export', function () {
    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\BudgetsExport(2025),
        'test.xlsx'
    );
});
Route::prefix('qontak/reports')->group(function () {
    Route::get('by-stage', [QontakDealReportController::class, 'byStage']);
    Route::get('amount-by-stage', [QontakDealReportController::class, 'amountByStage']);
    Route::get('per-month', [QontakDealReportController::class, 'perMonth']);
    Route::get('pipeline-lead', [ReportController::class, 'pipelineReport']);
    Route::get('deals', [QontakDealReportController::class, 'index']);
});

Route::resource('users', UserController::class)->only([
    'index',
    'store',
    'show',
    'update',
    'destroy'
]);
Route::get('/companies', [CompanyController::class, 'index']);
Route::post('/companies', [CompanyController::class, 'store']);

Route::get('/pipelines', [PipelineController::class, 'getpipelines']);

Route::get('/qontak-deal', [QontakDealController::class, 'getDeals']);
