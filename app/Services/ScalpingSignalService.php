<?php

namespace App\Services;

use Carbon\Carbon;
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
                'open_time' => Carbon::createFromTimestampMs($candle[0]),
                'open'      => (float)$candle[1],
                'high'      => (float)$candle[2],
                'low'       => (float)$candle[3],
                'close'     => (float)$candle[4],
                'volume'    => (float)$candle[5],
            ];
        }, $data);
    }

    private function ema(array $values, int $period)
    {
        if (count($values) < $period) return null;

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
        if (count($closes) < $period + 1) return null;

        $gains = $losses = 0;

        for ($i = 1; $i <= $period; $i++)
        {
            $diff = $closes[$i] - $closes[$i - 1];
            if ($diff > 0) $gains += $diff;
            else $losses -= $diff;
        }

        if ($losses == 0) return 100;

        $rs = ($gains / $period) / ($losses / $period);

        return 100 - (100 / (1 + $rs));
    }

    private function calculateATR(array $candles, int $period = 14)
    {
        if (count($candles) < $period + 1) return null;

        $trs = [];
        for ($i = 1; $i < count($candles); $i++)
        {
            $high = $candles[$i]['high'];
            $low  = $candles[$i]['low'];
            $closePrev = $candles[$i - 1]['close'];
            $trs[] = max([$high - $low, abs($high - $closePrev), abs($low - $closePrev)]);
        }

        return array_sum(array_slice($trs, -$period)) / $period;
    }

    public function analyzeScalpingBatch(array $symbols)
    {
        $results = [];

        foreach ($symbols as $symbol)
        {
            $signal = $this->analyzeScalping($symbol);
            if ($signal !== null && $signal['action'] !== 'HOLD')
            {
                $results[] = $signal;
            }
        }

        usort($results, function ($a, $b)
        {
            if ($a['action'] === 'BUY' && $b['action'] === 'SELL') return -1;
            if ($a['action'] === 'SELL' && $b['action'] === 'BUY') return 1;

            $aStrength = ($a['action'] === 'BUY') ? abs($a['rsi'] - 52.5) : abs($a['rsi'] - 47.5);
            $bStrength = ($b['action'] === 'BUY') ? abs($b['rsi'] - 52.5) : abs($b['rsi'] - 47.5);

            return $aStrength <=> $bStrength;
        });

        return $results;
    }

    public function analyzeScalping($symbol)
    {
        $candles = $this->getCandlesCrypto($symbol, '5m', 100);
        if (count($candles) < 30) return null;

        $closes = array_column($candles, 'close');
        $ema9  = $this->ema(array_slice($closes, -30), 9);
        $ema21 = $this->ema(array_slice($closes, -30), 21);
        $rsi   = $this->rsi(array_slice($closes, -15), 14);
        $lastClose = end($closes);

        $signal = [
            'symbol'     => $symbol,
            'price'      => $lastClose,
            'ema9'       => $ema9,
            'ema21'      => $ema21,
            'rsi'        => $rsi,
            'action'     => 'HOLD',
            'tp1'        => null,
            'tp2'        => null,
            'tp3'        => null,
            'sl'         => null,
        ];

        $fee       = 0.001;
        $slippage  = 0.001;
        $breakEven = ($fee * 2) + $slippage;
        $defaultTpPercent = $breakEven + 0.01;

        $atr = $this->calculateATR($candles, 14);
        $atrPercent = $atr ? $atr / $lastClose : 0.01;
        $slPercent = max($atrPercent, 0.01);

        if ($ema9 > $ema21 && $rsi !== null && $rsi > 40 && $rsi < 65)
        {
            $signal['action'] = 'BUY';
            $signal['tp1'] = $lastClose * (1 + 0.015);
            $signal['tp2'] = $lastClose * (1 + 0.03);
            $signal['tp3'] = $lastClose * (1 + $defaultTpPercent);
            $signal['sl']  = $lastClose * (1 - $slPercent);
        }
        elseif ($ema9 < $ema21 && $rsi !== null && $rsi > 35 && $rsi < 60)
        {
            $signal['action'] = 'SELL';
            $signal['tp1'] = $lastClose * (1 - 0.015);
            $signal['tp2'] = $lastClose * (1 - 0.03);
            $signal['tp3'] = $lastClose * (1 - $defaultTpPercent);
            $signal['sl']  = $lastClose * (1 + $slPercent);
        }

        $signal['tp1_percentage'] = isset($signal['tp1']) ? (($signal['tp1'] - $lastClose) / $lastClose) * 100 : null;
        $signal['tp2_percentage'] = isset($signal['tp2']) ? (($signal['tp2'] - $lastClose) / $lastClose) * 100 : null;
        $signal['tp3_percentage'] = isset($signal['tp3']) ? (($signal['tp3'] - $lastClose) / $lastClose) * 100 : null;
        $signal['sl_percentage']  = isset($signal['sl'])  ? (($signal['sl'] - $lastClose) / $lastClose) * 100 : null;

        return $signal;
    }
}
