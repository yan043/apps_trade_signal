<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function ()
{
    $now = now('Asia/Jakarta');
    $hour = $now->hour;
    $minute = $now->minute;
    $dayOfWeek = $now->dayOfWeek;

    if ($dayOfWeek == 0 || $dayOfWeek == 6)
    {
        return;
    }

    $isFriday = ($dayOfWeek == 5);

    $session1Start = 9;
    $session1End = $isFriday ? 11 : 12;
    $session2Start = $isFriday ? 14 : 13;
    $session2End = 16;

    $isSession1 = ($hour >= $session1Start && $hour < $session1End);
    $isSession2 = ($hour >= $session2Start && $hour <= $session2End);

    if ($isSession1 || $isSession2)
    {
        app(App\Http\Controllers\TradingSignalController::class)->generateAndSendSignals();
    }
})->everyFiveMinutes()->name('send-scalping-signals');

Schedule::call(function ()
{
    $now = now('Asia/Jakarta');
    $dayOfWeek = $now->dayOfWeek;

    if ($dayOfWeek == 0 || $dayOfWeek == 6)
    {
        return;
    }

    app(App\Http\Controllers\TradingSignalController::class)->generateAndSendSignals();
})->hourly()->name('send-swing-signals');
