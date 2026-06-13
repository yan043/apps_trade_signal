<?php

namespace App\Services;

class SignalEngine
{
    private const STRONG_BUY_MIN = 75;
    private const BUY_MIN = 60;
    private const SELL_MAX = 40;
    private const STRONG_SELL_MAX = 25;
    private const OPPOSING_SCORE_LIMIT = 40;

    public function scalpingSignal(array $stock): ?array
    {
        $context = $this->buildContext($stock);

        if ($context === null)
        {
            return null;
        }

        if (!$this->passesScalpingGates($stock, $context))
        {
            return null;
        }

        [$bullScore, $bullReasons] = $this->scalpingBullScore($stock, $context);
        [$bearScore, $bearReasons] = $this->scalpingBearScore($stock, $context);

        $netScore = 50 + ($bullScore - $bearScore) / 2;
        $signal = $this->labelFromScores($netScore, $bullScore, $bearScore);

        $rsi15 = $this->num($stock, 'RSI|15');
        $bbUpper15 = $this->num($stock, 'BB.upper|15');
        $bbLower15 = $this->num($stock, 'BB.lower|15');

        if ($this->isBuySide($signal))
        {
            $overheated = ($rsi15 !== null && $rsi15 >= 78)
                || ($bbUpper15 !== null && $context['close'] > 1.01 * $bbUpper15)
                || ($context['roomToAra'] < 0.04);

            if ($overheated)
            {
                $signal = 'NEUTRAL';
            }
        }

        if ($this->isSellSide($signal))
        {
            $oversold = ($rsi15 !== null && $rsi15 <= 22)
                || ($bbLower15 !== null && $context['close'] < 0.99 * $bbLower15);

            if ($oversold)
            {
                $signal = 'NEUTRAL';
            }
        }

        $atr15 = $this->num($stock, 'ATR|15');
        $levels = null;

        if ($this->isBuySide($signal))
        {
            $levels = $this->buildScalpingLevels($context['close'], $atr15, $context['araPrice']);

            if ($levels === null)
            {
                $signal = 'NEUTRAL';
            }
        }

        return $this->buildPayload($stock, $context, $signal, $netScore, $bullScore, $bearScore, $bullReasons, $bearReasons, $levels, [
            'rsi' => $rsi15,
            'adx' => $this->num($stock, 'ADX|15'),
            'timeframe' => 'M5-M15',
        ]);
    }

    public function swingSignal(array $stock): ?array
    {
        $context = $this->buildContext($stock);

        if ($context === null)
        {
            return null;
        }

        if (!$this->passesSwingGates($stock, $context))
        {
            return null;
        }

        [$bullScore, $bullReasons] = $this->swingBullScore($stock, $context);
        [$bearScore, $bearReasons] = $this->swingBearScore($stock, $context);

        $netScore = 50 + ($bullScore - $bearScore) / 2;
        $signal = $this->labelFromScores($netScore, $bullScore, $bearScore);

        $rsi = $this->num($stock, 'RSI');
        $perfWeek = $this->num($stock, 'Perf.W');

        if ($this->isBuySide($signal))
        {
            $badTiming = ($perfWeek !== null && ($perfWeek > 20 || $perfWeek < -15))
                || ($rsi !== null && $rsi >= 75);

            if ($badTiming)
            {
                $signal = 'NEUTRAL';
            }
        }

        if ($this->isSellSide($signal) && $rsi !== null && $rsi <= 25)
        {
            $signal = 'NEUTRAL';
        }

        $atr = $this->num($stock, 'ATR');
        $levels = null;

        if ($this->isBuySide($signal))
        {
            $levels = $this->buildSwingLevels($context['close'], $atr, $context['prevClose'], $context['band']);

            if ($levels === null)
            {
                $signal = 'NEUTRAL';
            }
        }

        return $this->buildPayload($stock, $context, $signal, $netScore, $bullScore, $bearScore, $bullReasons, $bearReasons, $levels, [
            'rsi' => $rsi,
            'adx' => $this->num($stock, 'ADX'),
            'timeframe' => 'D1',
        ]);
    }

    public function isScalpingSessionOpen(): bool
    {
        $now = now('Asia/Jakarta');
        $dayOfWeek = $now->dayOfWeekIso;

        if ($dayOfWeek >= 6)
        {
            return false;
        }

        $minutesNow = $now->hour * 60 + $now->minute;
        $isFriday = ($dayOfWeek === 5);

        $morningStart = 9 * 60 + 15;
        $morningEnd = $isFriday ? (11 * 60 + 15) : (11 * 60 + 45);
        $afternoonStart = $isFriday ? (14 * 60 + 15) : (13 * 60 + 45);
        $afternoonEnd = 16 * 60 + 15;

        $inMorning = $minutesNow >= $morningStart && $minutesNow <= $morningEnd;
        $inAfternoon = $minutesNow >= $afternoonStart && $minutesNow <= $afternoonEnd;

        return $inMorning || $inAfternoon;
    }

