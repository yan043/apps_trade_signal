<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Signal;
use Illuminate\Support\Facades\DB;

class SignalService
{
    public function populateAssets()
    {
        DB::table('signals')->delete();
        DB::table('assets')->delete();

        $cryptoSymbols = $this->fetchTopCryptoSymbols();
        $stockSymbols = $this->fetchTopStockSymbols();

        $allSymbols = array_merge($cryptoSymbols, $stockSymbols);

        \Log::info("Fetched " . count($cryptoSymbols) . " crypto symbols and " . count($stockSymbols) . " stock symbols. Total: " . count($allSymbols));

        $addedCount = 0;

        foreach ($allSymbols as $symbolData)
        {
            $symbol = $symbolData['symbol'];
            $market = $symbolData['market'];

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

        $cryptoSignals = [];
        $stockSignals = [];

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
                $expiredAt = $this->calculateExpiredAt($asset->market);
                $signal['expired_at'] = $expiredAt;

                if ($asset->market === 'crypto')
                {
                    $cryptoSignals[] = ['asset' => $asset, 'signal' => $signal];
                }
                else
                {
                    $stockSignals[] = ['asset' => $asset, 'signal' => $signal];
                }
            }
        }

        usort($cryptoSignals, function ($a, $b)
        {
            return $b['signal']['gain'] <=> $a['signal']['gain'];
        });
        $cryptoSignals = array_slice($cryptoSignals, 0, 15);

        usort($stockSignals, function ($a, $b)
        {
            return $b['signal']['gain'] <=> $a['signal']['gain'];
        });
        $stockSignals = array_slice($stockSignals, 0, 15);

        foreach ($cryptoSignals as $item)
        {
            $asset = $item['asset'];
            $signal = $item['signal'];

            Signal::create([
                'asset_id'        => $asset->id,
                'entry_price'     => $signal['entry'],
                'target_price'    => $signal['target'],
                'target_price_2'  => $signal['target_2'],
                'target_price_3'  => $signal['target_3'],
                'stop_loss'       => $signal['sl'],
                'expected_gain'   => $signal['gain'],
                'expected_gain_2' => $signal['gain_2'],
                'expected_gain_3' => $signal['gain_3'],
                'expired_at'      => $signal['expired_at'],
            ]);

            \Log::info("Signal created for {$asset->symbol}: Entry {$signal['entry']}, Target {$signal['target']}, SL {$signal['sl']}, Gain {$signal['gain']}%");
        }

