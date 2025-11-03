<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JurnalLocalApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/v1/local/sales_invoices', [JurnalLocalApiController::class, 'getSalesInvoices']);
Route::post('/dashboard', [DashboardController::class, 'index']);