<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function ()
{
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');
Route::get('/dashboard/refresh-scalping', [DashboardController::class, 'refreshScalping'])->name('dashboard.refresh.scalping');
Route::get('/dashboard/refresh-signals', [DashboardController::class, 'refreshSignals'])->name('dashboard.refresh.signals');
