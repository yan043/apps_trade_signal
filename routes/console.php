<?php


use Illuminate\Support\Facades\Artisan;

Artisan::command('send:scalping-signals', function ()
{
    app(\App\Http\Controllers\TradingSignalController::class)->generateAndSendSignals('scalping');
});

Artisan::command('send:swing-signals', function ()
{
    app(\App\Http\Controllers\TradingSignalController::class)->generateAndSendSignals('swing');
});

Artisan::command('send:evaluate-trading', function ()
{
    (new \App\Console\Commands\EvaluateSignals())->handle();
});
