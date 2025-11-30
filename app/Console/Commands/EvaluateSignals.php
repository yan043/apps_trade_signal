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

        $report = "<b>EVALUASI SINYAL TRADING</b>\n";
        $report .= "Date: " . now('Asia/Jakarta')->format('d M Y') . "\n";
        $report .= "==========================================\n\n";

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

                $extra = $signal->extra;
                $desc = $extra['description'] ?? '';
                $signalType = $signal->signal;
                $score = $extra['score'] ?? 0;
                $entry1 = number_format($extra['entry1'] ?? 0, 0, ',', '.');
                $entry2 = number_format($extra['entry2'] ?? 0, 0, ',', '.');
                $tp1 = number_format($extra['takeProfit1'] ?? 0, 0, ',', '.');
                $tp2 = number_format($extra['takeProfit2'] ?? 0, 0, ',', '.');
                $tp3 = number_format($extra['takeProfit3'] ?? 0, 0, ',', '.');
                $tp1p = $extra['takeProfit1_percent'] ?? 0;
                $tp2p = $extra['takeProfit2_percent'] ?? 0;
                $tp3p = $extra['takeProfit3_percent'] ?? 0;
                $sl = number_format($extra['stopLoss'] ?? 0, 0, ',', '.');

                $changeStr = '';
                if ($percent !== null)
                {
                    if ($percent > 0)
                    {
                        $changeStr = "+{$percent}%";
                    }
                    elseif ($percent < 0)
                    {
                        $changeStr = "{$percent}%";
                    }
                    else
                    {
                        $changeStr = "0%";
                    }
                }

                $report .= "#{$symbol}\n";
                $report .= "{$desc}\n";
                $report .= "Type: " . strtoupper($signal->signal_type) . "\n";
                $report .= "Signal: {$signalType} (Score: {$score})\n";
                $report .= "Entry Price: " . number_format($signal->signal_price, 0, ',', '.') . "\n";
                $report .= "Close Price: " . number_format($close, 0, ',', '.') . "\n";
                $report .= "Change: {$changeStr}\n";
                $report .= "Entry: {$entry1} - {$entry2}\n";
                $report .= "TP 1: {$tp1} ({$tp1p}%) | TP 2: {$tp2} ({$tp2p}%) | TP 3: {$tp3} ({$tp3p}%)\n";
                $report .= "SL: {$sl}\n";
                $report .= "==========================================\n\n";
            }
        }

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
