<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TradingSignalController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard-signal', [DashboardController::class, 'signal'])->name('dashboard-signal');

Route::get('/api/stock-data', [ApiController::class, 'stock_data'])->name('api.stock-data');
Route::get('/api/trading-signals', [TradingSignalController::class, 'getAllSignals'])->name('api.trading-signals');
Route::get('/api/send-signals', [TradingSignalController::class, 'generateAndSendSignals'])->name('api.send-signals');