    private function buildContext(array $stock): ?array
    {
        $close = $this->num($stock, 'close');
        $high = $this->num($stock, 'high');
        $low = $this->num($stock, 'low');
        $change = $this->num($stock, 'change');
        $changeAbs = $this->num($stock, 'change_abs');

        if ($close === null || $high === null || $low === null || $change === null || $changeAbs === null)
        {
            return null;
        }

        $prevClose = $close - $changeAbs;

        if ($prevClose <= 0)
        {
            return null;
        }

        $reconstructedClose = $prevClose * (1 + $change / 100);

        if (abs($reconstructedClose - $close) > $this->tickSize($close))
        {
            return null;
        }

        $band = $this->autoRejectionBand($prevClose);
        $araPrice = $this->roundDownTick($prevClose * (1 + $band));
        $arbPrice = $this->roundUpTick($prevClose * (1 - $band));

        return [
            'close' => $close,
            'high' => $high,
            'low' => $low,
            'change' => $change,
            'prevClose' => $prevClose,
            'band' => $band,
            'araPrice' => $araPrice,
            'arbPrice' => $arbPrice,
            'roomToAra' => ($araPrice - $close) / $close,
            'roomToArb' => ($close - $arbPrice) / $close,
            'dayPos' => $high > $low ? ($close - $low) / ($high - $low) : 0.5,
        ];
    }

    private function passesScalpingGates(array $stock, array $context): bool
    {
        $volume = $this->num($stock, 'volume');
        $avgVolume30d = $this->num($stock, 'average_volume_30d_calc');
        $valueTraded = $this->num($stock, 'Value.Traded');
        $marketCap = $this->num($stock, 'market_cap_basic');
        $relativeVolume = $this->num($stock, 'relative_volume_10d_calc');
        $dailyVolatility = $this->num($stock, 'Volatility.D');
        $atr15 = $this->num($stock, 'ATR|15');

        if ($volume === null || $avgVolume30d === null || $valueTraded === null || $marketCap === null
            || $relativeVolume === null || $dailyVolatility === null || $atr15 === null)
        {
            return false;
        }

        $close = $context['close'];

        if ($close < 100 || $context['prevClose'] <= 50)
        {
            return false;
        }

        $turnoverMatured = now('Asia/Jakarta')->hour >= 11;

        if ($avgVolume30d * $close < 10000000000)
        {
            return false;
        }

        if ($turnoverMatured && $valueTraded < 10000000000)
        {
            return false;
        }

        if ($marketCap < 500000000000)
        {
            return false;
        }

        if ($relativeVolume < 1.2)
        {
            return false;
        }

        if (abs($context['change']) > 0.7 * $context['band'] * 100)
        {
            return false;
        }

        if ($dailyVolatility < 1.5 || $dailyVolatility > 10)
        {
            return false;
        }

        if ($atr15 < 3 * $this->tickSize($close))
        {
            return false;
        }

        if ($volume <= 0 || $context['high'] <= $context['low'] || $atr15 <= 0)
        {
            return false;
        }

        return true;
    }

