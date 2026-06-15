<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function ($schedule): void
    {
        $schedule->command('send:scalping-signals')->weekdays()->timezone('Asia/Jakarta')->hourly()->between('09:00', '16:00');
        $schedule->command('send:swing-signals')->weekdays()->timezone('Asia/Jakarta')->dailyAt('16:35');
        $schedule->command('trading:evaluate-signals')->weekdays()->timezone('Asia/Jakarta')->dailyAt('17:00');
        $schedule->command('send:market-news')->weekdays()->timezone('Asia/Jakarta')->twiceDaily(8, 16);
    })
    ->withMiddleware(function (Middleware $middleware): void
    {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->validateCsrfTokens(except: ['api/send-signals']);
    })
    ->withExceptions(function (Exceptions $exceptions): void
    {
    })->create();
