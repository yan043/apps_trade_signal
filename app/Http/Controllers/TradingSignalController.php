<?php

namespace App\Http\Controllers;

use App\Models\SignalHistory;
use App\Models\TelegramModel;
use App\Services\SignalEngine;
use App\Services\SignalPerformanceService;
use App\Services\StockScreenerService;

class TradingSignalController extends Controller
{
    public function __construct(
        private StockScreenerService $screener,
        private SignalEngine $engine
    )
    {
    }

    public function generateAndSendSignals($type = 'all')
    {
        if (!$this->triggerKeyValid())
        {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json($this->dispatchSignals($type));
    }

    public function dispatchSignals($type = 'all'): array
    {
        $sessionOpen = $this->engine->isScalpingSessionOpen();
        $snapshots = $this->screener->getIndicatorSnapshots();

        $scalpingSignals = [];
        $swingSignals = [];

        foreach ($snapshots as $stock)
        {
            if (($type === 'all' || $type === 'scalping') && $sessionOpen)
            {
                $signal = $this->engine->scalpingSignal($stock);

                if ($signal !== null && $this->isBuySignal($signal['signal']))
                {
                    $scalpingSignals[] = $signal;
                }
            }

            if ($type === 'all' || $type === 'swing')
            {
                $signal = $this->engine->swingSignal($stock);

                if ($signal !== null && $this->isBuySignal($signal['signal']))
                {
                    $swingSignals[] = $signal;
                }
            }
        }

        $this->sortByScore($scalpingSignals);
        $this->sortByScore($swingSignals);

        if (!empty($scalpingSignals))
        {
            $this->sendSignalBatch($scalpingSignals, 'scalping');
        }

        if (!empty($swingSignals))
        {
            $this->sendSignalBatch($swingSignals, 'swing');
        }

        return [
            'success' => true,
            'scalping_session_open' => $sessionOpen,
            'scalping_signals' => count($scalpingSignals),
            'swing_signals' => count($swingSignals),
            'sent_at' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];
    }

    public function getAllSignals()
    {
        $snapshots = $this->screener->getIndicatorSnapshots();

        $scalpingSignals = [];
        $swingSignals = [];

        foreach ($snapshots as $stock)
        {
            $scalpSignal = $this->engine->scalpingSignal($stock);

            if ($scalpSignal !== null)
            {
                $scalpingSignals[] = $scalpSignal;
            }

            $swingSignal = $this->engine->swingSignal($stock);

            if ($swingSignal !== null)
            {
                $swingSignals[] = $swingSignal;
            }
        }

        $this->sortByScore($scalpingSignals);
        $this->sortByScore($swingSignals);

        return response()->json([
            'scalping_signals' => $scalpingSignals,
            'swing_signals' => $swingSignals,
            'scalping_session_open' => $this->engine->isScalpingSessionOpen(),
            'last_updated' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);
    }

    public function getPerformance(SignalPerformanceService $performance)
    {
        return response()->json($performance->metrics());
    }

    private function triggerKeyValid(): bool
    {
        $configuredKey = config('services.signal.trigger_key');

        if (empty($configuredKey))
        {
            return false;
        }

        return hash_equals($configuredKey, (string) request()->header('X-Trigger-Key'));
    }

    private function isBuySignal(string $signal): bool
    {
        return $signal === 'STRONG BUY' || $signal === 'BUY';
    }

    private function sortByScore(array &$signals): void
    {
        usort($signals, function ($a, $b)
        {
            return $b['score'] <=> $a['score'];
        });
    }

    private function sendSignalBatch(array $signals, string $type): void
    {
        $titles = [
            'scalping' => 'SCALPING SIGNALS (M5-M15)',
            'swing' => 'SWING TRADING SIGNALS (D1)',
        ];

        $header = "<b>{$titles[$type]}</b>\n";
        $header .= "Date/Time: " . now('Asia/Jakarta')->format('d M Y H:i:s') . " WIB\n";
        $header .= "==========================================\n\n";

        $bodies = [];

        foreach ($signals as $index => $signal)
        {
            $this->saveHistory($signal, $type);
            $bodies[] = $this->formatSignalBody($index + 1, $signal);
        }

        $threadID = config("services.telegram.{$type}_thread_id") ?: config('services.telegram.thread_id');

        $this->paginateAndSend($header, $bodies, $threadID);
    }

    private function saveHistory(array $signal, string $type): void
    {
        $history = SignalHistory::firstOrNew([
            'symbol' => $signal['symbol'],
            'signal_type' => $type,
            'sent_at' => now('Asia/Jakarta')->startOfDay(),
        ]);

        if (!$history->exists)
        {
            $history->signal_price = $signal['price'];
            $history->entry_price = $signal['entry2'];
            $history->stop_loss = $signal['stopLoss'];
            $history->take_profit_1 = $signal['takeProfit1'];
            $history->take_profit_2 = $signal['takeProfit2'];
            $history->take_profit_3 = $signal['takeProfit3'];
            $history->highest_high = $signal['entry2'];
            $history->lowest_low = $signal['entry2'];
            $history->status = 'open';
            $history->days_held = 0;
        }

        $history->signal = $signal['signal'];
        $history->close_price = $signal['price'];
        $history->extra = $signal;
        $history->save();
    }

    private function formatSignalBody(int $number, array $signal): string
    {
        $price = number_format($signal['price'], 0, ',', '.');
        $entry1 = number_format($signal['entry1'], 0, ',', '.');
        $entry2 = number_format($signal['entry2'], 0, ',', '.');
        $stopLoss = number_format($signal['stopLoss'], 0, ',', '.');
        $tp1 = number_format($signal['takeProfit1'], 0, ',', '.');
        $tp2 = number_format($signal['takeProfit2'], 0, ',', '.');
        $tp3 = number_format($signal['takeProfit3'], 0, ',', '.');

        $symbol = htmlspecialchars($signal['symbol'], ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($signal['description'], ENT_QUOTES, 'UTF-8');

        $body = "#{$number} {$symbol}\n";
        $body .= "{$description}\n";
        $body .= "Price: {$price} ({$signal['change']}%)\n";
        $body .= "Signal: {$signal['signal']} (Score: {$signal['score']})\n";
        $body .= "Entry: {$entry1} - {$entry2}\n";
        $body .= "TP 1: {$tp1} (+{$signal['takeProfit1_percent']}%) | TP 2: {$tp2} (+{$signal['takeProfit2_percent']}%) | TP 3: {$tp3} (+{$signal['takeProfit3_percent']}%)\n";
        $body .= "SL: {$stopLoss} ({$signal['stopLoss_percent']}%)\n";
        $body .= "R:R {$signal['riskReward']} | RSI {$signal['rsi']} | ADX {$signal['adx']} | Vol {$signal['volumeRatio']}x | Ruang ARA {$signal['roomToAra']}%\n";
        $body .= "==========================================\n\n";

        return $body;
    }

    private function paginateAndSend(string $header, array $bodies, $threadID): void
    {
        $maxLength = 4000;
        $pages = [];
        $current = '';

        foreach ($bodies as $body)
        {
            if (strlen($header . $current . $body) > $maxLength && $current !== '')
            {
                $pages[] = $current;
                $current = '';
            }

            $current .= $body;
        }

        if ($current !== '')
        {
            $pages[] = $current;
        }

        $totalPages = count($pages);
        $tokenBot = config('services.telegram.bot_token');
        $chatID = config('services.telegram.chat_id');

        foreach ($pages as $i => $content)
        {
            $pageHeader = $header . "<b>Page " . ($i + 1) . " of {$totalPages}</b>\n\n";

            TelegramModel::sendMessageThread($tokenBot, $chatID, $threadID, $pageHeader . $content);
        }
    }
}
