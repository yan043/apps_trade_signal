<?php

namespace App\Http\Controllers;

use App\Models\TelegramModel;
use App\Models\SignalHistory;

date_default_timezone_set('Asia/Jakarta');

class TradingSignalController extends Controller
{
    private $tokenBot;
    private $chatID;
    private $scalpingThreadID = null;
    private $swingThreadID = null;

    public function __construct()
    {
        $this->tokenBot = env('TELEGRAM_BOT_TOKEN');
        $this->chatID = env('TELEGRAM_CHAT_ID');
    }

    public function generateAndSendSignals()
    {
        $stockData = $this->getStockDataWithIndicators();

        $scalpingSignals = [];
        $swingSignals = [];

        foreach ($stockData as $stock)
        {
            $scalpSignal = $this->calculateScalpingSignal($stock);
            if ($scalpSignal['signal'] === 'STRONG BUY')
            {
                $scalpingSignals[] = $scalpSignal;
            }

            $swingSignal = $this->calculateSwingSignal($stock);
            if ($swingSignal['signal'] === 'STRONG BUY')
            {
                $swingSignals[] = $swingSignal;
            }
        }

        usort($scalpingSignals, function ($a, $b)
        {
            return $b['score'] - $a['score'];
        });
        usort($swingSignals, function ($a, $b)
        {
            return $b['score'] - $a['score'];
        });

        if (!empty($scalpingSignals))
        {
            $this->sendScalpingSignals($scalpingSignals);
        }

        if (!empty($swingSignals))
        {
            $this->sendSwingSignals($swingSignals);
        }

        return response()->json([
            'success' => true,
            'scalping_signals' => count($scalpingSignals),
            'swing_signals' => count($swingSignals),
            'sent_at' => now()->format('Y-m-d H:i:s')
        ]);
    }

    private function calculateEMA($data, $period)
    {
        if (count($data) < $period) return end($data);

        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($data, 0, $period)) / $period;

        for ($i = $period; $i < count($data); $i++)
        {
            $ema = ($data[$i] - $ema) * $multiplier + $ema;
        }

