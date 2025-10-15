<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function getStockData()
    {
        $stock_price_to_earnings_ratio = $this->stock_price_to_earnings_ratio();
        $stock_market_movers_gainers = $this->stock_market_movers_gainers();
        $stock_most_active = $this->stock_most_active();

        $lastUpdated = now()->format('Y-m-d H:i:s');

        return response()->json([
            'stock_price_to_earnings_ratio' => $stock_price_to_earnings_ratio,
            'stock_market_movers_gainers' => $stock_market_movers_gainers,
            'stock_most_active' => $stock_most_active,
            'last_updated' => $lastUpdated,
        ]);
    }

    private function stock_price_to_earnings_ratio()
    {
        $results = [];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://scanner.tradingview.com/indonesia/scan?label-product=screener-stock',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "columns": [
                "name",
                "description",
                "logoid",
                "update_mode",
                "type",
                "typespecs",
                "close",
                "pricescale",
                "minmov",
                "fractional",
                "minmove2",
                "currency",
                "change_abs",
                "open",
                "high",
                "low",
                "volume",
                "price_earnings_ttm",
                "AnalystRating",
                "AnalystRating.tr",
                "exchange"
            ],
            "filter": [
                { "left": "price_earnings_ttm", "operation": "egreater", "right": 25 },
                { "left": "AnalystRating", "operation": "in_range", "right": ["StrongSell", "Sell", "StrongBuy", "Buy"] },
                { "left": "is_primary", "operation": "equal", "right": true }
            ],
            "ignore_unknown_fields": false,
            "options": { "lang": "en" },
            "range": [0, 100],
            "sort": { "sortBy": "market_cap_basic", "sortOrder": "desc" },
            "symbols": {},
            "markets": ["indonesia"],
            "filter2": {
                "operator": "and",
                "operands": [
                    {
                        "operation": {
                            "operator": "or",
                            "operands": [
                                {
                                    "operation": {
                                        "operator": "and",
                                        "operands": [
                                            { "expression": { "left": "type", "operation": "equal", "right": "stock" } },
                                            { "expression": { "left": "typespecs", "operation": "has", "right": ["common"] } }
                                        ]
                                    }
                                },
                                {
                                    "operation": {
                                        "operator": "and",
                                        "operands": [
                                            { "expression": { "left": "type", "operation": "equal", "right": "stock" } },
                                            {
                                                "expression": {
                                                    "left": "typespecs",
                                                    "operation": "has",
                                                    "right": ["preferred"]
                                                }
                                            }
                                        ]
                                    }
                                },
                                {
                                    "operation": {
                                        "operator": "and",
                                        "operands": [{ "expression": { "left": "type", "operation": "equal", "right": "dr" } }]
                                    }
                                },
                                {
                                    "operation": {
                                        "operator": "and",
                                        "operands": [
                                            { "expression": { "left": "type", "operation": "equal", "right": "fund" } },
                                            {
                                                "expression": {
                                                    "left": "typespecs",
                                                    "operation": "has_none_of",
                                                    "right": ["etf"]
                                                }
                                            }
                                        ]
                                    }
                                }
                            ]
                        }
                    },
                    { "expression": { "left": "typespecs", "operation": "has_none_of", "right": ["pre-ipo"] } }
                ]
            }
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $data = json_decode($response, true);

        foreach ($data['data'] as $item)
        {
            $logo = 'https://s3-symbol-logo.tradingview.com/' . $item['d'][2] . '.svg';

            $name = $item['d'][0];

            $description = $item['d'][1];

            $price = rtrim(rtrim(number_format($item['d'][6], 2, '.', ''), '0'), '.');

            $currency = $item['d'][11];

            $change = rtrim(rtrim(number_format($item['d'][12], 2, '.', ''), '0'), '.');

            $open = rtrim(rtrim(number_format($item['d'][13], 2, '.', ''), '0'), '.');

            $high = rtrim(rtrim(number_format($item['d'][14], 2, '.', ''), '0'), '.');

            $low = rtrim(rtrim(number_format($item['d'][15], 2, '.', ''), '0'), '.');

            if ($item['d'][16] >= 1000000000000)
            {
                $volume = number_format($item['d'][16] / 1000000000000, 2) . ' T';
            }
            elseif ($item['d'][16] >= 1000000000)
            {
                $volume = number_format($item['d'][16] / 1000000000, 2) . ' B';
            }
            elseif ($item['d'][16] >= 1000000)
            {
                $volume = number_format($item['d'][16] / 1000000, 2) . ' M';
            }
            elseif ($item['d'][16] >= 1000)
            {
                $volume = number_format($item['d'][16] / 1000, 2) . ' K';
            }
            else
            {
                $volume = number_format($item['d'][16], 2);
            }

            $price_earnings_ttm = rtrim(rtrim(number_format($item['d'][17], 2, '.', ''), '0'), '.');

            $analystRating = $item['d'][18];

            $results[] = [
                'logo'               => $logo,
                'name'               => $name,
                'description'        => $description,
                'price'              => $price,
                'currency'           => $currency,
                'change'             => $change,
                'open'               => $open,
                'high'               => $high,
                'low'                => $low,
                'volume'             => $volume,
                'price_earnings_ttm' => $price_earnings_ttm,
                'analystRating'      => $analystRating,
            ];
        }

        return $results;
    }

    private function stock_market_movers_gainers()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://id.tradingview.com/markets/stocks-indonesia/market-movers-gainers/',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument;
        $dom->loadHTML(trim($response));

        $table = $dom->getElementsByTagName('table')->item(0);

        if ($table !== null)
        {
            $rows = $table->getElementsByTagName('tr');

            $columns = [
                'symbol',
                'change',
                'price',
                'volume',
                'rel_volume',
                'market_cap',
                'pe_ratio',
                'eps_dil_ttm',
                'eps_dil_growth',
                'div_yield',
                'sector',
                'analyst_rating',
            ];

            $result = [];

            for ($i = 1, $count = $rows->length; $i < $count; $i++)
            {
                $cells = $rows->item($i)->getElementsByTagName('td');

                $data = [];

                for ($j = 0; $j < count($columns); $j++)
                {
                    $td = $cells->item($j);
                    $data[$columns[$j]] = $td ? trim($td->nodeValue) : null;
                }

                $data['description'] = trim(substr($data['symbol'], 4));
                $data['symbol'] = substr($data['symbol'], 0, 4);

                $result[] = $data;
            }

            return $result;
        }
    }

    private function stock_most_active()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.tradingview.com/markets/stocks-indonesia/market-movers-active/',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument;
        $dom->loadHTML(trim($response));

        $table = $dom->getElementsByTagName('table')->item(0);

        if ($table !== null)
        {
            $rows = $table->getElementsByTagName('tr');

            $columns = [
                'symbol',
                'price_x_volume',
                'price',
                'change',
                'volume',
                'rel_volume',
                'market_cap',
                'pe_ratio',
                'eps_dil_ttm',
                'eps_dil_growth',
                'div_yield',
                'sector',
                'analyst_rating',
            ];

            $result = [];

            for ($i = 1, $count = $rows->length; $i < $count; $i++)
            {
                $cells = $rows->item($i)->getElementsByTagName('td');

                $data = [];

                for ($j = 0; $j < count($columns); $j++)
                {
                    $td = $cells->item($j);
                    $data[$columns[$j]] = $td ? trim($td->nodeValue) : null;
                }

                $data['description'] = trim(substr($data['symbol'], 4));
                $data['symbol'] = substr($data['symbol'], 0, 4);

                $result[] = $data;
            }

            return $result;
        }
    }
}
