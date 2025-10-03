<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ScalpingSignalService
{
    protected $binanceBaseUrl = 'https://api.binance.me';

    public function getCandlesCrypto($symbol, $interval = '5m', $limit = 100)
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
                'open_time'  => Carbon::createFromTimestampMs($candle[0]),
                'open'       => (float)$candle[1],
                'high'       => (float)$candle[2],
                'low'        => (float)$candle[3],
                'close'      => (float)$candle[4],
                'volume'     => (float)$candle[5],
            ];
        }, $data);
    }

    private function ema(array $values, int $period)
    {
        if (count($values) < $period)
        {
            return null;
        }

        $k = 2 / ($period + 1);
        $ema = $values[0];

        foreach (array_slice($values, 1) as $price)
        {
            $ema = ($price - $ema) * $k + $ema;
        }

        return $ema;
    }

    private function rsi(array $closes, int $period = 14)
    {
        if (count($closes) < $period + 1)
        {
            return null;
        }

        $gains = 0;
        $losses = 0;

        for ($i = 1; $i <= $period; $i++)
        {
            $diff = $closes[$i] - $closes[$i - 1];
            if ($diff > 0)
            {
                $gains += $diff;
            }
            else
            {
                $losses -= $diff;
            }
        }

        if ($losses == 0)
        {
            return 100;
        }

        $rs = ($gains / $period) / ($losses / $period);

        return 100 - (100 / (1 + $rs));
    }

    public function analyzeScalping($symbol = 'BTCUSDT')
    {
        $candles = $this->getCandlesCrypto($symbol, '5m', 100);

        if (count($candles) < 30)
        {
            return null;
        }

        $closes = array_column($candles, 'close');

        $ema9  = $this->ema(array_slice($closes, -30), 9);
        $ema21 = $this->ema(array_slice($closes, -30), 21);

        $rsi = $this->rsi(array_slice($closes, -15), 14);

        $lastClose = end($closes);

        $signal = [
            'symbol'     => $symbol,
            'price'      => $lastClose,
            'ema9'       => $ema9,
            'ema21'      => $ema21,
            'rsi'        => $rsi,
            'action'     => 'HOLD',
            'tp'         => null,
            'sl'         => null,
            'created_at' => Carbon::now(),
        ];

        if ($ema9 > $ema21 && $rsi !== null && $rsi > 40 && $rsi < 65)
        {
            $signal['action'] = 'BUY';
            $signal['tp']     = $lastClose * 1.005;
            $signal['sl']     = $lastClose * 0.997;
        }

        if ($ema9 < $ema21 && $rsi !== null && $rsi > 35 && $rsi < 60)
        {
            $signal['action'] = 'SELL';
            $signal['tp']     = $lastClose * 0.995;
            $signal['sl']     = $lastClose * 1.003;
        }

        return $signal;
    }
}
