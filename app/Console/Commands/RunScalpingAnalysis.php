<?php

namespace App\Console\Commands;

use App\Services\ScalpingSignalService;
use Illuminate\Console\Command;

class RunScalpingAnalysis extends Command
{
    protected $signature = 'signals:scalping {symbol=BTCUSDT}';

    protected $description = 'Run intraday scalping signal analysis for crypto';

    public function handle(ScalpingSignalService $service)
    {
        $symbol = strtoupper($this->argument('symbol'));

        $this->info("Running scalping analysis for {$symbol} ...");

        $signal = $service->analyzeScalping($symbol);

        if ($signal === null)
        {
            $this->error("Not enough data for {$symbol}.");
            return;
        }

        $this->info("Symbol: {$signal['symbol']}");
        $this->info("Price: {$signal['price']}");
        $this->info("EMA9: {$signal['ema9']}");
        $this->info("EMA21: {$signal['ema21']}");
        $this->info("RSI: {$signal['rsi']}");
        $this->info("Action: {$signal['action']}");

        if ($signal['action'] !== 'HOLD')
        {
            $this->info("Take Profit (TP): {$signal['tp']}");
            $this->info("Stop Loss (SL): {$signal['sl']}");
        }
    }
}