        foreach ($stockSignals as $item)
        {
            $asset = $item['asset'];
            $signal = $item['signal'];

            Signal::create([
                'asset_id'        => $asset->id,
                'entry_price'     => $signal['entry'],
                'target_price'    => $signal['target'],
                'target_price_2'  => $signal['target_2'],
                'target_price_3'  => $signal['target_3'],
                'stop_loss'       => $signal['sl'],
                'expected_gain'   => $signal['gain'],
                'expected_gain_2' => $signal['gain_2'],
                'expected_gain_3' => $signal['gain_3'],
                'expired_at'      => $signal['expired_at'],
            ]);

            \Log::info("Signal created for {$asset->symbol}: Entry {$signal['entry']}, Target {$signal['target']}, SL {$signal['sl']}, Gain {$signal['gain']}%");
        }
    }

    private function calculateExpiredAt($market)
    {
        $now = new \DateTime();

        if ($market === 'crypto')
        {
            $now->modify('+1 day');
        }
        elseif ($market === 'stock')
        {
            $now->modify('+7 days');
        }
        else
        {
            $now->modify('+1 day');
        }

        return $now->format('Y-m-d H:i:s');
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

        $target1 = $last['close'] + ($atr * $multiplier);
        $target2 = $last['close'] + ($atr * $multiplier * 1.5);
        $target3 = $last['close'] + ($atr * $multiplier * 2);

        $gain1 = (($target1 - $last['close']) / $last['close']) * 100;
        $gain2 = (($target2 - $last['close']) / $last['close']) * 100;
        $gain3 = (($target3 - $last['close']) / $last['close']) * 100;

        if (($market === 'crypto' && $gain1 >= 5) || ($market === 'stock' && $gain1 >= 10))
        {
            return [
                'entry'        => $last['close'],
                'target'       => round($target1, 2),
                'target_2'     => round($target2, 2),
                'target_3'     => round($target3, 2),
                'sl'           => round($last['close'] - ($atr * 1.2), 2),
                'gain'         => round($gain1, 2),
                'gain_2'       => round($gain2, 2),
                'gain_3'       => round($gain3, 2),
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

    public function fetchTopCryptoSymbols()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://api.binance.me/api/v3/ticker/24hr',
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
            return [];
        }

        $data = json_decode($response, true);

        if (! is_array($data))
        {
            \Log::error('Binance ticker API error', ['data' => $data]);
            return [];
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
        $indonesianStocks = [
            'AALI.JK',
            'ABBA.JK',
            'ABDA.JK',
            'ABMM.JK',
            'ACES.JK',
            'ACST.JK',
            'ADES.JK',
            'ADHI.JK',
            'ADMF.JK',
            'ADMG.JK',
            'ADRO.JK',
            'AGII.JK',
            'AGRO.JK',
            'AGRS.JK',
            'AHAP.JK',
            'AIMS.JK',
            'AISA.JK',
            'AKKU.JK',
            'AKPI.JK',
            'AKRA.JK',
            'AKSI.JK',
            'ALDO.JK',
            'ALKA.JK',
            'ALMI.JK',
            'ALTO.JK',
            'AMAG.JK',
            'AMFG.JK',
            'AMIN.JK',
            'AMRT.JK',
            'ANJT.JK',
            'ANTM.JK',
            'APEX.JK',
            'APIC.JK',
            'APII.JK',
            'APLI.JK',
            'APLN.JK',
            'ARGO.JK',
            'ARII.JK',
            'ARNA.JK',
            'ARTA.JK',
            'ARTI.JK',
            'ARTO.JK',
            'ASBI.JK',
            'ASDM.JK',
            'ASGR.JK',
            'ASII.JK',
            'ASJT.JK',
            'ASMI.JK',
            'ASRI.JK',
            'ASRM.JK',
            'ASSA.JK',
            'ATIC.JK',
            'AUTO.JK',
            'BABP.JK',
            'BACA.JK',
            'BAJA.JK',
            'BALI.JK',
            'BAPA.JK',
            'BATA.JK',
            'BAYU.JK',
            'BBCA.JK',
            'BBHI.JK',
            'BBKP.JK',
            'BBLD.JK',
            'BBMD.JK',
            'BBNI.JK',
            'BBRI.JK',
            'BBRM.JK',
            'BBTN.JK',
            'BBYB.JK',
            'BCAP.JK',
            'BCIC.JK',
            'BCIP.JK',
            'BDMN.JK',
            'BEKS.JK',
            'BEST.JK',
            'BFIN.JK',
            'BGTG.JK',
            'BHIT.JK',
            'BIKA.JK',
            'BIMA.JK',
            'BINA.JK',
            'BIPI.JK',
            'BIPP.JK',
            'BIRD.JK',
            'BISI.JK',
            'BJBR.JK',
            'BJTM.JK',
            'BKDP.JK',
            'BKSL.JK',
            'BKSW.JK',
            'BLTA.JK',
            'BLTZ.JK',
            'BMAS.JK',
            'BMRI.JK',
            'BMSR.JK',
            'BMTR.JK',
            'BNBA.JK',
            'BNBR.JK',
            'BNGA.JK',
            'BNII.JK',
            'BNLI.JK',
            'BOLT.JK',
            'BPFI.JK',
            'BPII.JK',
            'BRAM.JK',
            'BRMS.JK',
            'BRNA.JK',
            'BRPT.JK',
            'BSDE.JK',
            'BSIM.JK',
            'BSSR.JK',
            'BSWD.JK',
            'BTEK.JK',
            'BTEL.JK',
            'BTON.JK',
            'BTPN.JK',
            'BUDI.JK',
            'BUKK.JK',
            'BULL.JK',
            'BUMI.JK',
            'BUVA.JK',
            'BVIC.JK',
            'BWPT.JK',
            'BYAN.JK',
            'CANI.JK',
            'CASS.JK',
            'CEKA.JK',
            'CENT.JK',
            'CFIN.JK',
            'CINT.JK',
            'CITA.JK',
            'CLPI.JK',
            'CMNP.JK',
            'CMPP.JK',
            'CNKO.JK',
            'CNTX.JK',
            'COWL.JK',
            'CPIN.JK',
            'CPRO.JK',
            'CSAP.JK',
            'CTBN.JK',
            'CTRA.JK',
            'CTTH.JK',
            'DART.JK',
            'DEFI.JK',
            'DEWA.JK',
            'DGIK.JK',
            'DILD.JK',
            'DKFT.JK',
            'DLTA.JK',
            'DMAS.JK',
            'DNAR.JK',
            'DNET.JK',
            'DOID.JK',
            'DPNS.JK',
            'DSFI.JK',
            'DSNG.JK',
            'DSSA.JK',
            'DUTI.JK',
            'DVLA.JK',
            'DYAN.JK',
            'ECII.JK',
            'EKAD.JK',
            'ELSA.JK',
            'ELTY.JK',
            'EMDE.JK',
            'EMTK.JK',
            'ENRG.JK',
            'EPMT.JK',
            'ERAA.JK',
            'ERTX.JK',
            'ESSA.JK',
            'ESTI.JK',
            'ETWA.JK',
            'EXCL.JK',
            'FAST.JK',
            'FASW.JK',
            'FISH.JK',
            'FMII.JK',
            'FORU.JK',
            'FPNI.JK',
            'GAMA.JK',
            'GDST.JK',
            'GDYR.JK',
            'GEMA.JK',
            'GEMS.JK',
            'GGRM.JK',
            'GIAA.JK',
            'GJTL.JK',
            'GLOB.JK',
            'GMTD.JK',
            'GOLD.JK',
            'GOLL.JK',
            'GPRA.JK',
            'GSMF.JK',
            'GTBO.JK',
            'GWSA.JK',
            'GZCO.JK',
            'HADE.JK',
            'HDFA.JK',
            'HERO.JK',
            'HEXA.JK',
            'HITS.JK',
            'HMSP.JK',
            'HOME.JK',
            'HOTL.JK',
            'HRUM.JK',
            'IATA.JK',
            'IBFN.JK',
            'IBST.JK',
            'ICBP.JK',
            'ICON.JK',
            'IGAR.JK',
            'IIKP.JK',
            'IKAI.JK',
            'IKBI.JK',
            'IMAS.JK',
            'IMJS.JK',
            'IMPC.JK',
            'INAF.JK',
            'INAI.JK',
            'INCI.JK',
            'INCO.JK',
            'INDF.JK',
            'INDR.JK',
            'INDS.JK',
            'INDX.JK',
            'INDY.JK',
            'INKP.JK',
            'INPC.JK',
            'INPP.JK',
            'INRU.JK',
            'INTA.JK',
            'INTD.JK',
            'INTP.JK',
            'IPOL.JK',
            'ISAT.JK',
            'ISSP.JK',
            'ITMA.JK',
            'ITMG.JK',
            'JAWA.JK',
            'JECC.JK',
            'JIHD.JK',
            'JKON.JK',
            'JPFA.JK',
            'JRPT.JK',
            'JSMR.JK',
            'JSPT.JK',
            'JTPE.JK',
            'KAEF.JK',
            'KARW.JK',
            'KBLI.JK',
            'KBLM.JK',
            'KBLV.JK',
            'KBRI.JK',
            'KDSI.JK',
            'KIAS.JK',
            'KICI.JK',
            'KIJA.JK',
            'KKGI.JK',
            'KLBF.JK',
            'KOBX.JK',
            'KOIN.JK',
            'KONI.JK',
            'KOPI.JK',
            'KPIG.JK',
            'KRAS.JK',
            'KREN.JK',
            'LAPD.JK',
            'LCGP.JK',
            'LEAD.JK',
            'LINK.JK',
            'LION.JK',
            'LMAS.JK',
            'LMPI.JK',
            'LMSH.JK',
            'LPCK.JK',
            'LPGI.JK',
            'LPIN.JK',
            'LPKR.JK',
            'LPLI.JK',
            'LPPF.JK',
            'LPPS.JK',
            'LRNA.JK',
            'LSIP.JK',
            'LTLS.JK',
            'MAGP.JK',
            'MAIN.JK',
            'MAPI.JK',
            'MASA.JK',
            'MAYA.JK',
            'MBAP.JK',
            'MBSS.JK',
            'MBTO.JK',
            'MCOR.JK',
            'MDIA.JK',
            'MDKA.JK',
            'MDLN.JK',
            'MDRN.JK',
            'MEDC.JK',
            'MEGA.JK',
            'MERK.JK',
            'META.JK',
            'MFIN.JK',
            'MFMI.JK',
            'MGNA.JK',
            'MICE.JK',
            'MIDI.JK',
            'MIKA.JK',
            'MIRA.JK',
            'MITI.JK',
            'MKPI.JK',
            'MLBI.JK',
            'MLIA.JK',
            'MLPL.JK',
            'MLPT.JK',
            'MMLP.JK',
            'MNCN.JK',
            'MPMX.JK',
            'MPPA.JK',
            'MRAT.JK',
            'MREI.JK',
            'MSKY.JK',
            'MTDL.JK',
            'MTFN.JK',
            'MTLA.JK',
            'MTSM.JK',
            'MYOH.JK',
            'MYOR.JK',
            'MYTX.JK',
            'NELY.JK',
            'NIKL.JK',
            'NIRO.JK',
            'NISP.JK',
            'NOBU.JK',
            'NRCA.JK',
            'OCAP.JK',
            'OKAS.JK',
            'OMRE.JK',
            'PADI.JK',
            'PALM.JK',
            'PANR.JK',
            'PANS.JK',
            'PBRX.JK',
            'PDES.JK',
            'PEGE.JK',
            'PGAS.JK',
            'PGLI.JK',
            'PICO.JK',
            'PJAA.JK',
            'PKPK.JK',
            'PLAS.JK',
            'PLIN.JK',
            'PNBN.JK',
            'PNBS.JK',
            'PNIN.JK',
            'PNLF.JK',
            'PNSE.JK',
            'POLY.JK',
            'POOL.JK',
            'PPRO.JK',
            'PSAB.JK',
            'PSDN.JK',
            'PSKT.JK',
            'PTBA.JK',
            'PTIS.JK',
            'PTPP.JK',
            'PTRO.JK',
            'PTSN.JK',
            'PTSP.JK',
            'PUDP.JK',
            'PWON.JK',
            'PYFA.JK',
            'RAJA.JK',
            'RALS.JK',
            'RANC.JK',
            'RBMS.JK',
            'RDTX.JK',
            'RELI.JK',
            'RICY.JK',
            'RIGS.JK',
            'RIMO.JK',
            'RODA.JK',
            'ROTI.JK',
            'RUIS.JK',
            'SAFE.JK',
            'SAME.JK',
            'SCCO.JK',
            'SCMA.JK',
            'SCPI.JK',
            'SDMU.JK',
            'SDPC.JK',
            'SDRA.JK',
            'SGRO.JK',
            'SHID.JK',
            'SIDO.JK',
            'SILO.JK',
            'SIMA.JK',
            'SIMP.JK',
            'SIPD.JK',
            'SKBM.JK',
            'SKLT.JK',
            'SKYB.JK',
            'SMAR.JK',
            'SMBR.JK',
            'SMCB.JK',
            'SMDM.JK',
            'SMDR.JK',
            'SMGR.JK',
            'SMMA.JK',
            'SMMT.JK',
            'SMRA.JK',
            'SMRU.JK',
            'SMSM.JK',
            'SOCI.JK',
            'SONA.JK',
            'SPMA.JK',
            'SQMI.JK',
            'SRAJ.JK',
            'SRIL.JK',
            'SRSN.JK',
            'SRTG.JK',
            'SSIA.JK',
            'SSMS.JK',
            'SSTM.JK',
            'STAR.JK',
            'STTP.JK',
            'SUGI.JK',
            'SULI.JK',
            'SUPR.JK',
            'TALF.JK',
            'TARA.JK',
            'TAXI.JK',
            'TBIG.JK',
            'TBLA.JK',
            'TBMS.JK',
            'TCID.JK',
            'TELE.JK',
            'TFCO.JK',
            'TGKA.JK',
            'TIFA.JK',
            'TINS.JK',
            'TIRA.JK',
            'TIRT.JK',
            'TKIM.JK',
            'TLKM.JK',
            'TMAS.JK',
            'TMPO.JK',
            'TOBA.JK',
            'TOTL.JK',
            'TOTO.JK',
            'TOWR.JK',
            'TPIA.JK',
            'TPMA.JK',
            'TRAM.JK',
            'TRIL.JK',
            'TRIM.JK',
            'TRIO.JK',
            'TRIS.JK',
            'TRST.JK',
            'TRUS.JK',
            'TSPC.JK',
            'ULTJ.JK',
            'UNIC.JK',
            'UNIT.JK',
            'UNSP.JK',
            'UNTR.JK',
            'UNVR.JK',
            'VICO.JK',
            'VINS.JK',
            'VIVA.JK',
            'VOKS.JK',
            'VRNA.JK',
            'WAPO.JK',
            'WEHA.JK',
            'WICO.JK',
            'WIIM.JK',
            'WIKA.JK',
            'WINS.JK',
            'WOMF.JK',
            'WSKT.JK',
            'WTON.JK',
            'YPAS.JK',
            'YULE.JK',
            'ZBRA.JK',
            'SHIP.JK',
            'CASA.JK',
            'DAYA.JK',
            'DPUM.JK',
            'IDPR.JK',
            'JGLE.JK',
            'KINO.JK',
            'MARI.JK',
            'MKNT.JK',
            'MTRA.JK',
            'OASA.JK',
            'POWR.JK',
            'INCF.JK',
            'WSBP.JK',
            'PBSA.JK',
            'PRDA.JK',
            'BOGA.JK',
            'BRIS.JK',
            'PORT.JK',
            'CARS.JK',
            'MINA.JK',
            'CLEO.JK',
            'TAMU.JK',
            'CSIS.JK',
            'TGRA.JK',
            'FIRE.JK',
            'TOPS.JK',
            'KMTR.JK',
            'ARMY.JK',
            'MAPB.JK',
            'WOOD.JK',
            'HRTA.JK',
            'MABA.JK',
            'HOKI.JK',
            'MPOW.JK',
            'MARK.JK',
            'NASA.JK',
            'MDKI.JK',
            'BELL.JK',
            'KIOS.JK',
            'GMFI.JK',
            'MTWI.JK',
            'ZINC.JK',
            'MCAS.JK',
            'PPRE.JK',
            'WEGE.JK',
            'PSSI.JK',
            'MORA.JK',
            'DWGL.JK',
            'PBID.JK',
            'JMAS.JK',
            'CAMP.JK',
            'IPCM.JK',
            'PCAR.JK',
            'LCKM.JK',
            'BOSS.JK',
            'HELI.JK',
            'JSKY.JK',
            'INPS.JK',
            'GHON.JK',
            'TDPM.JK',
            'DFAM.JK',
            'NICK.JK',
            'BTPS.JK',
            'SPTO.JK',
            'PRIM.JK',
            'HEAL.JK',
            'TRUK.JK',
            'PZZA.JK',
            'TUGU.JK',
            'MSIN.JK',
            'SWAT.JK',
            'TNCA.JK',
            'MAPA.JK',
            'TCPI.JK',
            'IPCC.JK',
            'RISE.JK',
            'BPTR.JK',
            'POLL.JK',
            'NFCX.JK',
            'MGRO.JK',
            'NUSA.JK',
            'FILM.JK',
            'ANDI.JK',
            'LAND.JK',
            'MOLI.JK',
            'PANI.JK',
            'DIGI.JK',
            'CITY.JK',
            'SAPX.JK',
            'SURE.JK',
            'HKMU.JK',
            'MPRO.JK',
            'DUCK.JK',
            'GOOD.JK',
            'SKRN.JK',
            'YELO.JK',
            'CAKK.JK',
            'SATU.JK',
            'SOSS.JK',
            'DEAL.JK',
            'POLA.JK',
            'DIVA.JK',
            'LUCK.JK',
            'URBN.JK',
            'SOTS.JK',
            'ZONE.JK',
            'PEHA.JK',
            'FOOD.JK',
            'BEEF.JK',
            'POLI.JK',
            'CLAY.JK',
            'NATO.JK',
            'JAYA.JK',
            'COCO.JK',
            'MTPS.JK',
            'CPRI.JK',
            'HRME.JK',
            'POSA.JK',
            'JAST.JK',
            'FITT.JK',
            'BOLA.JK',
            'CCSI.JK',
            'SFAN.JK',
            'POLU.JK',
            'KJEN.JK',
            'KAYU.JK',
            'ITIC.JK',
            'PAMG.JK',
            'IPTV.JK',
            'BLUE.JK',
            'ENVY.JK',
            'EAST.JK',
            'LIFE.JK',
            'FUJI.JK',
            'KOTA.JK',
            'INOV.JK',
            'ARKA.JK',
            'SMKL.JK',
            'HDIT.JK',
            'KEEN.JK',
            'BAPI.JK',
            'TFAS.JK',
            'GGRP.JK',
            'OPMS.JK',
            'NZIA.JK',
            'SLIS.JK',
            'PURE.JK',
            'IRRA.JK',
            'DMMX.JK',
            'SINI.JK',
            'WOWS.JK',
            'ESIP.JK',
            'TEBE.JK',
            'KEJU.JK',
            'PSGO.JK',
            'AGAR.JK',
            'IFSH.JK',
            'REAL.JK',
            'IFII.JK',
            'PMJS.JK',
            'UCID.JK',
            'GLVA.JK',
            'PGJO.JK',
            'AMAR.JK',
            'CSRA.JK',
            'INDO.JK',
            'AMOR.JK',
            'TRIN.JK',
            'DMND.JK',
            'PURA.JK',
            'PTPW.JK',
            'TAMA.JK',
            'IKAN.JK',
            'SAMF.JK',
            'SBAT.JK',
            'KBAG.JK',
            'CBMF.JK',
            'RONY.JK',
            'CSMI.JK',
            'BBSS.JK',
            'BHAT.JK',
            'CASH.JK',
            'TECH.JK',
            'EPAC.JK',
            'UANG.JK',
            'PGUN.JK',
            'SOFA.JK',
            'PPGL.JK',
            'TOYS.JK',
            'SGER.JK',
            'TRJA.JK',
            'PNGO.JK',
            'SCNP.JK',
            'BBSI.JK',
            'KMDS.JK',
            'PURI.JK',
            'SOHO.JK',
            'HOMI.JK',
            'ROCK.JK',
            'ENZO.JK',
            'PLAN.JK',
            'PTDU.JK',
            'ATAP.JK',
            'VICI.JK',
            'PMMP.JK',
            'WIFI.JK',
            'FAPA.JK',
            'DCII.JK',
            'KETR.JK',
            'DGNS.JK',
            'UFOE.JK',
            'BANK.JK',
            'WMUU.JK',
            'EDGE.JK',
            'UNIQ.JK',
            'BEBS.JK',
            'SNLK.JK',
            'ZYRX.JK',
            'LFLO.JK',
            'FIMP.JK',
            'TAPG.JK',
            'NPGF.JK',
            'LUCY.JK',
            'ADCP.JK',
            'HOPE.JK',
            'MGLV.JK',
            'TRUE.JK',
            'LABA.JK',
            'ARCI.JK',
            'IPAC.JK',
            'MASB.JK',
            'BMHS.JK',
            'FLMC.JK',
            'NICL.JK',
            'UVCR.JK',
            'BUKA.JK',
            'HAIS.JK',
            'OILS.JK',
            'GPSO.JK',
            'MCOL.JK',
            'RSGK.JK',
            'RUNS.JK',
            'SBMA.JK',
            'CMNT.JK',
            'GTSI.JK',
            'IDEA.JK',
            'KUAS.JK',
            'BOBA.JK',
            'MTEL.JK',
            'DEPO.JK',
            'BINO.JK',
            'CMRY.JK',
            'WGSH.JK',
            'TAYS.JK',
            'WMPP.JK',
            'RMKE.JK',
            'OBMD.JK',
            'AVIA.JK',
            'IPPE.JK',
            'NASI.JK',
            'BSML.JK',
            'DRMA.JK',
            'ADMR.JK',
            'SEMA.JK',
            'ASLC.JK',
            'NETV.JK',
            'BAUT.JK',
            'ENAK.JK',
            'NTBK.JK',
            'SMKM.JK',
            'STAA.JK',
            'NANO.JK',
            'BIKE.JK',
            'WIRG.JK',
            'SICO.JK',
            'GOTO.JK',
            'TLDN.JK',
            'MTMH.JK',
            'WINR.JK',
            'IBOS.JK',
            'OLIV.JK',
            'ASHA.JK',
            'SWID.JK',
            'TRGU.JK',
            'ARKO.JK',
            'CHEM.JK',
            'DEWI.JK',
            'AXIO.JK',
            'KRYA.JK',
            'HATM.JK',
            'RCCC.JK',
            'GULA.JK',
            'JARR.JK',
            'AMMS.JK',
            'RAFI.JK',
            'KKES.JK',
            'ELPI.JK',
            'EURO.JK',
            'KLIN.JK',
            'TOOL.JK',
            'BUAH.JK',
            'CRAB.JK',
            'MEDS.JK',
            'COAL.JK',
            'PRAY.JK',
            'CBUT.JK',
            'BELI.JK',
            'MKTR.JK',
            'OMED.JK',
            'BSBK.JK',
            'PDPP.JK',
            'KDTN.JK',
            'ZATA.JK',
            'NINE.JK',
            'MMIX.JK',
            'PADA.JK',
            'ISAP.JK',
            'VTNY.JK',
            'SOUL.JK',
            'ELIT.JK',
            'BEER.JK',
            'CBPE.JK',
            'SUNI.JK',
            'CBRE.JK',
            'WINE.JK',
            'BMBL.JK',
            'PEVE.JK',
            'LAJU.JK',
            'FWCT.JK',
            'NAYZ.JK',
            'IRSX.JK',
            'PACK.JK',
            'VAST.JK',
            'CHIP.JK',
            'HALO.JK',
            'KING.JK',
            'PGEO.JK',
            'FUTR.JK',
            'HILL.JK',
            'BDKR.JK',
            'PTMP.JK',
            'SAGE.JK',
            'TRON.JK',
            'CUAN.JK',
            'NSSS.JK',
            'GTRA.JK',
            'HAJJ.JK',
            'PIPA.JK',
            'NCKL.JK',
            'MENN.JK',
            'AWAN.JK',
            'MBMA.JK',
            'RAAM.JK',
            'DOOH.JK',
            'JATI.JK',
            'TYRE.JK',
            'MPXL.JK',
            'SMIL.JK',
            'KLAS.JK',
            'MAXI.JK',
            'VKTR.JK',
            'RELF.JK',
            'AMMN.JK',
            'CRSN.JK',
            'GRPM.JK',
            'WIDI.JK',
            'TGUK.JK',
            'INET.JK',
            'MAHA.JK',
            'RMKO.JK',
            'CNMA.JK',
            'FOLK.JK',
            'HBAT.JK',
            'GRIA.JK',
            'PPRI.JK',
            'ERAL.JK',
            'CYBR.JK',
            'MUTU.JK',
            'LMAX.JK',
            'HUMI.JK',
            'MSIE.JK',
            'RSCH.JK',
            'BABA.JK',
            'AEGS.JK',
            'IOTF.JK',
            'KOCI.JK',
            'PTPS.JK',
            'BREN.JK',
            'STRK.JK',
            'KOKA.JK',
            'LOPI.JK',
            'UDNG.JK',
            'RGAS.JK',
            'MSTI.JK',
            'IKPM.JK',
            'AYAM.JK',
            'SURI.JK',
            'ASLI.JK',
            'CGAS.JK',
            'NICE.JK',
            'MSJA.JK',
            'SMLE.JK',
            'ACRO.JK',
            'MANG.JK',
            'GRPH.JK',
            'SMGA.JK',
            'UNTD.JK',
            'TOSK.JK',
            'MPIX.JK',
            'ALII.JK',
            'MKAP.JK',
            'MEJA.JK',
            'LIVE.JK',
            'HYGN.JK',
            'BAIK.JK',
            'VISI.JK',
            'AREA.JK',
            'MHKI.JK',
            'ATLA.JK',
            'DATA.JK',
            'SOLA.JK',
            'BATR.JK',
            'SPRE.JK',
            'PART.JK',
            'GOLF.JK',
            'ISEA.JK',
            'BLES.JK',
            'GUNA.JK',
            'LABS.JK',
            'DOSS.JK',
            'NEST.JK',
            'PTMR.JK',
            'VERN.JK',
            'DAAZ.JK',
            'BOAT.JK',
            'NAIK.JK',
            'AADI.JK',
            'MDIY.JK',
            'KSIX.JK',
            'RATU.JK',
            'YOII.JK',
            'HGII.JK',
            'BRRC.JK',
            'DGWG.JK',
            'CBDK.JK',
            'OBAT.JK',
            'MINE.JK',
            'ASPR.JK',
            'PSAT.JK',
            'COIN.JK',
            'CDIA.JK',
            'BLOG.JK',
            'MERI.JK',
            'CHEK.JK',
            'PMUI.JK',
            'EMAS.JK',
            'KAQI.JK',
            'YUPI.JK',
            'FORE.JK',
            'MDLA.JK',
            'DKHH.JK',
            'AYLS.JK',
            'DADA.JK',
            'ASPI.JK',
            'ESTA.JK',
            'BESS.JK',
            'AMAN.JK',
            'CARE.JK'
        ];

        return collect($indonesianStocks)->map(function ($symbol)
        {
            return [
                'symbol' => $symbol,
                'market' => 'stock',
            ];
        })->toArray();
    }
}
