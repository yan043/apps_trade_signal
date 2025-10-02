<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Signal;
use Illuminate\Support\Facades\Http;

class SignalService
{
    public function analyze()
    {
        $assets = Asset::all();

        foreach ($assets as $asset)
        {
            if ($asset->market === 'crypto')
            {
                $candles = $this->getCandlesCrypto($asset->symbol);
            }
            elseif ($asset->market === 'stock')
            {
                $candles = $this->getCandlesStock($asset->symbol);
            }
            else
            {
                continue;
            }

            if (empty($candles))
            {
                \Log::warning("No candle data for {$asset->symbol}");

                continue;
            }

            $signal = $this->predict($candles, $asset->market);
            if ($signal)
            {
                Signal::create([
                    'asset_id'      => $asset->id,
                    'entry_price'   => $signal['entry'],
                    'target_price'  => $signal['target'],
                    'stop_loss'     => $signal['sl'],
                    'expected_gain' => $signal['gain'],
                    'reason'        => $signal['reason'],
                ]);

                $this->sendTelegram($asset->symbol, $signal);
            }
        }
    }

    private function getCandlesCrypto($symbol)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://api.binance.me/api/v3/klines?symbol=' . $symbol . '&interval=1d&limit=50',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        libxml_use_internal_errors(true);

        $data = json_decode($response, true);

        if (! is_array($data) || isset($data['code']))
        {
            \Log::error('Binance API error', ['symbol' => $symbol, 'data' => $data]);

            return [];
        }

        return collect($data)->map(function ($c)
        {
            if (! is_array($c) || count($c) < 6)
            {
                return null;
            }

            return [
                'open'   => (float) $c[1],
                'high'   => (float) $c[2],
                'low'    => (float) $c[3],
                'close'  => (float) $c[4],
                'volume' => (float) $c[5],
            ];
        })->filter()->values()->toArray();
    }

    private function getCandlesStock($symbol)
    {
        sleep(1);

        ini_set('memory_limit', '-1');
        ini_set("max_execution_time", "-1");

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://query1.finance.yahoo.com/v8/finance/chart/' . $symbol . '?interval=1d&range=6mo',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        libxml_use_internal_errors(true);

        $data = json_decode($response, true);

        if (! isset($data['chart']['result'][0]))
        {
            \Log::error('Yahoo Finance error', ['symbol' => $symbol, 'data' => $data]);

            return [];
        }

        $result     = $data['chart']['result'][0];
        $timestamps = $result['timestamp']              ?? [];
        $quote      = $result['indicators']['quote'][0] ?? [];

        return collect($timestamps)->map(function ($ts, $i) use ($quote)
        {
            if (
                ! is_array($quote['open']) || ! is_array($quote['high']) || ! is_array($quote['low']) || ! is_array($quote['close']) ||
                ! isset($quote['open'][$i], $quote['close'][$i])
            )
            {
                return null;
            }

            return [
                'open'   => $quote['open'][$i],
                'high'   => $quote['high'][$i],
                'low'    => $quote['low'][$i],
                'close'  => $quote['close'][$i],
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

        $last   = end($candles);
        if (! is_array($last) || ! isset($last['close']))
        {
            return null;
        }
        $closes = array_column($candles, 'close');
        $ma20   = array_sum(array_slice($closes, -20)) / 20;
        $atr    = $this->calcATR($candles, 14);

        $multiplier = $market === 'crypto' ? 2.5 : 1.2;
        $target     = $last['close'] + ($atr * $multiplier);
        $gain       = (($target - $last['close']) / $last['close']) * 100;

        if (($market === 'crypto' && $gain >= 10) || ($market === 'stock' && $gain >= 3))
        {
            return [
                'entry'  => $last['close'],
                'target' => round($target, 2),
                'sl'     => round($last['close'] - ($atr * 1.2), 2),
                'gain'   => round($gain, 2),
                'reason' => 'Breakout + Volume + ATR',
            ];
        }

        return null;
    }

    private function calcATR($candles, $period = 14)
    {
        $trs = [];
        for ($i = 1; $i < count($candles); $i++)
        {
            if (
                ! is_array($candles[$i])     || ! isset($candles[$i]['high'], $candles[$i]['low']) ||
                ! is_array($candles[$i - 1]) || ! isset($candles[$i - 1]['close'])
            )
            {
                continue;
            }
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

    private function sendTelegram($symbol, $signal)
    {
        $msg  = "<code>";
        $msg .= "🚀 Signal " . $symbol . " 🚀\n";
        $msg .= "Entry    : " . $signal['entry'] . "\n";
        $msg .= "Target   : " . $signal['target'] . " (+" . $signal['gain'] . "%) ✅\n";
        $msg .= "Stop Loss: " . $signal['sl'] . "\n";
        $msg .= "Reason   : " . $signal['reason'] . "\n";
        $msg .= "</code>";

        $url = 'https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/sendMessage';
        Http::post($url, [
            'chat_id'    => env('TELEGRAM_CHAT_ID'),
            'text'       => $msg,
            'parse_mode' => 'HTML'
        ]);
    }
}
