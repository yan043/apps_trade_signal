<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function getStockData()
    {
        $stock_top_volume_for_buy = $this->stock_top_volume_for_buy();

        $stock_technical_analysis = $this->stock_technical_analysis();

        $lastUpdated = now()->format('Y-m-d H:i:s');

        return response()->json([
            'stock_top_volume_for_buy' => $stock_top_volume_for_buy,
            'stock_technical_analysis' => $stock_technical_analysis,
            'last_updated'             => $lastUpdated,
            'payload' => [
                'top_volume' => $stock_top_volume_for_buy,
                'technical_analysis' => $stock_technical_analysis,
                'counts' => [
                    'top_volume' => is_array($stock_top_volume_for_buy) ? count($stock_top_volume_for_buy) : 0,
                    'technical_analysis' => is_array($stock_technical_analysis) ? count($stock_technical_analysis) : 0,
                ],
                'last_updated' => $lastUpdated,
            ],
        ]);
    }

    private function stock_top_volume_for_buy()
    {
        $results = [];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://scanner.tradingview.com/indonesia/scan?label-product=markets-screener',
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
                    "change",
                    "volume",
                    "relative_volume_10d_calc",
                    "market_cap_basic",
                    "fundamental_currency_code",
                    "price_earnings_ttm",
                    "earnings_per_share_diluted_ttm",
                    "earnings_per_share_diluted_yoy_growth_ttm",
                    "dividends_yield_current",
                    "sector.tr",
                    "market",
                    "sector",
                    "AnalystRating",
                    "AnalystRating.tr"
                ],
                "ignore_unknown_fields": false,
                "options": { "lang": "id_ID" },
                "range": [0, 10],
                "sort": { "sortBy": "volume", "sortOrder": "desc" },
                "preset": "all_stocks"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $result = json_decode($response, true);

        if ($result && isset($result['totalCount']))
        {
            if ($result['totalCount'] > 0)
            {
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://scanner.tradingview.com/indonesia/scan?label-product=markets-screener',
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
                            "change",
                            "volume",
                            "relative_volume_10d_calc",
                            "market_cap_basic",
                            "fundamental_currency_code",
                            "price_earnings_ttm",
                            "earnings_per_share_diluted_ttm",
                            "earnings_per_share_diluted_yoy_growth_ttm",
                            "dividends_yield_current",
                            "sector.tr",
                            "market",
                            "sector",
                            "AnalystRating",
                            "AnalystRating.tr"
                        ],
                        "ignore_unknown_fields": false,
                        "options": { "lang": "id_ID" },
                        "range": [0, ' . $result['totalCount'] . '],
                        "sort": { "sortBy": "volume", "sortOrder": "desc" },
                        "preset": "all_stocks"
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
                    if (in_array($item['d'][24], ['Buy', 'StrongBuy']))
                    {
                        $logo = 'https://s3-symbol-logo.tradingview.com/' . $item['d'][2] . '.svg';

                        $name = $item['d'][0];

                        $description = $item['d'][1];

                        $close = rtrim(rtrim(number_format($item['d'][6], 2, '.', ''), '0'), '.');

                        $currency = $item['d'][11];

                        if ($item['d'][12] >= 0)
                        {
                            $change = '+' . number_format($item['d'][12], 2) . '%';
                        }
                        else
                        {
                            $change = number_format($item['d'][12], 2) . '%';
                        }

                        if ($item['d'][13] >= 1000000000000)
                        {
                            $value = number_format($item['d'][13] / 1000000000000, 2) . ' T';
                        }
                        elseif ($item['d'][13] >= 1000000000)
                        {
                            $value = number_format($item['d'][13] / 1000000000, 2) . ' B';
                        }
                        elseif ($item['d'][13] >= 1000000)
                        {
                            $value = number_format($item['d'][13] / 1000000, 2) . ' M';
                        }
                        elseif ($item['d'][13] >= 1000)
                        {
                            $value = number_format($item['d'][13] / 1000, 2) . ' K';
                        }
                        else
                        {
                            $value = number_format($item['d'][13], 2);
                        }

                        if ($item['d'][24] === 'StrongBuy')
                        {
                            $analystRating = 'Strong Buy';
                        }
                        else
                        {
                            $analystRating = $item['d'][24];
                        }

                        $results[] = [
                            'logo'          => $logo,
                            'name'          => $name,
                            'description'   => $description,
                            'close'         => $close,
                            'currency'      => $currency,
                            'change'        => $change,
                            'value'         => $value,
                            'analystRating' => $analystRating,
                        ];
                    }
                }

                return $results;
            }
        }

        return $results;
    }

    private function stock_technical_analysis()
    {
        $results = [];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://scanner.tradingview.com/indonesia/scan?label-product=markets-screener',
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
                    "TechRating_1D",
                    "TechRating_1D.tr",
                    "MARating_1D",
                    "MARating_1D.tr",
                    "OsRating_1D",
                    "OsRating_1D.tr",
                    "RSI",
                    "Mom",
                    "pricescale",
                    "minmov",
                    "fractional",
                    "minmove2",
                    "AO",
                    "CCI20",
                    "Stoch.K",
                    "Stoch.D",
                    "MACD.macd",
                    "MACD.signal"
                ],
                "ignore_unknown_fields": false,
                "options": { "lang": "id_ID" },
                "range": [0, 10],
                "sort": { "sortBy": "Recommend.Other", "sortOrder": "asc" },
                "preset": "all_stocks"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $result = json_decode($response, true);

        if ($result && isset($result['totalCount']))
        {
            if ($result['totalCount'] > 0)
            {
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://scanner.tradingview.com/indonesia/scan?label-product=markets-screener',
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
                            "TechRating_1D",
                            "TechRating_1D.tr",
                            "MARating_1D",
                            "MARating_1D.tr",
                            "OsRating_1D",
                            "OsRating_1D.tr",
                            "RSI",
                            "Mom",
                            "pricescale",
                            "minmov",
                            "fractional",
                            "minmove2",
                            "AO",
                            "CCI20",
                            "Stoch.K",
                            "Stoch.D",
                            "MACD.macd",
                            "MACD.signal"
                        ],
                        "ignore_unknown_fields": false,
                        "options": { "lang": "id_ID" },
                        "range": [0, ' . $result['totalCount'] . '],
                        "sort": { "sortBy": "Recommend.Other", "sortOrder": "asc" },
                        "preset": "all_stocks"
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
                    if (($item['d'][6] === 'StrongBuy' && $item['d'][8] === 'StrongBuy' && $item['d'][10] === 'Buy')
                        || ($item['d'][6] === 'Buy' && $item['d'][8] === 'StrongBuy' && $item['d'][10] === 'StrongBuy')
                    )
                    {
                        $logo = 'https://s3-symbol-logo.tradingview.com/' . $item['d'][2] . '.svg';

                        $name = $item['d'][0];

                        $description = $item['d'][1];

                        if ($item['d'][6] === 'StrongBuy')
                        {
                            $techRating_1D = 'Strong Buy';
                        }
                        else
                        {
                            $techRating_1D = $item['d'][6];
                        }

                        if ($item['d'][8] === 'StrongBuy')
                        {
                            $maRating_1D = 'Strong Buy';
                        }
                        else
                        {
                            $maRating_1D = $item['d'][8];
                        }

                        if ($item['d'][10] === 'StrongBuy')
                        {
                            $osRating_1D = 'Strong Buy';
                        }
                        else
                        {
                            $osRating_1D = $item['d'][10];
                        }

                        $results[] = [
                            'logo'          => $logo,
                            'name'          => $name,
                            'description'   => $description,
                            'techRating_1D' => $techRating_1D,
                            'maRating_1D'   => $maRating_1D,
                            'osRating_1D'   => $osRating_1D,
                        ];
                    }
                }

                return $results;
            }
        }

        return $results;
    }
}
