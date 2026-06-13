<?php


use Illuminate\Support\Facades\Artisan;

Artisan::command('send:scalping-signals', function ()
{
    app(\App\Http\Controllers\TradingSignalController::class)->dispatchSignals('scalping');
});

Artisan::command('send:swing-signals', function ()
{
    app(\App\Http\Controllers\TradingSignalController::class)->dispatchSignals('swing');
});