    private function scalpingBullScore(array $stock, array $context): array
    {
        $score = 0;
        $reasons = [];
        $close = $context['close'];

        $ema5m5 = $this->num($stock, 'EMA5|5');
        $ema20m5 = $this->num($stock, 'EMA20|5');
        $ema20m15 = $this->num($stock, 'EMA20|15');
        $ema50m15 = $this->num($stock, 'EMA50|15');
        $vwap = $this->num($stock, 'VWAP');
        $rsi15 = $this->num($stock, 'RSI|15');
        $macd15 = $this->num($stock, 'MACD.macd|15');
        $macdSignal15 = $this->num($stock, 'MACD.signal|15');
        $adx15 = $this->num($stock, 'ADX|15');
        $plusDi15 = $this->num($stock, 'ADX+DI|15');
        $minusDi15 = $this->num($stock, 'ADX-DI|15');
        $relativeVolume = $this->num($stock, 'relative_volume_10d_calc');
        $valueTraded = $this->num($stock, 'Value.Traded');
        $bbUpper15 = $this->num($stock, 'BB.upper|15');
        $bbLower15 = $this->num($stock, 'BB.lower|15');

        if ($ema5m5 !== null && $ema20m5 !== null && $close > $ema20m5 && $ema5m5 > $ema20m5)
        {
            $score += 8;
            $reasons[] = ['label' => 'Tren micro 5m naik (close > EMA20, EMA5 > EMA20)', 'points' => 8];
        }

        if ($ema20m15 !== null && $ema50m15 !== null && $close > $ema20m15 && $ema20m15 > $ema50m15)
        {
            $score += 12;
            $reasons[] = ['label' => 'Struktur 15m naik (close > EMA20 > EMA50)', 'points' => 12];
        }

        if ($vwap !== null && $vwap > 0 && $close > $vwap)
        {
            $vwapDistance = ($close - $vwap) / $vwap;
            $points = $vwapDistance <= 0.03 ? 10 : 5;
            $score += $points;
            $reasons[] = ['label' => $points === 10 ? 'Di atas VWAP, tidak overextended (<= 3%)' : 'Di atas VWAP tapi > 3% (mengejar)', 'points' => $points];
        }

        if ($rsi15 !== null)
        {
            $points = 0;
            if ($rsi15 >= 55 && $rsi15 <= 68) $points = 10;
            elseif (($rsi15 >= 50 && $rsi15 < 55) || ($rsi15 > 68 && $rsi15 <= 72)) $points = 5;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'RSI 15m di zona momentum sehat (' . round($rsi15, 1) . ')', 'points' => $points];
            }
        }

        if ($macd15 !== null && $macdSignal15 !== null && $macd15 > $macdSignal15)
        {
            $points = $macd15 > 0 ? 10 : 5;
            $score += $points;
            $reasons[] = ['label' => $points === 10 ? 'MACD 15m di atas signal dan positif' : 'MACD 15m di atas signal', 'points' => $points];
        }

        if ($adx15 !== null && $plusDi15 !== null && $minusDi15 !== null && $adx15 >= 20 && $plusDi15 > $minusDi15)
        {
            $score += 5;
            $reasons[] = ['label' => 'ADX 15m >= 20 dengan +DI dominan', 'points' => 5];
        }

        if ($context['change'] > 0.5)
        {
            if ($relativeVolume !== null)
            {
                $points = 0;
                if ($relativeVolume >= 3.0) $points = 15;
                elseif ($relativeVolume >= 2.0) $points = 10;
                elseif ($relativeVolume >= 1.5) $points = 5;

                if ($points > 0)
                {
                    $score += $points;
                    $reasons[] = ['label' => 'Volume relatif ' . round($relativeVolume, 1) . 'x pada hari naik', 'points' => $points];
                }
            }

            if ($valueTraded !== null)
            {
                $points = 0;
                if ($valueTraded >= 50000000000) $points = 10;
                elseif ($valueTraded >= 20000000000) $points = 5;

                if ($points > 0)
                {
                    $score += $points;
                    $reasons[] = ['label' => 'Nilai transaksi besar (Rp ' . round($valueTraded / 1000000000, 1) . ' M)', 'points' => $points];
                }
            }
        }

        $roomPoints = 0;
        if ($context['roomToAra'] >= 0.10) $roomPoints = 8;
        elseif ($context['roomToAra'] >= 0.06) $roomPoints = 4;

        if ($roomPoints > 0)
        {
            $score += $roomPoints;
            $reasons[] = ['label' => 'Ruang ke ARA masih ' . round($context['roomToAra'] * 100, 1) . '%', 'points' => $roomPoints];
        }

        $dayPosPoints = 0;
        if ($context['dayPos'] >= 0.7) $dayPosPoints = 6;
        elseif ($context['dayPos'] >= 0.5) $dayPosPoints = 3;

        if ($dayPosPoints > 0)
        {
            $score += $dayPosPoints;
            $reasons[] = ['label' => 'Harga bertahan di area atas range harian', 'points' => $dayPosPoints];
        }

        if ($bbUpper15 !== null && $bbLower15 !== null)
        {
            $bbMiddle = ($bbUpper15 + $bbLower15) / 2;

            if ($close > $bbMiddle && $close <= $bbUpper15)
            {
                $score += 6;
                $reasons[] = ['label' => 'Di paruh atas Bollinger 15m tanpa menembus upper band', 'points' => 6];
            }
        }

        return [$score, $reasons];
    }

    private function scalpingBearScore(array $stock, array $context): array
    {
        $score = 0;
        $reasons = [];
        $close = $context['close'];

        $ema5m5 = $this->num($stock, 'EMA5|5');
        $ema20m5 = $this->num($stock, 'EMA20|5');
        $ema20m15 = $this->num($stock, 'EMA20|15');
        $ema50m15 = $this->num($stock, 'EMA50|15');
        $vwap = $this->num($stock, 'VWAP');
        $rsi15 = $this->num($stock, 'RSI|15');
        $macd15 = $this->num($stock, 'MACD.macd|15');
        $macdSignal15 = $this->num($stock, 'MACD.signal|15');
        $adx15 = $this->num($stock, 'ADX|15');
        $plusDi15 = $this->num($stock, 'ADX+DI|15');
        $minusDi15 = $this->num($stock, 'ADX-DI|15');
        $relativeVolume = $this->num($stock, 'relative_volume_10d_calc');
        $valueTraded = $this->num($stock, 'Value.Traded');
        $bbUpper15 = $this->num($stock, 'BB.upper|15');
        $bbLower15 = $this->num($stock, 'BB.lower|15');

        if ($ema5m5 !== null && $ema20m5 !== null && $close < $ema20m5 && $ema5m5 < $ema20m5)
        {
            $score += 8;
            $reasons[] = ['label' => 'Tren micro 5m turun', 'points' => 8];
        }

        if ($ema20m15 !== null && $ema50m15 !== null && $close < $ema20m15 && $ema20m15 < $ema50m15)
        {
            $score += 12;
            $reasons[] = ['label' => 'Struktur 15m turun (close < EMA20 < EMA50)', 'points' => 12];
        }

        if ($vwap !== null && $vwap > 0 && $close < $vwap)
        {
            $vwapDistance = ($vwap - $close) / $vwap;
            $points = $vwapDistance <= 0.03 ? 10 : 5;
            $score += $points;
            $reasons[] = ['label' => 'Di bawah VWAP', 'points' => $points];
        }

        if ($rsi15 !== null)
        {
            $points = 0;
            if ($rsi15 >= 32 && $rsi15 <= 45) $points = 10;
            elseif (($rsi15 > 45 && $rsi15 <= 50) || ($rsi15 >= 28 && $rsi15 < 32)) $points = 5;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'RSI 15m di zona pelemahan (' . round($rsi15, 1) . ')', 'points' => $points];
            }
        }

        if ($macd15 !== null && $macdSignal15 !== null && $macd15 < $macdSignal15)
        {
            $points = $macd15 < 0 ? 10 : 5;
            $score += $points;
            $reasons[] = ['label' => $points === 10 ? 'MACD 15m di bawah signal dan negatif' : 'MACD 15m di bawah signal', 'points' => $points];
        }

        if ($adx15 !== null && $plusDi15 !== null && $minusDi15 !== null && $adx15 >= 20 && $minusDi15 > $plusDi15)
        {
            $score += 5;
            $reasons[] = ['label' => 'ADX 15m >= 20 dengan -DI dominan', 'points' => 5];
        }

        if ($context['change'] < -0.5)
        {
            if ($relativeVolume !== null)
            {
                $points = 0;
                if ($relativeVolume >= 3.0) $points = 15;
                elseif ($relativeVolume >= 2.0) $points = 10;
                elseif ($relativeVolume >= 1.5) $points = 5;

                if ($points > 0)
                {
                    $score += $points;
                    $reasons[] = ['label' => 'Volume relatif ' . round($relativeVolume, 1) . 'x pada hari turun (distribusi)', 'points' => $points];
                }
            }

            if ($valueTraded !== null)
            {
                $points = 0;
                if ($valueTraded >= 50000000000) $points = 10;
                elseif ($valueTraded >= 20000000000) $points = 5;

                if ($points > 0)
                {
                    $score += $points;
                    $reasons[] = ['label' => 'Nilai transaksi besar pada penurunan', 'points' => $points];
                }
            }
        }

        $roomPoints = 0;
        if ($context['roomToArb'] >= 0.10) $roomPoints = 8;
        elseif ($context['roomToArb'] >= 0.06) $roomPoints = 4;

        if ($roomPoints > 0)
        {
            $score += $roomPoints;
            $reasons[] = ['label' => 'Ruang ke ARB masih lebar', 'points' => $roomPoints];
        }

        $dayPosPoints = 0;
        if ($context['dayPos'] <= 0.3) $dayPosPoints = 6;
        elseif ($context['dayPos'] <= 0.5) $dayPosPoints = 3;

        if ($dayPosPoints > 0)
        {
            $score += $dayPosPoints;
            $reasons[] = ['label' => 'Harga tertahan di area bawah range harian', 'points' => $dayPosPoints];
        }

        if ($bbUpper15 !== null && $bbLower15 !== null)
        {
            $bbMiddle = ($bbUpper15 + $bbLower15) / 2;

            if ($close < $bbMiddle && $close >= $bbLower15)
            {
                $score += 6;
                $reasons[] = ['label' => 'Di paruh bawah Bollinger 15m', 'points' => 6];
            }
        }

        return [$score, $reasons];
    }

    private function passesSwingGates(array $stock, array $context): bool
    {
        $volume = $this->num($stock, 'volume');
        $avgVolume30d = $this->num($stock, 'average_volume_30d_calc');
        $valueTraded = $this->num($stock, 'Value.Traded');
        $marketCap = $this->num($stock, 'market_cap_basic');
        $dailyVolatility = $this->num($stock, 'Volatility.D');
        $atr = $this->num($stock, 'ATR');
        $ema20 = $this->num($stock, 'EMA20');

        if ($volume === null || $avgVolume30d === null || $valueTraded === null || $marketCap === null
            || $dailyVolatility === null || $atr === null || $ema20 === null || $ema20 <= 0)
        {
            return false;
        }

        $close = $context['close'];

        if ($close < 100 || $context['prevClose'] <= 50)
        {
            return false;
        }

        if ($avgVolume30d * $close < 3000000000 || $valueTraded < 2000000000)
        {
            return false;
        }

        if ($marketCap < 500000000000)
        {
            return false;
        }

        if (abs($context['change']) > 0.6 * $context['band'] * 100)
        {
            return false;
        }

        if (abs($close - $ema20) / $ema20 > 0.15)
        {
            return false;
        }

        if ($dailyVolatility > 8 || $atr / $close > 0.06 || $atr < 4 * $this->tickSize($close))
        {
            return false;
        }

        if ($volume <= 0 || $atr <= 0)
        {
            return false;
        }

        return true;
    }

    private function swingBullScore(array $stock, array $context): array
    {
        $score = 0;
        $reasons = [];
        $close = $context['close'];

        $ema20 = $this->num($stock, 'EMA20');
        $ema50 = $this->num($stock, 'EMA50');
        $ema200 = $this->num($stock, 'EMA200');
        $macdWeekly = $this->num($stock, 'MACD.macd|1W');
        $macdSignalWeekly = $this->num($stock, 'MACD.signal|1W');
        $rsi = $this->num($stock, 'RSI');
        $macd = $this->num($stock, 'MACD.macd');
        $macdSignal = $this->num($stock, 'MACD.signal');
        $adx = $this->num($stock, 'ADX');
        $plusDi = $this->num($stock, 'ADX+DI');
        $minusDi = $this->num($stock, 'ADX-DI');
        $relativeVolume = $this->num($stock, 'relative_volume_10d_calc');
        $avgVolume10d = $this->num($stock, 'average_volume_10d_calc');
        $avgVolume30d = $this->num($stock, 'average_volume_30d_calc');
        $valueTraded = $this->num($stock, 'Value.Traded');
        $yearHigh = $this->num($stock, 'price_52_week_high');
        $pivotMiddle = $this->num($stock, 'Pivot.M.Classic.Middle');
        $perfMonth = $this->num($stock, 'Perf.1M');

        if ($ema20 !== null && $ema50 !== null && $close > $ema20 && $ema20 > $ema50)
        {
            $score += 15;
            $reasons[] = ['label' => 'Susunan tren naik (close > EMA20 > EMA50)', 'points' => 15];
        }

        if ($ema200 !== null)
        {
            if ($close > $ema200)
            {
                $score += 6;
                $reasons[] = ['label' => 'Di atas EMA200 (rezim jangka panjang bullish)', 'points' => 6];
            }

            if ($ema50 !== null && $ema50 > $ema200)
            {
                $score += 4;
                $reasons[] = ['label' => 'EMA50 di atas EMA200 (stage-2 uptrend)', 'points' => 4];
            }
        }

        if ($macdWeekly !== null && $macdSignalWeekly !== null && $macdWeekly > $macdSignalWeekly)
        {
            $score += 10;
            $reasons[] = ['label' => 'MACD weekly naik (siklus besar mendukung)', 'points' => 10];
        }

        if ($rsi !== null)
        {
            $points = 0;
            if ($rsi >= 50 && $rsi <= 65) $points = 10;
            elseif (($rsi >= 45 && $rsi < 50) || ($rsi > 65 && $rsi <= 70)) $points = 5;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'RSI harian sehat (' . round($rsi, 1) . '), bukan jenuh beli', 'points' => $points];
            }
        }

        if ($macd !== null && $macdSignal !== null && $macd > $macdSignal)
        {
            $points = $macd > 0 ? 8 : 5;
            $score += $points;
            $reasons[] = ['label' => $points === 8 ? 'MACD harian di atas signal dan positif' : 'MACD harian di atas signal', 'points' => $points];
        }

        if ($adx !== null && $plusDi !== null && $minusDi !== null && $plusDi > $minusDi)
        {
            $points = 0;
            if ($adx >= 20) $points = 7;
            elseif ($adx >= 15) $points = 3;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'Kekuatan tren terkonfirmasi (ADX ' . round($adx, 1) . ', +DI dominan)', 'points' => $points];
            }
        }

        if ($relativeVolume !== null && $context['change'] > 0)
        {
            $points = 0;
            if ($relativeVolume >= 1.5) $points = 8;
            elseif ($relativeVolume >= 1.2) $points = 4;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'Hari naik dengan volume ' . round($relativeVolume, 1) . 'x rata-rata', 'points' => $points];
            }
        }

        if ($avgVolume10d !== null && $avgVolume30d !== null && $avgVolume10d > $avgVolume30d)
        {
            $score += 6;
            $reasons[] = ['label' => 'Volume 10 hari > 30 hari (jejak akumulasi)', 'points' => 6];
        }

        if ($valueTraded !== null && $valueTraded >= 10000000000)
        {
            $score += 6;
            $reasons[] = ['label' => 'Likuiditas dalam (nilai transaksi >= Rp 10 M)', 'points' => 6];
        }

        if ($yearHigh !== null && $yearHigh > 0)
        {
            $points = 0;
            if ($close >= 0.75 * $yearHigh) $points = 7;
            elseif ($close >= 0.5 * $yearHigh) $points = 3;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'Dekat 52-week high (saham leader)', 'points' => $points];
            }
        }

        if ($pivotMiddle !== null && $close > $pivotMiddle)
        {
            $score += 6;
            $reasons[] = ['label' => 'Di atas pivot bulanan (buyer memegang kendali)', 'points' => 6];
        }

        if ($perfMonth !== null)
        {
            $points = 0;
            if ($perfMonth > 0 && $perfMonth <= 25) $points = 7;
            elseif ($perfMonth > 25) $points = 2;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => $points === 7 ? 'Performa 1 bulan positif tapi tidak parabolik' : 'Performa 1 bulan parabolik (risiko mean-reversion)', 'points' => $points];
            }
        }

        return [$score, $reasons];
    }

    private function swingBearScore(array $stock, array $context): array
    {
        $score = 0;
        $reasons = [];
        $close = $context['close'];

        $ema20 = $this->num($stock, 'EMA20');
        $ema50 = $this->num($stock, 'EMA50');
        $ema200 = $this->num($stock, 'EMA200');
        $macdWeekly = $this->num($stock, 'MACD.macd|1W');
        $macdSignalWeekly = $this->num($stock, 'MACD.signal|1W');
        $rsi = $this->num($stock, 'RSI');
        $macd = $this->num($stock, 'MACD.macd');
        $macdSignal = $this->num($stock, 'MACD.signal');
        $adx = $this->num($stock, 'ADX');
        $plusDi = $this->num($stock, 'ADX+DI');
        $minusDi = $this->num($stock, 'ADX-DI');
        $relativeVolume = $this->num($stock, 'relative_volume_10d_calc');
        $avgVolume10d = $this->num($stock, 'average_volume_10d_calc');
        $avgVolume30d = $this->num($stock, 'average_volume_30d_calc');
        $valueTraded = $this->num($stock, 'Value.Traded');
        $yearLow = $this->num($stock, 'price_52_week_low');
        $pivotMiddle = $this->num($stock, 'Pivot.M.Classic.Middle');
        $perfWeek = $this->num($stock, 'Perf.W');
        $perfMonth = $this->num($stock, 'Perf.1M');

        if ($ema20 !== null && $ema50 !== null && $close < $ema20 && $ema20 < $ema50)
        {
            $score += 15;
            $reasons[] = ['label' => 'Susunan tren turun (close < EMA20 < EMA50)', 'points' => 15];
        }

        if ($ema200 !== null)
        {
            if ($close < $ema200)
            {
                $score += 6;
                $reasons[] = ['label' => 'Di bawah EMA200', 'points' => 6];
            }

            if ($ema50 !== null && $ema50 < $ema200)
            {
                $score += 4;
                $reasons[] = ['label' => 'EMA50 di bawah EMA200 (downtrend struktural)', 'points' => 4];
            }
        }

        if ($macdWeekly !== null && $macdSignalWeekly !== null && $macdWeekly < $macdSignalWeekly)
        {
            $score += 10;
            $reasons[] = ['label' => 'MACD weekly turun', 'points' => 10];
        }

        if ($rsi !== null)
        {
            $points = 0;
            if ($rsi >= 35 && $rsi < 50) $points = 10;
            elseif (($rsi >= 30 && $rsi < 35) || ($rsi >= 50 && $rsi < 55)) $points = 5;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'RSI harian melemah (' . round($rsi, 1) . ')', 'points' => $points];
            }
        }

        if ($macd !== null && $macdSignal !== null && $macd < $macdSignal)
        {
            $points = $macd < 0 ? 8 : 5;
            $score += $points;
            $reasons[] = ['label' => $points === 8 ? 'MACD harian di bawah signal dan negatif' : 'MACD harian di bawah signal', 'points' => $points];
        }

        if ($adx !== null && $plusDi !== null && $minusDi !== null && $minusDi > $plusDi)
        {
            $points = 0;
            if ($adx >= 20) $points = 7;
            elseif ($adx >= 15) $points = 3;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'Tekanan jual terkonfirmasi (ADX ' . round($adx, 1) . ', -DI dominan)', 'points' => $points];
            }
        }

        if ($relativeVolume !== null && $context['change'] < 0)
        {
            $points = 0;
            if ($relativeVolume >= 1.5) $points = 8;
            elseif ($relativeVolume >= 1.2) $points = 4;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'Hari turun dengan volume tinggi (distribusi)', 'points' => $points];
            }
        }

        if ($avgVolume10d !== null && $avgVolume30d !== null && $avgVolume10d > $avgVolume30d
            && $perfWeek !== null && $perfWeek < 0)
        {
            $score += 6;
            $reasons[] = ['label' => 'Volume meningkat pada minggu negatif (distribusi)', 'points' => 6];
        }

        if ($valueTraded !== null && $valueTraded >= 10000000000)
        {
            $score += 6;
            $reasons[] = ['label' => 'Penurunan pada saham likuid (sinyal kredibel)', 'points' => 6];
        }

        if ($yearLow !== null && $yearLow > 0)
        {
            $points = 0;
            if ($close <= 1.25 * $yearLow) $points = 7;
            elseif ($close <= 1.5 * $yearLow) $points = 3;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'Dekat 52-week low (saham laggard)', 'points' => $points];
            }
        }

        if ($pivotMiddle !== null && $close < $pivotMiddle)
        {
            $score += 6;
            $reasons[] = ['label' => 'Di bawah pivot bulanan', 'points' => 6];
        }

        if ($perfMonth !== null)
        {
            $points = 0;
            if ($perfMonth >= -25 && $perfMonth < 0) $points = 7;
            elseif ($perfMonth < -25) $points = 2;

            if ($points > 0)
            {
                $score += $points;
                $reasons[] = ['label' => 'Performa 1 bulan negatif', 'points' => $points];
            }
        }

        return [$score, $reasons];
    }

    private function labelFromScores(float $netScore, int $bullScore, int $bearScore): string
    {
        if ($netScore >= self::STRONG_BUY_MIN && $bearScore <= self::OPPOSING_SCORE_LIMIT)
        {
            return 'STRONG BUY';
        }

        if ($netScore >= self::BUY_MIN)
        {
            return 'BUY';
        }

        if ($netScore <= self::STRONG_SELL_MAX && $bullScore <= self::OPPOSING_SCORE_LIMIT)
        {
            return 'STRONG SELL';
        }

        if ($netScore <= self::SELL_MAX)
        {
            return 'SELL';
        }

        return 'NEUTRAL';
    }

    private function buildScalpingLevels(float $close, ?float $atr15, float $araPrice): ?array
    {
        if ($atr15 === null || $atr15 <= 0)
        {
            return null;
        }

        $entry = $this->roundDownTick($close);
        $entryLow = $this->roundDownTick($close - 0.25 * $atr15);
        $stopLoss = $this->roundDownTick($entry - 1.2 * $atr15);

        $takeProfitCap = $araPrice - 2 * $this->tickSize($araPrice);
        $takeProfit1 = min($this->roundDownTick($entry + 1.2 * $atr15), $takeProfitCap);
        $takeProfit2 = min($this->roundDownTick($entry + 2.4 * $atr15), $takeProfitCap);
        $takeProfit3 = min($this->roundDownTick($entry + 3.6 * $atr15), $takeProfitCap);

        $tick = $this->tickSize($entry);

        $stopLossViable = $stopLoss <= $entry - 2 * $tick && $stopLoss > 0;
        $takeProfitViable = $takeProfit1 >= $entry + 3 * $tick && ($takeProfit1 - $entry) / $entry >= 0.004;

        if (!$stopLossViable || !$takeProfitViable)
        {
            return null;
        }

        $riskReward = ($takeProfit1 - $entry) / ($entry - $stopLoss);

        if ($riskReward < 0.9)
        {
            return null;
        }

        return $this->formatLevels($entryLow, $entry, $stopLoss, $takeProfit1, $takeProfit2, $takeProfit3, $riskReward);
    }

    private function buildSwingLevels(float $close, ?float $atr, float $prevClose, float $band): ?array
    {
        if ($atr === null || $atr <= 0)
        {
            return null;
        }

        $entry = $this->roundDownTick($close);
        $entryLow = $this->roundDownTick($close - 0.5 * $atr);
        $stopLoss = $this->roundDownTick($entry - 2.0 * $atr);

        $takeProfits = [];

        foreach ([2.0, 4.0, 6.0] as $dayIndex => $multiplier)
        {
            $target = $this->roundDownTick($entry + $multiplier * $atr);
            $compoundedCap = $this->roundDownTick($prevClose * pow(1 + $band, $dayIndex + 1));
            $cap = $compoundedCap - $this->tickSize($compoundedCap);
            $takeProfits[] = min($target, $cap);
        }

        [$takeProfit1, $takeProfit2, $takeProfit3] = $takeProfits;

        $tick = $this->tickSize($entry);

        $stopLossViable = $stopLoss <= $entry - 2 * $tick && $stopLoss > 0;
        $takeProfitViable = $takeProfit1 >= $entry + 2 * $tick;

        if (!$stopLossViable || !$takeProfitViable)
        {
            return null;
        }

        $riskReward = ($takeProfit1 - $entry) / ($entry - $stopLoss);

        if ($riskReward < 0.9)
        {
            return null;
        }

        return $this->formatLevels($entryLow, $entry, $stopLoss, $takeProfit1, $takeProfit2, $takeProfit3, $riskReward);
    }

    private function formatLevels(float $entryLow, float $entry, float $stopLoss, float $tp1, float $tp2, float $tp3, float $riskReward): array
    {
        return [
            'entry1' => $entryLow,
            'entry2' => $entry,
            'stopLoss' => $stopLoss,
            'takeProfit1' => $tp1,
            'takeProfit2' => $tp2,
            'takeProfit3' => $tp3,
            'takeProfit1_percent' => round(($tp1 - $entry) / $entry * 100, 2),
            'takeProfit2_percent' => round(($tp2 - $entry) / $entry * 100, 2),
            'takeProfit3_percent' => round(($tp3 - $entry) / $entry * 100, 2),
            'stopLoss_percent' => round(($stopLoss - $entry) / $entry * 100, 2),
            'riskReward' => round($riskReward, 2),
        ];
    }

    private function buildPayload(array $stock, array $context, string $signal, float $netScore, int $bullScore, int $bearScore, array $bullReasons, array $bearReasons, ?array $levels, array $extras): array
    {
        $payload = [
            'symbol' => $stock['name'] ?? '',
            'description' => $stock['description'] ?? '',
            'logo' => $stock['logo'] ?? '',
            'signal' => $signal,
            'score' => round($netScore, 1),
            'bull_score' => $bullScore,
            'bear_score' => $bearScore,
            'price' => $context['close'],
            'change' => round($context['change'], 2),
            'entry1' => null,
            'entry2' => null,
            'stopLoss' => null,
            'takeProfit1' => null,
            'takeProfit2' => null,
            'takeProfit3' => null,
            'takeProfit1_percent' => null,
            'takeProfit2_percent' => null,
            'takeProfit3_percent' => null,
            'stopLoss_percent' => null,
            'riskReward' => null,
            'rsi' => $extras['rsi'] !== null ? round($extras['rsi'], 1) : null,
            'adx' => $extras['adx'] !== null ? round($extras['adx'], 1) : null,
            'volumeRatio' => $this->num($stock, 'relative_volume_10d_calc') !== null ? round($this->num($stock, 'relative_volume_10d_calc'), 2) : null,
            'valueTraded' => $this->num($stock, 'Value.Traded'),
            'roomToAra' => round($context['roomToAra'] * 100, 1),
            'bull_reasons' => $bullReasons,
            'bear_reasons' => $bearReasons,
            'timeframe' => $extras['timeframe'],
            'timestamp' => now('Asia/Jakarta')->format('H:i:s'),
        ];

        if ($levels !== null)
        {
            $payload = array_merge($payload, $levels);
        }

        return $payload;
    }

    private function autoRejectionBand(float $prevClose): float
    {
        if ($prevClose <= 200) return 0.35;
        if ($prevClose <= 5000) return 0.25;
        return 0.20;
    }

    private function tickSize(float $price): int
    {
        if ($price < 200) return 1;
        if ($price < 500) return 2;
        if ($price < 2000) return 5;
        if ($price < 5000) return 10;
        return 25;
    }

    private function roundDownTick(float $price): float
    {
        if ($price <= 0)
        {
            return 0;
        }

        $tick = $this->tickSize($price);
        $rounded = floor($price / $tick) * $tick;
        $tickAfterRounding = $this->tickSize($rounded);

        if ($tickAfterRounding !== $tick)
        {
            $rounded = floor($rounded / $tickAfterRounding) * $tickAfterRounding;
        }

        return $rounded;
    }

    private function roundUpTick(float $price): float
    {
        if ($price <= 0)
        {
            return 0;
        }

        $tick = $this->tickSize($price);
        $rounded = ceil($price / $tick) * $tick;
        $tickAfterRounding = $this->tickSize($rounded);

        if ($tickAfterRounding !== $tick)
        {
            $rounded = ceil($rounded / $tickAfterRounding) * $tickAfterRounding;
        }

        return $rounded;
    }

    private function isBuySide(string $signal): bool
    {
        return $signal === 'BUY' || $signal === 'STRONG BUY';
    }

    private function isSellSide(string $signal): bool
    {
        return $signal === 'SELL' || $signal === 'STRONG SELL';
    }

    private function num(array $stock, string $key): ?float
    {
        return isset($stock[$key]) && is_numeric($stock[$key]) ? (float) $stock[$key] : null;
    }
}
