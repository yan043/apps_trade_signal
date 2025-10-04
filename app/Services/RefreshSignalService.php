<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class RefreshSignalService
{
    protected $binanceBaseUrl = 'https://api.binance.me';
    protected $yahooFinanceBaseUrl = 'https://query1.finance.yahoo.com/v8/finance/chart/';

    public function refreshSignals()
    {
        $assets = $this->getTopAssets();

        $cryptoSignals = [];
        $stockSignals  = [];

        foreach ($assets as $asset)
        {
            $candles = $asset['market'] === 'crypto'
                ? $this->getCandlesCrypto($asset['symbol'])
                : $this->getCandlesStock($asset['symbol']);

            if (empty($candles)) continue;

            $signal = $this->predict($candles, $asset['market']);
            if (!$signal) continue;

            // Mapping ke object agar blade & ajax compatible
            $signalMapped = (object) [
                'asset' => (object) [
                    'symbol' => $asset['symbol'],
                    'market' => $asset['market'],
                ],
                'entry_price'     => $signal['entry'] ?? null,
                'target_price'    => $signal['target'] ?? null,
                'target_price_2'  => $signal['target_2'] ?? null,
                'target_price_3'  => $signal['target_3'] ?? null,
                'stop_loss'       => $signal['sl'] ?? null,
                'expected_gain'   => $signal['gain'] ?? null,
                'expected_gain_2' => $signal['gain_2'] ?? null,
                'expected_gain_3' => $signal['gain_3'] ?? null,
                'expired_at'      => $this->calculateExpiredAt($asset['market']),
            ];

            if ($asset['market'] === 'crypto')
            {
                $cryptoSignals[] = $signalMapped;
            }
            else
            {
                $stockSignals[] = $signalMapped;
            }
        }

        usort($cryptoSignals, fn($a, $b) => $b->expected_gain <=> $a->expected_gain);
        usort($stockSignals, fn($a, $b) => $b->expected_gain <=> $a->expected_gain);

        return [
            'crypto' => array_slice($cryptoSignals, 0, 30),
            'stock'  => array_slice($stockSignals, 0, 30),
        ];
    }

    private function getTopAssets()
    {
        return array_merge($this->fetchTopCryptoSymbols(), $this->fetchTopStockSymbols());
    }

    private function calculateExpiredAt($market)
    {
        $now = Carbon::now();
        $now->add($market === 'crypto' ? '1 day' : '7 days');
        return $now->toDateTimeString();
    }

    private function getCandlesCrypto($symbol, $interval = '1d', $limit = 50)
    {
        $url = $this->binanceBaseUrl . '/api/v3/klines';
        $params = [
            'symbol'   => $symbol,
            'interval' => $interval,
            'limit'    => $limit,
        ];

        $response = Http::get($url, $params);

        if ($response->failed())
        {
            return [];
        }

        $data = $response->json();

        return array_map(function ($candle)
        {
            return [
                'open'   => (float) $candle[1],
                'high'   => (float) $candle[2],
                'low'    => (float) $candle[3],
                'close'  => (float) $candle[4],
                'volume' => (float) $candle[5],
            ];
        }, $data);
    }

    private function getCandlesStock($symbol, $interval = '1d', $range = '6mo')
    {
        $url = $this->yahooFinanceBaseUrl . $symbol . "?interval={$interval}&range={$range}";
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0'
        ])->get($url);

        if ($response->failed())
        {
            return [];
        }

        $data = $response->json();
        $result = $data['chart']['result'][0] ?? null;

        if (!$result || !isset($result['timestamp']))
        {
            return [];
        }

        $timestamps = $result['timestamp'];
        $quote      = $result['indicators']['quote'][0];

        return collect($timestamps)->map(function ($ts, $i) use ($quote)
        {
            return [
                'open'   => $quote['open'][$i] ?? null,
                'high'   => $quote['high'][$i] ?? null,
                'low'    => $quote['low'][$i] ?? null,
                'close'  => $quote['close'][$i] ?? null,
                'volume' => $quote['volume'][$i] ?? 0,
            ];
        })->filter()->values()->toArray();
    }

    private function predict($candles, $market)
    {
        if (count($candles) < 20)
        {
            return null;
        }

        $last = end($candles);
        if (!isset($last['close']) || $last['close'] <= 0)
        {
            return null;
        }

        $closes = array_column($candles, 'close');
        $ma20   = array_sum(array_slice($closes, -20)) / 20;
        $atr    = $this->calcATR($candles, 14);

        if ($atr <= 0)
        {
            return null;
        }

        $multiplier = $market === 'crypto' ? 2.5 : 1.2;

        $target1 = $last['close'] + ($atr * $multiplier);
        $target2 = $last['close'] + ($atr * $multiplier * 1.5);
        $target3 = $last['close'] + ($atr * $multiplier * 2);

        $gain1 = (($target1 - $last['close']) / $last['close']) * 100;
        $gain2 = (($target2 - $last['close']) / $last['close']) * 100;
        $gain3 = (($target3 - $last['close']) / $last['close']) * 100;

        if (($market === 'crypto' && $gain1 >= 5) || ($market === 'stock' && $gain1 >= 10))
        {
            return [
                'entry'    => $last['close'],
                'target'   => round($target1, 2),
                'target_2' => round($target2, 2),
                'target_3' => round($target3, 2),
                'sl'       => round($last['close'] - ($atr * 1.2), 2),
                'gain'     => round($gain1, 2),
                'gain_2'   => round($gain2, 2),
                'gain_3'   => round($gain3, 2),
            ];
        }

        return null;
    }

    private function calcATR($candles, $period = 14)
    {
        $trs = [];
        for ($i = 1; $i < count($candles); $i++)
        {
            $h  = $candles[$i]['high'];
            $l  = $candles[$i]['low'];
            $pc = $candles[$i - 1]['close'];

            $trs[] = max([$h - $l, abs($h - $pc), abs($l - $pc)]);
        }

        if (count($trs) < $period)
        {
            return 0;
        }

        return array_sum(array_slice($trs, -$period)) / $period;
    }

    private function fetchTopCryptoSymbols()
    {
        $response = Http::get($this->binanceBaseUrl . '/api/v3/ticker/24hr');

        if ($response->failed())
        {
            return [];
        }

        $data = $response->json();

        usort($data, function ($a, $b)
        {
            return (float) $b['quoteVolume'] <=> (float) $a['quoteVolume'];
        });

        return collect(array_slice($data, 0, 50))->map(function ($ticker)
        {
            return [
                'symbol' => $ticker['symbol'],
                'market' => 'crypto',
            ];
        })->toArray();
    }

    private function fetchTopStockSymbols()
    {
        $stocks = [
            'BBCA.JK',
            'TLKM.JK',
            'BMRI.JK',
            'ASII.JK',
            'UNVR.JK',
            'GGRM.JK',
            'ICBP.JK',
            'HMSP.JK',
            'INDF.JK',
            'ADRO.JK'
        ];

        return collect($stocks)->map(function ($symbol)
        {
            return [
                'symbol' => $symbol,
                'market' => 'stock',
            ];
        })->toArray();
    }
}
