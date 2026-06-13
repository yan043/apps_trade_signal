<?php

namespace App\Services;

use App\Models\SignalHistory;

class SignalPerformanceService
{
    private const SWING_MAX_HOLD_DAYS = 20;

    public function __construct(private StockScreenerService $screener)
    {
    }

    public function updateOpenPositions(): array
    {
        $openPositions = SignalHistory::where('status', 'open')->get();

        if ($openPositions->isEmpty())
        {
            return [];
        }

        $symbols = $openPositions->pluck('symbol')->unique()->values()->all();
        $quotes = $this->screener->getQuotesBySymbols($symbols);

        $justClosed = [];

        foreach ($openPositions as $position)
        {
            $quote = $quotes[$position->symbol] ?? null;

            if ($quote === null)
            {
                continue;
            }

            if ($this->applyQuote($position, $quote))
            {
                $justClosed[] = $position;
            }
        }

        return $justClosed;
    }

    public function metrics(): array
    {
        $closed = SignalHistory::where('status', '!=', 'open')->get();

        return [
            'scalping' => $this->aggregate($closed->where('signal_type', 'scalping')),
            'swing' => $this->aggregate($closed->where('signal_type', 'swing')),
            'open_positions' => SignalHistory::where('status', 'open')->count(),
            'updated_at' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ];
    }

    private function applyQuote(SignalHistory $position, array $quote): bool
    {
        $high = floatval($quote['high']);
        $low = floatval($quote['low']);
        $close = floatval($quote['close']);

        $entry = floatval($position->entry_price);
        $stopLoss = floatval($position->stop_loss);
        $takeProfit1 = floatval($position->take_profit_1);
        $takeProfit2 = floatval($position->take_profit_2);
        $takeProfit3 = floatval($position->take_profit_3);

        $position->highest_high = max(floatval($position->highest_high), $high);
        $position->lowest_low = min(floatval($position->lowest_low), $low);
        $position->close_price = $close;
        $position->days_held = $position->days_held + 1;

        if ($low <= $stopLoss)
        {
            return $this->closePosition($position, $stopLoss, 'sl', $entry);
        }

        if ($high >= $takeProfit3)
        {
            return $this->closePosition($position, $takeProfit3, 'tp3', $entry);
        }

        if ($high >= $takeProfit2)
        {
            return $this->closePosition($position, $takeProfit2, 'tp2', $entry);
        }

        if ($high >= $takeProfit1)
        {
            return $this->closePosition($position, $takeProfit1, 'tp1', $entry);
        }

        if ($position->signal_type === 'scalping')
        {
            return $this->closePosition($position, $close, 'closed', $entry);
        }

        if ($position->days_held >= self::SWING_MAX_HOLD_DAYS)
        {
            return $this->closePosition($position, $close, 'expired', $entry);
        }

        $position->percent_change = $this->percentChange($entry, $close);
        $position->save();

        return false;
    }

    private function closePosition(SignalHistory $position, float $exitPrice, string $status, float $entry): bool
    {
        $risk = $entry - floatval($position->stop_loss);
        $realizedR = $risk > 0 ? ($exitPrice - $entry) / $risk : 0;

        $position->status = $status;
        $position->close_price = $exitPrice;
        $position->realized_r = round($realizedR, 3);
        $position->percent_change = $this->percentChange($entry, $exitPrice);
        $position->outcome = $this->classifyOutcome($status, $realizedR);
        $position->closed_at = now('Asia/Jakarta');
        $position->save();

        return true;
    }

    private function classifyOutcome(string $status, float $realizedR): string
    {
        if (in_array($status, ['tp1', 'tp2', 'tp3'], true))
        {
            return 'win';
        }

        if ($status === 'sl')
        {
            return 'loss';
        }

        if ($realizedR > 0.05)
        {
            return 'win';
        }

        if ($realizedR < -0.05)
        {
            return 'loss';
        }

        return 'scratch';
    }

    private function aggregate($positions): array
    {
        $total = $positions->count();

        if ($total === 0)
        {
            return [
                'total' => 0,
                'wins' => 0,
                'losses' => 0,
                'scratch' => 0,
                'win_rate' => null,
                'avg_r' => null,
                'expectancy' => null,
                'tp1_rate' => null,
                'tp2_rate' => null,
                'tp3_rate' => null,
                'sl_rate' => null,
            ];
        }

        $wins = $positions->where('outcome', 'win')->count();
        $losses = $positions->where('outcome', 'loss')->count();
        $scratch = $positions->where('outcome', 'scratch')->count();

        $tp1Plus = $positions->whereIn('status', ['tp1', 'tp2', 'tp3'])->count();
        $tp2Plus = $positions->whereIn('status', ['tp2', 'tp3'])->count();
        $tp3Hit = $positions->where('status', 'tp3')->count();
        $slHit = $positions->where('status', 'sl')->count();

        $avgR = $positions->avg('realized_r');

        return [
            'total' => $total,
            'wins' => $wins,
            'losses' => $losses,
            'scratch' => $scratch,
            'win_rate' => round($wins / $total * 100, 1),
            'avg_r' => round($avgR, 2),
            'expectancy' => round($avgR, 2),
            'tp1_rate' => round($tp1Plus / $total * 100, 1),
            'tp2_rate' => round($tp2Plus / $total * 100, 1),
            'tp3_rate' => round($tp3Hit / $total * 100, 1),
            'sl_rate' => round($slHit / $total * 100, 1),
        ];
    }

    private function percentChange(float $entry, float $price): float
    {
        return $entry > 0 ? round(($price - $entry) / $entry * 100, 2) : 0;
    }
}
