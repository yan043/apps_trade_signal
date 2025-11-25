<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\TradingSignalController;

class SendTradingSignals extends Command
{
    protected $signature = 'trading:send-signals {type?}';
    protected $description = 'Send trading signals to Telegram (scalping/swing/all)';

    public function handle()
    {
        $type = $this->argument('type') ?? 'all';

        $this->info("Generating {$type} trading signals...");

        $controller = new TradingSignalController();
        $result = $controller->generateAndSendSignals();

        $data = $result->getData();

        $this->info("✅ Signals sent successfully!");
        $this->info("Scalping signals: " . $data->scalping_signals);
        $this->info("Swing signals: " . $data->swing_signals);
        $this->info("Sent at: " . $data->sent_at);

        return 0;
    }
}
