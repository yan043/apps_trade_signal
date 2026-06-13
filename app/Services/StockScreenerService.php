<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class StockScreenerService
{
    private const SCANNER_URL = 'https://scanner.tradingview.com/indonesia/scan?label-product=screener-stock';
    private const CACHE_SECONDS = 60;

    private const COLUMNS = [
        'name', 'description', 'logoid',
        'close', 'open', 'high', 'low', 'volume', 'change', 'change_abs',
        'Value.Traded', 'relative_volume_10d_calc',
        'average_volume_10d_calc', 'average_volume_30d_calc',
        'market_cap_basic', 'Volatility.D', 'VWAP',
        'ATR|15', 'EMA5|5', 'EMA20|5', 'EMA20|15', 'EMA50|15',
        'RSI|15', 'MACD.macd|15', 'MACD.signal|15',
        'ADX|15', 'ADX+DI|15', 'ADX-DI|15', 'BB.upper|15', 'BB.lower|15',
        'ATR', 'EMA20', 'EMA50', 'EMA200', 'RSI',
        'MACD.macd', 'MACD.signal', 'ADX', 'ADX+DI', 'ADX-DI',
        'MACD.macd|1W', 'MACD.signal|1W',
        'price_52_week_high', 'price_52_week_low',
        'Pivot.M.Classic.Middle', 'Perf.W', 'Perf.1M',
    ];

    public function getIndicatorSnapshots(): array
    {
        return Cache::remember('screener.indicator_snapshots', self::CACHE_SECONDS, function ()
        {
            return $this->fetchSnapshots();
        });
    }

    public function getQuotesBySymbols(array $symbols): array
    {
        if (empty($symbols))
        {
            return [];
        }

        $tickers = array_map(fn ($symbol) => 'IDX:' . $symbol, $symbols);

        $payload = [
            'columns' => ['name', 'close', 'high', 'low'],
            'symbols' => ['tickers' => $tickers],
            'options' => ['lang' => 'en'],
        ];

        $response = Http::timeout(30)->post(self::SCANNER_URL, $payload);

        if (!$response->successful())
        {
            return [];
        }

        $quotes = [];

        foreach ($response->json('data') ?? [] as $item)
        {
            $d = $item['d'];
            $quotes[$d[0]] = [
                'close' => $d[1],
                'high' => $d[2],
                'low' => $d[3],
            ];
        }

        return $quotes;
    }

    private function fetchSnapshots(): array
    {
        $payload = [
            'columns' => self::COLUMNS,
            'filter' => [
                ['left' => 'close', 'operation' => 'egreater', 'right' => 100],
                ['left' => 'market_cap_basic', 'operation' => 'egreater', 'right' => 500000000000],
                ['left' => 'volume', 'operation' => 'greater', 'right' => 0],
                ['left' => 'is_primary', 'operation' => 'equal', 'right' => true],
            ],
            'ignore_unknown_fields' => false,
            'options' => ['lang' => 'en'],
            'range' => [0, 600],
            'sort' => ['sortBy' => 'Value.Traded', 'sortOrder' => 'desc'],
            'symbols' => (object) [],
            'markets' => ['indonesia'],
        ];

        $response = Http::timeout(30)->post(self::SCANNER_URL, $payload);

        if (!$response->successful())
        {
            return [];
        }

        $snapshots = [];

        foreach ($response->json('data') ?? [] as $item)
        {
            $row = array_combine(self::COLUMNS, $item['d']);
            $row['logo'] = 'https://s3-symbol-logo.tradingview.com/' . ($row['logoid'] ?? '') . '.svg';
            $snapshots[] = $row;
        }

        return $snapshots;
    }
}
