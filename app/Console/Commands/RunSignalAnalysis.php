<?php

namespace App\Console\Commands;

use App\Services\SignalService;
use Illuminate\Console\Command;

class RunSignalAnalysis extends Command
{
    protected $signature = 'signals:run';

    protected $description = 'Run auto signal analysis for all assets';

    public function handle(SignalService $service)
    {
        $service->analyze();
        $this->info('Signal analysis done!');
    }
}
