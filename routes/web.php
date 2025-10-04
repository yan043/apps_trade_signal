<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');
Route::get('/refresh-scalping', [DashboardController::class, 'refreshScalping'])->name('dashboard.refresh.scalping');
Route::get('/refresh-signals', [DashboardController::class, 'refreshSignals'])->name('dashboard.refresh.signals');