        return $ema;
    }

    private function calculateSMA($data, $period)
    {
        if (count($data) < $period) return end($data);
        return array_sum(array_slice($data, -$period)) / $period;
    }

    private function calculateRMA($data, $period)
    {
        if (count($data) < $period) return end($data);

        $alpha = 1.0 / $period;
        $rma = $data[0];

        for ($i = 1; $i < count($data); $i++)
        {
            $rma = $alpha * $data[$i] + (1 - $alpha) * $rma;
        }

        return $rma;
    }

    private function calculateADX($highs, $lows, $closes, $period = 14)
    {
        $count = count($closes);
        if ($count < $period + 1) return 20;

        $plusDMs = [];
        $minusDMs = [];
        $trs = [];

        for ($i = 1; $i < $count; $i++)
        {
            $up = $highs[$i] - $highs[$i - 1];
            $down = $lows[$i - 1] - $lows[$i];

            $plusDM = ($up > $down && $up > 0) ? $up : 0;
            $minusDM = ($down > $up && $down > 0) ? $down : 0;

            $tr = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i - 1]),
                abs($lows[$i] - $closes[$i - 1])
            );

            $plusDMs[] = $plusDM;
            $minusDMs[] = $minusDM;
            $trs[] = $tr;
        }

        $atrR = $this->calculateRMA($trs, $period);
        $plusDMr = $this->calculateRMA($plusDMs, $period);
        $minusDMr = $this->calculateRMA($minusDMs, $period);

        if ($atrR == 0) return 20;

        $plusDI = 100 * ($plusDMr / $atrR);
        $minusDI = 100 * ($minusDMr / $atrR);

        $sum = $plusDI + $minusDI;
        if ($sum == 0) return 20;

        $dx = 100 * abs($plusDI - $minusDI) / $sum;

        return $dx;
    }

    private function detectBullishDivergence($rsis, $lows, $lookback = 5)
    {
        $count = count($rsis);
        if ($count < $lookback * 2 + 1) return false;

        $rsiPivotIndex = -1;
        $rsiPivotValue = PHP_FLOAT_MAX;

        for ($i = $count - $lookback * 2 - 1; $i < $count - $lookback; $i++)
        {
            $isPivot = true;
            for ($j = 1; $j <= $lookback; $j++)
            {
                if ($rsis[$i] >= $rsis[$i - $j] || $rsis[$i] >= $rsis[$i + $j])
                {
                    $isPivot = false;
                    break;
                }
            }

            if ($isPivot && $rsis[$i] < $rsiPivotValue)
            {
                $rsiPivotValue = $rsis[$i];
                $rsiPivotIndex = $i;
            }
        }

        if ($rsiPivotIndex === -1) return false;

        $currentRSI = end($rsis);
        $pivotRSI = $rsis[$rsiPivotIndex];
        $currentLow = end($lows);
        $pivotLow = $lows[$rsiPivotIndex];

        return ($pivotRSI < $currentRSI && $pivotLow > $currentLow);
    }

    private function detectBearishDivergence($rsis, $highs, $lookback = 5)
    {
        $count = count($rsis);
        if ($count < $lookback * 2 + 1) return false;

        $rsiPivotIndex = -1;
        $rsiPivotValue = -PHP_FLOAT_MAX;

        for ($i = $count - $lookback * 2 - 1; $i < $count - $lookback; $i++)
        {
            $isPivot = true;
            for ($j = 1; $j <= $lookback; $j++)
            {
                if ($rsis[$i] <= $rsis[$i - $j] || $rsis[$i] <= $rsis[$i + $j])
                {
                    $isPivot = false;
                    break;
                }
            }

            if ($isPivot && $rsis[$i] > $rsiPivotValue)
            {
                $rsiPivotValue = $rsis[$i];
                $rsiPivotIndex = $i;
            }
        }

        if ($rsiPivotIndex === -1) return false;

        $currentRSI = end($rsis);
        $pivotRSI = $rsis[$rsiPivotIndex];
        $currentHigh = end($highs);
        $pivotHigh = $highs[$rsiPivotIndex];

        return ($pivotRSI > $currentRSI && $pivotHigh < $currentHigh);
    }

    private function findSupportResistance($highs, $lows, $closes, $lookback = 50)
    {
        $count = count($closes);
        if ($count < $lookback * 2 + 1) return ['supports' => [], 'resistances' => []];

        $supports = [];
        $resistances = [];

        for ($i = $lookback; $i < $count - $lookback; $i++)
        {
            $isResistance = true;
            $isSupport = true;

            for ($j = 1; $j <= $lookback; $j++)
            {
                if ($highs[$i] <= $highs[$i - $j] || $highs[$i] <= $highs[$i + $j])
                {
                    $isResistance = false;
                }
                if ($lows[$i] >= $lows[$i - $j] || $lows[$i] >= $lows[$i + $j])
                {
                    $isSupport = false;
                }
            }

            if ($isResistance && count($resistances) < 10)
            {
                $resistances[] = $highs[$i];
            }
            if ($isSupport && count($supports) < 10)
            {
                $supports[] = $lows[$i];
            }
        }

        return ['supports' => $supports, 'resistances' => $resistances];
    }

    private function isNearLevel($price, $levels, $tolerance = 1.0)
    {
        foreach ($levels as $level)
        {
            if (abs($price - $level) / $price < ($tolerance / 100))
            {
                return true;
            }
        }
        return false;
    }

    private function calculateScalpingSignal($data)
    {
        $score = 0;
        $signal = 'NEUTRAL';

        $close = floatval($data['close']);
        $open = floatval($data['open']);
        $high = floatval($data['high']);
        $low = floatval($data['low']);
        $volume = $this->parseVolume($data['volume']);
        $ema20 = floatval($data['EMA20'] ?? $close);
        $ema60 = floatval($data['EMA60'] ?? $close);
        $rsi = floatval($data['RSI'] ?? 50);
        $vwap = floatval($data['VWAP'] ?? $close);
        $atr = floatval($data['ATR'] ?? ($high - $low));
        $volumeSMA = floatval($data['volume_sma'] ?? $volume);
        $macdLine = floatval($data['MACD.macd'] ?? 0);
        $macdSignal = floatval($data['MACD.signal'] ?? 0);
        $adx = floatval($data['ADX'] ?? 20);

        $highs = $data['highs'] ?? [$high];
        $lows = $data['lows'] ?? [$low];
        $closes = $data['closes'] ?? [$close];
        $rsis = $data['rsis'] ?? [$rsi];

        $macdBull = $macdLine > $macdSignal && $macdLine > ($data['prev_macd'] ?? $macdLine);
        $macdBear = $macdLine < $macdSignal && $macdLine < ($data['prev_macd'] ?? $macdLine);

        $rsiBull = $rsi > 54;
        $rsiBear = $rsi < 46;

        $volMultiplier = 1.1;
        $volSpike = $volume > ($volumeSMA * $volMultiplier);

        $marketState = 'RANGING';
        if ($adx > 25) $marketState = 'TRENDING';
        elseif ($adx > 15) $marketState = 'MILD';

        $adjustedVolMult = $volMultiplier;
        $adjustedADX = 18.0;

        if ($marketState == 'TRENDING')
        {
            $adjustedVolMult = $volMultiplier * 1.2;
            $adjustedADX = 25.0;
        }
        elseif ($marketState == 'RANGING')
        {
            $adjustedVolMult = $volMultiplier * 0.9;
            $adjustedADX = 15.0;
        }

        $adxThreshold = 15;
        $finalVolMultiplier = $adjustedVolMult;

        $bullishDivRSI = $this->detectBullishDivergence($rsis, $lows, 5);
        $bearishDivRSI = $this->detectBearishDivergence($rsis, $highs, 5);

        $srLevels = $this->findSupportResistance($highs, $lows, $closes, 50);
        $nearSupport = $this->isNearLevel($close, $srLevels['supports'], 1.0);
        $nearResistance = $this->isNearLevel($close, $srLevels['resistances'], 1.0);

        $longBase = $macdBull && $volSpike && $close > $vwap && $ema20 > $ema60 && $close > 50;
        $longCondition = $longBase && $rsiBull && $adx > $adxThreshold;

        if ($longCondition && !$bearishDivRSI)
        {
            $score += 30;
        }

        if ($longCondition && ($nearSupport || !$nearResistance))
        {
            $score += 20;
        }

        if ($rsi > 30 && $rsi < 70)
        {
            $score += 20;
            if ($rsi < 40) $score += 5;
            if ($rsi > 60) $score -= 5;
        }

        if ($close > $ema20 && $ema20 > $ema60)
        {
            $score += 25;
        }
        elseif ($close < $ema20 && $ema20 < $ema60)
        {
            $score -= 25;
        }

        $bodySize = abs($close - $open);
        $candleRange = $high - $low;
        $bodyRatio = $candleRange > 0 ? ($bodySize / $candleRange) : 0;

        if ($bodyRatio > 0.6)
        {
            if ($close > $open) $score += 20;
            else $score -= 20;
        }

        $volRatio = $volumeSMA > 0 ? ($volume / $volumeSMA) : 1;
        if ($volRatio > 1.2) $score += 15;

        $momentum = $ema60 > 0 ? (($close - $ema60) / $ema60) * 100 : 0;
        if ($momentum > 0.5) $score += 10;
        elseif ($momentum < -0.5) $score -= 10;

        if ($close > $vwap) $score += 10;
        else $score -= 10;

        if ($adx > 25) $score += 15;
        elseif ($adx > 15) $score += 10;

        if ($macdBull) $score += 15;
        elseif ($macdBear) $score -= 15;

        if ($bullishDivRSI) $score += 20;
        if ($bearishDivRSI) $score -= 20;

        if ($score >= 70) $signal = 'STRONG BUY';
        elseif ($score >= 50) $signal = 'BUY';
        elseif ($score <= -50) $signal = 'SELL';
        elseif ($score <= -70) $signal = 'STRONG SELL';

        $atrMultiplierTP1 = 1.5;
        $atrMultiplierTP2 = 2.5;
        $atrMultiplierTP3 = 3.5;
        $slAtrMult = 1.2;

        $stopLoss = $close - ($atr * $slAtrMult);
        $entry1 = $close;
        $entry2 = $close + ($atr * 0.3);
        $takeProfit1 = $close + ($atr * $atrMultiplierTP1);
        $takeProfit2 = $close + ($atr * $atrMultiplierTP2);
        $takeProfit3 = $close + ($atr * $atrMultiplierTP3);

        $riskReward = ($stopLoss != $close) ? ($takeProfit1 - $close) / ($close - $stopLoss) : 0;

        $tp1Percent = $close > 0 ? (($takeProfit1 - $close) / $close) * 100 : 0;
        $tp2Percent = $close > 0 ? (($takeProfit2 - $close) / $close) * 100 : 0;
        $tp3Percent = $close > 0 ? (($takeProfit3 - $close) / $close) * 100 : 0;

        return [
            'symbol' => $data['name'],
            'description' => $data['description'],
            'signal' => $signal,
            'score' => round($score, 2),
            'price' => $close,
            'entry1' => round($entry1, 2),
            'entry2' => round($entry2, 2),
            'stopLoss' => round($stopLoss, 2),
            'takeProfit1' => round($takeProfit1, 2),
            'takeProfit2' => round($takeProfit2, 2),
            'takeProfit3' => round($takeProfit3, 2),
            'takeProfit1_percent' => round($tp1Percent, 2),
            'takeProfit2_percent' => round($tp2Percent, 2),
            'takeProfit3_percent' => round($tp3Percent, 2),
            'rsi' => round($rsi, 2),
            'macd' => round($macdLine - $macdSignal, 4),
            'adx' => round($adx, 2),
            'riskReward' => round($riskReward, 2),
            'marketState' => $marketState,
            'volumeRatio' => round($volRatio, 2),
            'bullishDivergence' => $bullishDivRSI,
            'bearishDivergence' => $bearishDivRSI,
            'nearSupport' => $nearSupport,
            'nearResistance' => $nearResistance,
            'timeframe' => 'M1-M5',
            'timestamp' => now()->format('H:i:s')
        ];
    }

    private function calculateSwingSignal($data)
    {
        $score = 0;
        $signal = 'NEUTRAL';
        $trendStrength = 0;

        $close = floatval($data['close']);
        $open = floatval($data['open']);
        $high = floatval($data['high']);
        $low = floatval($data['low']);
        $volume = $this->parseVolume($data['volume']);
        $ema20 = floatval($data['EMA20'] ?? $close);
        $ema60 = floatval($data['EMA60'] ?? $close);
        $ema200 = floatval($data['EMA200'] ?? $close);
        $rsi = floatval($data['RSI'] ?? 50);
        $macdLine = floatval($data['MACD.macd'] ?? 0);
        $macdSignal = floatval($data['MACD.signal'] ?? 0);
        $adx = floatval($data['ADX'] ?? 20);
        $atr = floatval($data['ATR'] ?? ($high - $low));
        $volumeSMA = floatval($data['volume_sma'] ?? $volume);

        $highs = $data['highs'] ?? [$high];
        $lows = $data['lows'] ?? [$low];
        $closes = $data['closes'] ?? [$close];
        $rsis = $data['rsis'] ?? [$rsi];

        $bullishDivRSI = $this->detectBullishDivergence($rsis, $lows, 5);
        $bearishDivRSI = $this->detectBearishDivergence($rsis, $highs, 5);

        $srLevels = $this->findSupportResistance($highs, $lows, $closes, 50);
        $nearSupport = $this->isNearLevel($close, $srLevels['supports'], 1.0);
        $nearResistance = $this->isNearLevel($close, $srLevels['resistances'], 1.0);

        if ($ema20 > $ema60 && $ema60 > $ema200)
        {
            $score += 30;
            $trendStrength = 2;
        }
        elseif ($ema20 < $ema60 && $ema60 < $ema200)
        {
            $score -= 30;
            $trendStrength = -2;
        }
        elseif ($ema20 > $ema60)
        {
            $score += 15;
            $trendStrength = 1;
        }
        elseif ($ema20 < $ema60)
        {
            $score -= 15;
            $trendStrength = -1;
        }

        if ($macdLine > $macdSignal && $macdLine > 0)
        {
            $score += 25;
        }
        elseif ($macdLine < $macdSignal && $macdLine < 0)
        {
            $score -= 25;
        }
        elseif ($macdLine > $macdSignal)
        {
            $score += 15;
        }

        if ($rsi > 40 && $rsi < 60)
        {
            $score += 20;
        }
        elseif ($rsi < 35)
        {
            $score += 15;
        }
        elseif ($rsi > 65)
        {
            $score -= 15;
        }

        $volRatio = $volumeSMA > 0 ? ($volume / $volumeSMA) : 1;
        if ($volRatio > 1.3)
        {
            $score += 15;
        }
        elseif ($volRatio < 0.7)
        {
            $score -= 10;
        }

        if ($adx > 25) $score += 10;
        elseif ($adx < 20) $score -= 5;

        if ($bullishDivRSI) $score += 20;
        if ($bearishDivRSI) $score -= 20;

        if ($nearSupport && $trendStrength > 0) $score += 15;
        if ($nearResistance && $trendStrength > 0) $score -= 10;

        if ($score >= 70 && $trendStrength > 0) $signal = 'STRONG BUY';
        elseif ($score >= 50 && $trendStrength > 0) $signal = 'BUY';
        elseif ($score >= 30) $signal = 'WEAK BUY';
        elseif ($score <= -70 && $trendStrength < 0) $signal = 'STRONG SELL';
        elseif ($score <= -50 && $trendStrength < 0) $signal = 'SELL';

        $atrMultiplierTP1 = 3.0;
        $atrMultiplierTP2 = 5.0;
        $atrMultiplierTP3 = 7.0;
        $slAtrMult = 2.0;

        $stopLoss = $close - ($atr * $slAtrMult);
        $entry1 = $close;
        $entry2 = $close + ($atr * 0.5);
        $takeProfit1 = $close + ($atr * $atrMultiplierTP1);
        $takeProfit2 = $close + ($atr * $atrMultiplierTP2);
        $takeProfit3 = $close + ($atr * $atrMultiplierTP3);

        $riskReward = ($stopLoss != $close) ? ($takeProfit1 - $close) / ($close - $stopLoss) : 0;

        $tp1Percent = $close > 0 ? (($takeProfit1 - $close) / $close) * 100 : 0;
        $tp2Percent = $close > 0 ? (($takeProfit2 - $close) / $close) * 100 : 0;
        $tp3Percent = $close > 0 ? (($takeProfit3 - $close) / $close) * 100 : 0;

        return [
            'symbol' => $data['name'],
            'description' => $data['description'],
            'signal' => $signal,
            'score' => round($score, 2),
            'price' => $close,
            'entry1' => round($entry1, 2),
            'entry2' => round($entry2, 2),
            'stopLoss' => round($stopLoss, 2),
            'takeProfit1' => round($takeProfit1, 2),
            'takeProfit2' => round($takeProfit2, 2),
            'takeProfit3' => round($takeProfit3, 2),
            'takeProfit1_percent' => round($tp1Percent, 2),
            'takeProfit2_percent' => round($tp2Percent, 2),
            'takeProfit3_percent' => round($tp3Percent, 2),
            'rsi' => round($rsi, 2),
            'macd' => round($macdLine - $macdSignal, 4),
            'adx' => round($adx, 2),
            'riskReward' => round($riskReward, 2),
            'trendStrength' => $trendStrength,
            'volumeRatio' => round($volRatio, 2),
            'bullishDivergence' => $bullishDivRSI,
            'bearishDivergence' => $bearishDivRSI,
            'nearSupport' => $nearSupport,
            'nearResistance' => $nearResistance,
            'timeframe' => 'H1-D1',
            'timestamp' => now()->format('H:i:s')
        ];
    }

    private function sendScalpingSignals($signals)
    {
        $header = "<b>SCALPING SIGNALS (M1-M5)</b>\n";
        $header .= "Date/Time: " . now()->format('d M Y H:i:s') . " WIB\n";
        $header .= "==========================================\n\n";

        $messages = [];
        $current = '';
        $maxLen = 4000;
        $bodies = [];

        foreach ($signals as $index => $signal)
        {
            $sentAt = now()->startOfDay();
            SignalHistory::updateOrCreate(
                [
                    'symbol' => $signal['symbol'],
                    'signal_type' => 'scalping',
                    'sent_at' => $sentAt,
                ],
                [
                    'signal' => $signal['signal'],
                    'signal_price' => $signal['price'],
                    'extra' => json_encode($signal),
                ]
            );

            $num = $index + 1;
            $symbol = $signal['symbol'];
            $desc = $signal['description'];
            $price = number_format($signal['price'], 0, ',', '.');
            $sig = $signal['signal'];
            $score = $signal['score'];
            $entry1 = number_format($signal['entry1'], 0, ',', '.');
            $entry2 = number_format($signal['entry2'], 0, ',', '.');
            $stopLoss = number_format($signal['stopLoss'], 0, ',', '.');
            $tp1 = number_format($signal['takeProfit1'], 0, ',', '.');
            $tp2 = number_format($signal['takeProfit2'], 0, ',', '.');
            $tp3 = number_format($signal['takeProfit3'], 0, ',', '.');
            $tp1p = $signal['takeProfit1_percent'];
            $tp2p = $signal['takeProfit2_percent'];
            $tp3p = $signal['takeProfit3_percent'];

            $body = "#{$num} {$symbol}\n";
            $body .= "{$desc}\n";
            $body .= "Price: {$price}\n";
            $body .= "Signal: {$sig} (Score: {$score})\n";
            $body .= "Entry: {$entry1} - {$entry2}\n";
            $body .= "TP 1: {$tp1} ({$tp1p}%) | TP 2: {$tp2} ({$tp2p}%) | TP 3: {$tp3} ({$tp3p}%)\n";
            $body .= "SL: {$stopLoss}\n";
            $body .= "==========================================\n\n";
            $bodies[] = $body;
        }

        $page = 1;
        $pages = [];
        $current = '';
        foreach ($bodies as $body)
        {
            if (strlen($header . $current . $body) > $maxLen && $current !== '')
            {
                $pages[] = $current;
                $current = '';
            }
            $current .= $body;
        }
        if ($current !== '')
        {
            $pages[] = $current;
        }

        $totalPages = count($pages);
        foreach ($pages as $i => $content)
        {
            $pageHeader = $header . "<b>Page " . ($i + 1) . " of {$totalPages}</b>\n\n";
            $msg = $pageHeader . $content;
            if ($this->scalpingThreadID)
            {
                TelegramModel::sendMessageThread($this->tokenBot, $this->chatID, $this->scalpingThreadID, $msg);
            }
            else
            {
                TelegramModel::sendMessage($this->tokenBot, $this->chatID, $msg);
            }
        }
    }

    private function sendSwingSignals($signals)
    {
        $header = "<b>SWING TRADING SIGNALS (H1-D1)</b>\n";
        $header .= "Date/Time: " . now()->format('d M Y H:i:s') . " WIB\n";
        $header .= "==========================================\n\n";

        $messages = [];
        $current = '';
        $maxLen = 4000;
        $bodies = [];

        foreach ($signals as $index => $signal)
        {
            $sentAt = now()->startOfDay();
            SignalHistory::updateOrCreate(
                [
                    'symbol' => $signal['symbol'],
                    'signal_type' => 'swing',
                    'sent_at' => $sentAt,
                ],
                [
                    'signal' => $signal['signal'],
                    'signal_price' => $signal['price'],
                    'extra' => json_encode($signal),
                ]
            );

            $num = $index + 1;
            $symbol = $signal['symbol'];
            $desc = $signal['description'];
            $price = number_format($signal['price'], 0, ',', '.');
            $sig = $signal['signal'];
            $score = $signal['score'];
            $entry1 = number_format($signal['entry1'], 0, ',', '.');
            $entry2 = number_format($signal['entry2'], 0, ',', '.');
            $stopLoss = number_format($signal['stopLoss'], 0, ',', '.');
            $tp1 = number_format($signal['takeProfit1'], 0, ',', '.');
            $tp2 = number_format($signal['takeProfit2'], 0, ',', '.');
            $tp3 = number_format($signal['takeProfit3'], 0, ',', '.');
            $tp1p = $signal['takeProfit1_percent'];
            $tp2p = $signal['takeProfit2_percent'];
            $tp3p = $signal['takeProfit3_percent'];

            $body = "#{$num} {$symbol}\n";
            $body .= "{$desc}\n";
            $body .= "Price: {$price}\n";
            $body .= "Signal: {$sig} (Score: {$score})\n";
            $body .= "Entry: {$entry1} - {$entry2}\n";
            $body .= "TP 1: {$tp1} ({$tp1p}%) | TP 2: {$tp2} ({$tp2p}%) | TP 3: {$tp3} ({$tp3p}%)\n";
            $body .= "SL: {$stopLoss}\n";
            $body .= "==========================================\n\n";
            $bodies[] = $body;
        }

        $page = 1;
        $pages = [];
        $current = '';
        foreach ($bodies as $body)
        {
            if (strlen($header . $current . $body) > $maxLen && $current !== '')
            {
                $pages[] = $current;
                $current = '';
            }
            $current .= $body;
        }
        if ($current !== '')
        {
            $pages[] = $current;
        }

        $totalPages = count($pages);
        foreach ($pages as $i => $content)
        {
            $pageHeader = $header . "<b>Page " . ($i + 1) . " of {$totalPages}</b>\n\n";
            $msg = $pageHeader . $content;
            if ($this->swingThreadID)
            {
                TelegramModel::sendMessageThread($this->tokenBot, $this->chatID, $this->swingThreadID, $msg);
            }
            else
            {
                TelegramModel::sendMessage($this->tokenBot, $this->chatID, $msg);
            }
        }
    }

    private function getStockDataWithIndicators()
    {
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
                    "close",
                    "open",
                    "high",
                    "low",
                    "volume",
                    "EMA20",
                    "EMA60",
                    "EMA200",
                    "RSI",
                    "VWAP",
                    "ATR",
                    "BB.upper",
                    "BB.lower",
                    "SMA20",
                    "MACD.macd",
                    "MACD.signal",
                    "ADX",
                    "Stoch.K",
                    "close|1",
                    "high|1",
                    "low|1",
                    "close|5",
                    "high|5",
                    "low|5",
                    "close|15",
                    "high|15",
                    "low|15",
                    "logoid"
                ],
                "filter": [
                    { "left": "volume", "operation": "greater", "right": 100000 },
                    { "left": "close", "operation": "greater", "right": 50 },
                    { "left": "is_primary", "operation": "equal", "right": true }
                ],
                "ignore_unknown_fields": false,
                "options": { "lang": "en" },
                "range": [0, 1000],
                "sort": { "sortBy": "volume", "sortOrder": "desc" },
                "symbols": {},
                "markets": ["indonesia"]
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);
        $results = [];

        if (isset($data['data']))
        {
            foreach ($data['data'] as $item)
            {
                $close = $item['d'][2] ?? 0;
                $high = $item['d'][4] ?? 0;
                $low = $item['d'][5] ?? 0;
                $volume = $item['d'][6] ?? 0;

                $close1 = $item['d'][20] ?? $close;
                $high1 = $item['d'][21] ?? $high;
                $low1 = $item['d'][22] ?? $low;

                $close5 = $item['d'][23] ?? $close;
                $high5 = $item['d'][24] ?? $high;
                $low5 = $item['d'][25] ?? $low;

                $close15 = $item['d'][26] ?? $close;
                $high15 = $item['d'][27] ?? $high;
                $low15 = $item['d'][28] ?? $low;

                $closes = [$close15, $close5, $close1, $close];
                $highs = [$high15, $high5, $high1, $high];
                $lows = [$low15, $low5, $low1, $low];

                $rsi = $item['d'][10] ?? 50;
                $rsis = array_fill(0, 20, $rsi);
                for ($i = 0; $i < 4; $i++)
                {
                    $rsis[16 + $i] = $rsi + (($i - 1.5) * 2);
                }

                $volumeData = array_fill(0, 20, $this->parseVolume($volume));
                $volumeSMA = array_sum($volumeData) / 20;

                $macdLine = $item['d'][16] ?? 0;
                $prevMACD = $macdLine - 0.1;

                $logo = 'https://s3-symbol-logo.tradingview.com/' . $item['d'][29] . '.svg';

                $results[] = [
                    'name' => $item['d'][0] ?? '',
                    'description' => $item['d'][1] ?? '',
                    'logoid' => $logo,
                    'close' => $close,
                    'open' => $item['d'][3] ?? 0,
                    'high' => $high,
                    'low' => $low,
                    'volume' => $volume,
                    'EMA20' => $item['d'][7] ?? 0,
                    'EMA60' => $item['d'][8] ?? 0,
                    'EMA200' => $item['d'][9] ?? null,
                    'RSI' => $rsi,
                    'VWAP' => $item['d'][11] ?? 0,
                    'ATR' => $item['d'][12] ?? 0,
                    'BB.upper' => $item['d'][13] ?? null,
                    'BB.lower' => $item['d'][14] ?? null,
                    'SMA20' => $item['d'][15] ?? 0,
                    'MACD.macd' => $macdLine,
                    'MACD.signal' => $item['d'][17] ?? 0,
                    'ADX' => $item['d'][18] ?? 20,
                    'Stoch.K' => $item['d'][19] ?? null,
                    'closes' => $closes,
                    'highs' => $highs,
                    'lows' => $lows,
                    'rsis' => $rsis,
                    'volume_sma' => $volumeSMA,
                    'prev_macd' => $prevMACD,
                ];
            }
        }

        return $results;
    }

    private function parseVolume($volumeStr)
    {
        if (is_numeric($volumeStr))
        {
            return floatval($volumeStr);
        }

        $volumeStr = str_replace([' ', ','], '', strtoupper($volumeStr));

        if (strpos($volumeStr, 'T') !== false)
        {
            return floatval($volumeStr) * 1000000000000;
        }
        elseif (strpos($volumeStr, 'B') !== false)
        {
            return floatval($volumeStr) * 1000000000;
        }
        elseif (strpos($volumeStr, 'M') !== false)
        {
            return floatval($volumeStr) * 1000000;
        }
        elseif (strpos($volumeStr, 'K') !== false)
        {
            return floatval($volumeStr) * 1000;
        }

        return floatval($volumeStr);
    }

    public function getAllSignals()
    {
        $stockData = $this->getStockDataWithIndicators();

        $scalpingSignals = [];
        $swingSignals = [];

        foreach ($stockData as $stock)
        {
            $scalpSignal = $this->calculateScalpingSignal($stock);
            $swingSignal = $this->calculateSwingSignal($stock);

            $scalpSignal['logo'] = $stock['logoid'];
            $swingSignal['logo'] = $stock['logoid'];

            $scalpingSignals[] = $scalpSignal;
            $swingSignals[] = $swingSignal;
        }

        usort($scalpingSignals, function ($a, $b)
        {
            return $b['score'] - $a['score'];
        });
        usort($swingSignals, function ($a, $b)
        {
            return $b['score'] - $a['score'];
        });

        return response()->json([
            'scalping_signals' => $scalpingSignals,
            'swing_signals' => $swingSignals,
            'last_updated' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
