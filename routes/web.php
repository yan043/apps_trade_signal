<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ApiController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/api/stock-data', [ApiController::class, 'getStockData'])->name('api.stock-data');
