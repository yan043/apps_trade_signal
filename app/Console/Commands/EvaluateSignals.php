<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelegramModel;
use App\Services\SignalPerformanceService;

class EvaluateSignals extends Command
{
    protected $signature = 'trading:evaluate-signals';
    protected $description = 'Lacak hasil sinyal (TP/SL) dan laporkan win-rate setelah market tutup';

    public function handle(SignalPerformanceService $performance)
    {
        $justClosed = $performance->updateOpenPositions();
        $metrics = $performance->metrics();

        $report = $this->buildReport($justClosed, $metrics);

        $this->sendReport($report);
        $this->info(strip_tags($report));

        return 0;
    }

    private function buildReport(array $justClosed, array $metrics): string
    {
        $report = "<b>EVALUASI & PERFORMA SINYAL</b>\n";
        $report .= "Date: " . now('Asia/Jakarta')->format('d M Y') . "\n";
        $report .= "==========================================\n\n";

        $report .= "<b>POSISI DITUTUP HARI INI (" . count($justClosed) . ")</b>\n";

        if (empty($justClosed))
        {
            $report .= "Tidak ada posisi yang ditutup.\n";
        }
        else
        {
            foreach ($justClosed as $position)
            {
                $symbol = htmlspecialchars($position->symbol, ENT_QUOTES, 'UTF-8');
                $exit = $this->statusLabel($position->status);
                $realizedR = $position->realized_r >= 0 ? "+{$position->realized_r}R" : "{$position->realized_r}R";
                $percent = $position->percent_change >= 0 ? "+{$position->percent_change}%" : "{$position->percent_change}%";

                $report .= "#{$symbol} [" . strtoupper($position->signal_type) . "] {$exit} {$realizedR} ({$percent})\n";
            }
        }

        $report .= "\n<b>PERFORMA KESELURUHAN</b>\n";
        $report .= $this->metricsBlock('SCALPING', $metrics['scalping']);
        $report .= $this->metricsBlock('SWING', $metrics['swing']);
        $report .= "Posisi masih terbuka: {$metrics['open_positions']}\n";

        return $report;
    }

    private function metricsBlock(string $title, array $stats): string
    {
        if ($stats['total'] === 0)
        {
            return "\n{$title}: belum ada data tertutup\n";
        }

        $block = "\n<b>{$title}</b> ({$stats['total']} sinyal selesai)\n";
        $block .= "Win-rate: {$stats['win_rate']}% | Expectancy: {$stats['expectancy']}R\n";
        $block .= "TP1+: {$stats['tp1_rate']}% | TP2+: {$stats['tp2_rate']}% | TP3: {$stats['tp3_rate']}% | SL: {$stats['sl_rate']}%\n";

        return $block;
    }

    private function statusLabel(string $status): string
    {
        $labels = [
            'tp1' => 'TP1 kena',
            'tp2' => 'TP2 kena',
            'tp3' => 'TP3 kena',
            'sl' => 'SL kena',
            'closed' => 'Tutup (time-stop)',
            'expired' => 'Tutup (max hold)',
        ];

        return $labels[$status] ?? $status;
    }

    private function sendReport(string $report): void
    {
        $tokenBot = config('services.telegram.bot_token');
        $chatID = config('services.telegram.chat_id');
        $threadID = config('services.telegram.thread_id');

        if (empty($tokenBot) || empty($chatID))
        {
            return;
        }

        $maxLength = 4000;

        foreach (str_split($report, $maxLength) as $chunk)
        {
            TelegramModel::sendMessageThread($tokenBot, $chatID, $threadID, $chunk);
        }
    }
}
