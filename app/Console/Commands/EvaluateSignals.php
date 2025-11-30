<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SignalHistory;
use App\Models\TelegramModel;
use Illuminate\Support\Facades\Http;

class EvaluateSignals extends Command
{
    protected $signature = 'trading:evaluate-signals';
    protected $description = 'Evaluasi hasil sinyal trading setelah market tutup';

    public function handle()
    {
        $today = now('Asia/Jakarta')->startOfDay();
        $signals = SignalHistory::whereDate('sent_at', $today)->get();
        if ($signals->isEmpty())
        {
            $this->info('Tidak ada sinyal yang perlu dievaluasi hari ini.');
            return 0;
        }

        $symbols = $signals->pluck('symbol')->unique()->values()->all();
        $closePrices = $this->fetchClosePrices($symbols);

        $report = "\n📊 EVALUASI SINYAL TRADING (" . now('Asia/Jakarta')->format('d M Y') . ")\n==============================\n";
        foreach ($signals as $signal)
        {
            $symbol = $signal->symbol;
            $close = $closePrices[$symbol] ?? null;
            if ($close)
            {
                $percent = $signal->signal_price > 0 ? round((($close - $signal->signal_price) / $signal->signal_price) * 100, 2) : null;
                $signal->close_price = $close;
                $signal->percent_change = $percent;
                $signal->save();
                $report .= "{$symbol} ({$signal->signal_type})\n";
                $changeStr = '-';
                if ($percent !== null)
                {
                    if ($percent > 0)
                    {
                        $changeStr = "📈 +{$percent}%";
                    }
                    elseif ($percent < 0)
                    {
                        $changeStr = "📉 {$percent}%";
                    }
                    else
                    {
                        $changeStr = "0%";
                    }
                }
                $report .= "Entry: {$signal->signal_price} | Close: {$close} | Change: {$changeStr}\n";
                $extra = $signal->extra;
                if ($extra)
                {
                    $report .= "TP1: " . ($extra['takeProfit1'] ?? '-') . ", TP2: " . ($extra['takeProfit2'] ?? '-') . ", TP3: " . ($extra['takeProfit3'] ?? '-') . ", SL: " . ($extra['stopLoss'] ?? '-') . "\n";
                }
                $report .= "------------------------------\n";
            }
        }
        $report .= "\nSelesai.\n";

        TelegramModel::sendMessage(env('TELEGRAM_BOT_TOKEN'), env('TELEGRAM_CHAT_ID'), $report);

        $this->info($report);
        return 0;
    }

    private function fetchClosePrices($symbols)
    {
        $result = [];
        if (empty($symbols)) return $result;
        $url = 'https://scanner.tradingview.com/indonesia/scan';
        $payload = [
            'symbols' => ['tickers' => $symbols, 'query' => ['types' => []]],
            'columns' => ['close'],
        ];
        $response = Http::post($url, $payload);
        if ($response->ok() && isset($response['data']))
        {
            foreach ($response['data'] as $item)
            {
                $symbol = $item['s'] ?? null;
                $close = $item['d'][0] ?? null;
                if ($symbol && $close)
                {
                    $result[$symbol] = $close;
                }
            }
        }
        return $result;
    }
}
