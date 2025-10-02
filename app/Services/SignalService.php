<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Signal;
use Illuminate\Support\Facades\Http;

class SignalService
{
    public function populateAssets()
    {
        $cryptoSymbols = $this->fetchTopCryptoSymbols();
        $stockSymbols = $this->fetchTopStockSymbols();

        $allSymbols = array_merge($cryptoSymbols, $stockSymbols);

        \Log::info("Fetched " . count($cryptoSymbols) . " crypto symbols and " . count($stockSymbols) . " stock symbols. Total: " . count($allSymbols));

        $addedCount = 0;

        foreach ($allSymbols as $symbolData)
        {
            $symbol = $symbolData['symbol'];
            $market = $symbolData['market'];

            if (Asset::where('symbol', $symbol)->where('market', $market)->exists())
            {
                continue;
            }

            if ($market === 'crypto')
            {
                $candles = $this->getCandlesCrypto($symbol);
            }
            elseif ($market === 'stock')
            {
                $candles = $this->getCandlesStock($symbol);
            }
            else
            {
                continue;
            }

            if (empty($candles))
            {
                \Log::warning("No candles for {$symbol} ({$market})");
                continue;
            }

            $signal = $this->predict($candles, $market);
            if ($signal)
            {
                Asset::create([
                    'symbol' => $symbol,
                    'market' => $market,
                ]);

                $addedCount++;
                \Log::info("Added asset: {$symbol} ({$market}) - Gain: {$signal['gain']}%");
            }
        }

        \Log::info("Total assets added: {$addedCount}");
    }

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

                \Log::info("Signal created for {$asset->symbol}: Entry {$signal['entry']}, Target {$signal['target']}, SL {$signal['sl']}, Gain {$signal['gain']}%");
                $this->sendTelegram($asset->symbol, $signal);
            }
        }

        Signal::query()->delete();
        Asset::query()->delete();
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
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        libxml_use_internal_errors(true);

        if ($httpCode !== 200)
        {
            \Log::error('Yahoo Finance HTTP error', ['symbol' => $symbol, 'http_code' => $httpCode, 'response' => substr($response, 0, 500)]);
            return [];
        }

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
        if (! is_array($last) || ! isset($last['close']) || $last['close'] <= 0)
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

    private function fetchTopCryptoSymbols()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://api.binance.com/api/v3/ticker/24hr',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode !== 200)
        {
            \Log::error('Binance ticker API HTTP error', ['http_code' => $httpCode, 'response' => $response]);
            $fallbackCryptos = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT', 'XRPUSDT', 'SOLUSDT', 'DOTUSDT', 'DOGEUSDT', 'AVAXUSDT', 'LTCUSDT'];
            return collect($fallbackCryptos)->map(function ($symbol)
            {
                return [
                    'symbol' => $symbol,
                    'market' => 'crypto',
                ];
            })->toArray();
        }

        $data = json_decode($response, true);

        if (! is_array($data))
        {
            \Log::error('Binance ticker API error', ['data' => $data]);
            $fallbackCryptos = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT', 'XRPUSDT', 'SOLUSDT', 'DOTUSDT', 'DOGEUSDT', 'AVAXUSDT', 'LTCUSDT'];
            return collect($fallbackCryptos)->map(function ($symbol)
            {
                return [
                    'symbol' => $symbol,
                    'market' => 'crypto',
                ];
            })->toArray();
        }

        usort($data, function ($a, $b)
        {
            return (float) $b['quoteVolume'] <=> (float) $a['quoteVolume'];
        });

        return collect(array_slice($data, 0, 50))->map(function ($ticker)
        {
            if (!is_array($ticker) || !isset($ticker['symbol']))
            {
                return null;
            }
            return [
                'symbol' => $ticker['symbol'],
                'market' => 'crypto',
            ];
        })->filter()->toArray();
    }

    private function fetchTopStockSymbols()
    {
        $topIndonesianStocks = [
            'BBCA.JK',
            'BBRI.JK',
            'BMRI.JK',
            'BBNI.JK',
            'ASII.JK',
            'UNVR.JK',
            'TLKM.JK',
            'ANTM.JK',
            'ICBP.JK',
            'SMGR.JK',
            'CPIN.JK',
            'INDF.JK',
            'KLBF.JK',
            'HMSP.JK',
            'BRPT.JK',
            'ADRO.JK',
            'PTBA.JK',
            'ITMG.JK',
            'MAPI.JK',
            'JSMR.JK',
            'GGRM.JK',
            'INCO.JK',
            'MDKA.JK',
            'PGAS.JK',
            'TKIM.JK',
            'TOWR.JK',
            'WSKT.JK',
            'WIKA.JK',
            'WTON.JK',
            'EXCL.JK',
            'ISAT.JK',
            'FREN.JK',
            'MIKA.JK',
            'SCMA.JK',
            'SRIL.JK',
            'TINS.JK',
            'UNTR.JK',
            'WIIM.JK',
            'WOOD.JK',
            'ZINC.JK',
            'AKRA.JK',
            'APLN.JK',
            'BAPA.JK',
            'BATA.JK',
            'BTPN.JK',
            'CASS.JK',
            'DMAS.JK',
            'ELSA.JK',
            'EMTK.JK',
            'ENRG.JK'
        ];

        return collect($topIndonesianStocks)->map(function ($symbol)
        {
            return [
                'symbol' => $symbol,
                'market' => 'stock',
            ];
        })->toArray();
    }

    private function sendTelegram($symbol, $signal)
    {
        $isCrypto = str_contains($symbol, 'USDT');
        $currency = $isCrypto ? 'IDR' : 'IDR';

        $entry = $signal['entry'];
        $target = $signal['target'];
        $sl = $signal['sl'];

        if ($isCrypto)
        {
            $usdToIdr = $this->getUsdToIdrRate();
            $entry = round($entry * $usdToIdr, 0);
            $target = round($target * $usdToIdr, 0);
            $sl = round($sl * $usdToIdr, 0);
        }

        $msg  = "<code>";
        $msg .= "🚀 Signal " . $symbol . " 🚀\n";
        $msg .= "Entry    : " . number_format($entry, 0, ',', '.') . " " . $currency . "\n";
        $msg .= "Target   : " . number_format($target, 0, ',', '.') . " " . $currency . " (+" . $signal['gain'] . "%) ✅\n";
        $msg .= "Stop Loss: " . number_format($sl, 0, ',', '.') . " " . $currency . "\n";
        $msg .= "Reason   : " . $signal['reason'] . "\n";
        $msg .= "</code>";

        $url = 'https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/sendMessage';
        Http::post($url, [
            'chat_id'    => env('TELEGRAM_CHAT_ID'),
            'text'       => $msg,
            'parse_mode' => 'HTML'
        ]);
    }

    private function getUsdToIdrRate()
    {
        try
        {
            $response = Http::get('https://api.exchangerate-api.com/v4/latest/USD');
            if ($response->successful())
            {
                $data = $response->json();
                return $data['rates']['IDR'] ?? 15000;
            }
        }
        catch (\Exception $e)
        {
            \Log::error('Exchange rate API error', ['error' => $e->getMessage()]);
        }
    }
}
