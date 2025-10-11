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
        $assets = array_merge($this->fetchTopCryptoSymbols(), $this->fetchTopStockSymbols());

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

    private function calculateExpiredAt($market)
    {
        $now = Carbon::now();
        $now->add($market === 'crypto' ? '1 day' : '7 days');
        return $now->toDateTimeString();
    }

    private function getCandlesCrypto($symbol, $interval = '1d', $limit = 50)
    {
        $url = $this->binanceBaseUrl . '/api/v3/klines';
        $params = ['symbol' => $symbol, 'interval' => $interval, 'limit' => $limit];
        $response = Http::get($url, $params);

        if ($response->failed()) return [];

        $data = $response->json();
        return array_map(fn($c) => [
            'open'   => (float) $c[1],
            'high'   => (float) $c[2],
            'low'    => (float) $c[3],
            'close'  => (float) $c[4],
            'volume' => (float) $c[5],
        ], $data);
    }

    private function getCandlesStock($symbol, $interval = '1d', $range = '6mo')
    {
        $url = $this->yahooFinanceBaseUrl . $symbol . "?interval={$interval}&range={$range}";
        $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);

        if ($response->failed()) return [];

        $data = $response->json();
        $result = $data['chart']['result'][0] ?? null;
        if (!$result || !isset($result['timestamp'])) return [];

        $timestamps = $result['timestamp'];
        $quote      = $result['indicators']['quote'][0];

        return collect($timestamps)->map(fn($ts, $i) => [
            'open'   => $quote['open'][$i] ?? null,
            'high'   => $quote['high'][$i] ?? null,
            'low'    => $quote['low'][$i] ?? null,
            'close'  => $quote['close'][$i] ?? null,
            'volume' => $quote['volume'][$i] ?? 0,
        ])->filter()->values()->toArray();
    }

    private function predict($candles, $market)
    {
        if (count($candles) < 20) return null;

        $last = end($candles);
        if (!isset($last['close']) || $last['close'] <= 0) return null;

        $closes = array_column($candles, 'close');
        $atr = $this->calcATR($candles, 14);
        if ($atr <= 0) return null;

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

        if (count($trs) < $period) return 0;
        return array_sum(array_slice($trs, -$period)) / $period;
    }

    private function fetchTopCryptoSymbols()
    {
        $response = Http::get($this->binanceBaseUrl . '/api/v3/ticker/24hr');
        if ($response->failed()) return [];

        $data = $response->json();
        usort($data, fn($a, $b) => (float) $b['quoteVolume'] <=> (float) $a['quoteVolume']);

        return collect(array_slice($data, 0, 50))->map(fn($ticker) => [
            'symbol' => $ticker['symbol'],
            'market' => 'crypto',
        ])->toArray();
    }

    private function fetchTopStockSymbols()
    {
        return collect([
            'BBCA.JK',
            'TLKM.JK',
            'UNVR.JK',
            'ASII.JK',
            'BMRI.JK',
            'BBRI.JK',
            'HMSP.JK',
            'ICBP.JK',
            'PGAS.JK',
            'GGRM.JK',
            'AALI.JK',
            'ANTM.JK',
            'INDF.JK',
            'KLBF.JK',
            'LSIP.JK',
            'MNCN.JK',
            'PTBA.JK',
            'SMGR.JK',
            'UNTR.JK',
            'TPIA.JK',
            'ADRO.JK',
            'AKRA.JK',
            'ERAA.JK',
            'EXCL.JK',
            'INCO.JK',
            'JSMR.JK',
            'SIDO.JK',
            'TBIG.JK',
            'MIKA.JK',
            'BRPT.JK',
            'BNGA.JK',
            'BBHI.JK',
            'BSDE.JK',
            'CPIN.JK',
            'MDKA.JK',
            'WIKA.JK',
            'SRIL.JK',
            'ISAT.JK',
            'MAPI.JK',
            'MIDI.JK',
            'PWON.JK',
            'MYOR.JK',
            'SCMA.JK',
            'LPKR.JK',
            'CMPP.JK',
            'TOWR.JK',
            'LINK.JK',
            'MAPA.JK',
            'KAEF.JK',
            'NIKL.JK',
            'PTPP.JK',
            'TINS.JK',
            'ACES.JK',
            'KIJA.JK',
            'LPPF.JK',
            'ADHI.JK',
            'HOKI.JK',
            'FREN.JK',
            'GOTO.JK',
            'ERTX.JK',
            'KINO.JK',
            'WTON.JK',
            'KBLV.JK',
            'ADMF.JK',
            'INAF.JK',
            'BRMS.JK',
            'TBLA.JK',
            'SMRA.JK',
            'HRUM.JK',
            'MDLN.JK',
            'SCBD.JK',
            'TGRA.JK',
            'SRAP.JK',
            'CPRO.JK',
            'BIPI.JK',
            'AISA.JK',
            'MEDC.JK',
            'TRIM.JK',
            'UNSP.JK',
            'INTP.JK',
            'BFIN.JK',
            'BUDI.JK',
            'RALS.JK',
            'SRSN.JK',
            'TMAS.JK',
            'TMPI.JK',
            'PMJS.JK',
            'BKSL.JK',
            'FPNI.JK',
            'PPLI.JK',
            'JAWA.JK',
            'LION.JK',
            'ELSA.JK',
            'NRCA.JK',
            'AKPI.JK',
            'BBMD.JK',
            'EJIP.JK',
            'INAF.JK',
            'LTLS.JK',
            'MBAP.JK'
        ])->map(fn($symbol) => ['symbol' => $symbol, 'market' => 'stock'])->toArray();
    }
}
