<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MarketDataService
{
    private const SCANNER_URL = 'https://scanner.tradingview.com/indonesia/scan?label-product=screener-stock';
    private const CACHE_SECONDS = 120;

    public function trendingStocks(): array
    {
        return Cache::remember('market.trending_stocks', self::CACHE_SECONDS, function ()
        {
            return $this->fetchTrendingStocks();
        });
    }

    public function topGainers(): array
    {
        return Cache::remember('market.top_gainers', self::CACHE_SECONDS, function ()
        {
            return $this->scrapeMoversTable(
                'https://id.tradingview.com/markets/stocks-indonesia/market-movers-gainers/',
                ['symbol', 'change', 'price', 'volume', 'rel_volume', 'market_cap', 'pe_ratio', 'eps_dil_ttm', 'eps_dil_growth', 'div_yield', 'sector', 'analyst_rating']
            );
        });
    }

    public function mostActive(): array
    {
        return Cache::remember('market.most_active', self::CACHE_SECONDS, function ()
        {
            return $this->scrapeMoversTable(
                'https://www.tradingview.com/markets/stocks-indonesia/market-movers-active/',
                ['symbol', 'price_x_volume', 'price', 'change', 'volume', 'rel_volume', 'market_cap', 'pe_ratio', 'eps_dil_ttm', 'eps_dil_growth', 'div_yield', 'sector', 'analyst_rating']
            );
        });
    }

    private function fetchTrendingStocks(): array
    {
        $payload = [
            'columns' => [
                'ticker-view', 'close', 'currency', 'change_abs', 'change',
                'open', 'high', 'low', 'volume', 'price_target_1y',
                'sector.tr', 'industry.tr',
            ],
            'filter' => [
                ['left' => 'is_primary', 'operation' => 'equal', 'right' => true],
            ],
            'ignore_unknown_fields' => false,
            'options' => ['lang' => 'id_ID'],
            'range' => [0, 1000],
            'sort' => ['sortBy' => 'change', 'sortOrder' => 'desc'],
            'symbols' => (object) [],
            'markets' => ['indonesia'],
            'filter2' => $this->commonStockTypeFilter(),
        ];

        $response = Http::timeout(30)->post(self::SCANNER_URL, $payload);

        if (!$response->successful())
        {
            return [];
        }

        $results = [];

        foreach ($response->json('data') ?? [] as $item)
        {
            $d = $item['d'];

            $results[] = [
                'logo'         => 'https://s3-symbol-logo.tradingview.com/' . ($d[0]['logoid'] ?? '') . '.svg',
                'name'         => $d[0]['name'] ?? '',
                'description'  => $d[0]['description'] ?? '',
                'price'        => $this->trimZeros($d[1]),
                'currency'     => $d[2] ?? 'IDR',
                'change'       => $this->trimZeros($d[3]),
                'price_change' => $this->trimZeros($d[4]),
                'open'         => $this->trimZeros($d[5]),
                'high'         => $this->trimZeros($d[6]),
                'low'          => $this->trimZeros($d[7]),
                'volume'       => $this->humanVolume($d[8] ?? 0),
                'target_price' => $this->trimZeros($d[9]),
                'sector'       => $d[10] ?? '-',
                'industry'     => $d[11] ?? '-',
            ];
        }

        return $results;
    }

    private function commonStockTypeFilter(): array
    {
        return [
            'operator' => 'and',
            'operands' => [
                [
                    'operation' => [
                        'operator' => 'or',
                        'operands' => [
                            ['operation' => ['operator' => 'and', 'operands' => [
                                ['expression' => ['left' => 'type', 'operation' => 'equal', 'right' => 'stock']],
                                ['expression' => ['left' => 'typespecs', 'operation' => 'has', 'right' => ['common']]],
                            ]]],
                            ['operation' => ['operator' => 'and', 'operands' => [
                                ['expression' => ['left' => 'type', 'operation' => 'equal', 'right' => 'stock']],
                                ['expression' => ['left' => 'typespecs', 'operation' => 'has', 'right' => ['preferred']]],
                            ]]],
                            ['operation' => ['operator' => 'and', 'operands' => [
                                ['expression' => ['left' => 'type', 'operation' => 'equal', 'right' => 'dr']],
                            ]]],
                            ['operation' => ['operator' => 'and', 'operands' => [
                                ['expression' => ['left' => 'type', 'operation' => 'equal', 'right' => 'fund']],
                                ['expression' => ['left' => 'typespecs', 'operation' => 'has_none_of', 'right' => ['etf']]],
                            ]]],
                        ],
                    ],
                ],
                ['expression' => ['left' => 'typespecs', 'operation' => 'has_none_of', 'right' => ['pre-ipo']]],
            ],
        ];
    }

    private function scrapeMoversTable(string $url, array $columns): array
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful())
        {
            return [];
        }

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument;
        $dom->loadHTML(trim($response->body()));

        $table = $dom->getElementsByTagName('table')->item(0);

        if ($table === null)
        {
            return [];
        }

        $rows = $table->getElementsByTagName('tr');
        $result = [];

        for ($i = 1, $count = $rows->length; $i < $count; $i++)
        {
            $cells = $rows->item($i)->getElementsByTagName('td');
            $data = [];

            foreach ($columns as $j => $column)
            {
                $td = $cells->item($j);
                $data[$column] = $td ? trim($td->nodeValue) : null;
            }

            $data['description'] = trim(substr($data['symbol'] ?? '', 4));
            $data['symbol'] = substr($data['symbol'] ?? '', 0, 4);

            $result[] = $data;
        }

        return $result;
    }

    private function trimZeros($value): string
    {
        return rtrim(rtrim(number_format(floatval($value), 2, '.', ''), '0'), '.');
    }

    private function humanVolume($value): string
    {
        $value = floatval($value);

        if ($value >= 1000000000000) return number_format($value / 1000000000000, 2) . ' T';
        if ($value >= 1000000000) return number_format($value / 1000000000, 2) . ' B';
        if ($value >= 1000000) return number_format($value / 1000000, 2) . ' M';
        if ($value >= 1000) return number_format($value / 1000, 2) . ' K';

        return number_format($value, 2);
    }
}
