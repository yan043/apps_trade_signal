<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelegramModel;

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
            if ($scalpSignal['signal'] === 'STRONG BUY' || $scalpSignal['signal'] === 'BUY')
            {
                $scalpingSignals[] = $scalpSignal;
            }
            $swingSignal = $this->calculateSwingSignal($stock);
            if ($swingSignal['signal'] === 'STRONG BUY' || $swingSignal['signal'] === 'BUY')
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
            $this->sendScalpingSignals(array_slice($scalpingSignals, 0, 10));
        }

        if (!empty($swingSignals))
        {
            $this->sendSwingSignals(array_slice($swingSignals, 0, 10));
        }

        return response()->json([
            'success' => true,
            'scalping_signals' => count($scalpingSignals),
            'swing_signals' => count($swingSignals),
            'sent_at' => now()->format('Y-m-d H:i:s')
        ]);
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
        $ema9 = floatval($data['EMA9'] ?? 0);
        $ema21 = floatval($data['EMA21'] ?? 0);
        $rsi = floatval($data['RSI'] ?? 50);
        $vwap = floatval($data['VWAP'] ?? $close);
        $atr = floatval($data['ATR'] ?? ($high - $low));
        $volumeSMA = $this->parseVolume($data['volume']) * 0.8;

        if ($rsi > 30 && $rsi < 70)
        {
            $score += 20;
            if ($rsi < 40) $score += 5;
            if ($rsi > 60) $score -= 5;
        }

        if ($close > $ema9 && $ema9 > $ema21)
        {
            $score += 25;
        }
        elseif ($close < $ema9 && $ema9 < $ema21)
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

        $momentum = $ema21 > 0 ? (($close - $ema21) / $ema21) * 100 : 0;
        if ($momentum > 0.5) $score += 10;
        elseif ($momentum < -0.5) $score -= 10;

        if ($close > $vwap) $score += 10;
        else $score -= 10;

        if ($score >= 70) $signal = 'STRONG BUY';
        elseif ($score >= 50) $signal = 'BUY';
        elseif ($score <= -50) $signal = 'SELL';
        elseif ($score <= -70) $signal = 'STRONG SELL';

        $stopLoss = $close - ($atr * 1);
        $entry1 = $close;
        $entry2 = $close + ($atr * 0.3);
        $takeProfit1 = $close + ($atr * 1.5);
        $takeProfit2 = $close + ($atr * 2.5);
        $riskReward = ($takeProfit1 - $close) / ($close - $stopLoss);

        $tp1Percent = $close > 0 ? (($takeProfit1 - $close) / $close) * 100 : 0;
        $tp2Percent = $close > 0 ? (($takeProfit2 - $close) / $close) * 100 : 0;

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
            'takeProfit1_percent' => round($tp1Percent, 2),
            'takeProfit2_percent' => round($tp2Percent, 2),
            'rsi' => round($rsi, 2),
            'riskReward' => round($riskReward, 2),
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
        $ema9 = floatval($data['EMA9'] ?? 0);
        $ema21 = floatval($data['EMA21'] ?? 0);
        $ema50 = floatval($data['EMA50'] ?? 0);
        $rsi = floatval($data['RSI'] ?? 50);
        $macdLine = floatval($data['MACD.macd'] ?? 0);
        $macdSignal = floatval($data['MACD.signal'] ?? 0);
        $adx = floatval($data['ADX'] ?? 20);
        $atr = floatval($data['ATR'] ?? ($high - $low));
        $volumeSMA = $this->parseVolume($data['volume']) * 0.8;

        if ($ema9 > $ema21 && $ema21 > $ema50)
        {
            $score += 30;
            $trendStrength = 2;
        }
        elseif ($ema9 < $ema21 && $ema21 < $ema50)
        {
            $score -= 30;
            $trendStrength = -2;
        }
        elseif ($ema9 > $ema21)
        {
            $score += 15;
            $trendStrength = 1;
        }
        elseif ($ema9 < $ema21)
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

        if ($score >= 70 && $trendStrength > 0) $signal = 'STRONG BUY';
        elseif ($score >= 50 && $trendStrength > 0) $signal = 'BUY';
        elseif ($score >= 30) $signal = 'WEAK BUY';
        elseif ($score <= -70 && $trendStrength < 0) $signal = 'STRONG SELL';
        elseif ($score <= -50 && $trendStrength < 0) $signal = 'SELL';

        $stopLoss = $close - ($atr * 2);
        $entry1 = $close;
        $entry2 = $close + ($atr * 0.5);
        $takeProfit1 = $close + ($atr * 3);
        $takeProfit2 = $close + ($atr * 5);
        $riskReward = ($takeProfit1 - $close) / ($close - $stopLoss);

        $tp1Percent = $close > 0 ? (($takeProfit1 - $close) / $close) * 100 : 0;
        $tp2Percent = $close > 0 ? (($takeProfit2 - $close) / $close) * 100 : 0;

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
            'takeProfit1_percent' => round($tp1Percent, 2),
            'takeProfit2_percent' => round($tp2Percent, 2),
            'rsi' => round($rsi, 2),
            'macd' => round($macdLine - $macdSignal, 4),
            'adx' => round($adx, 2),
            'riskReward' => round($riskReward, 2),
            'trendStrength' => $trendStrength,
            'timeframe' => 'H1-D1',
            'timestamp' => now()->format('H:i:s')
        ];
    }

    private function sendScalpingSignals($signals)
    {
        $message = "<b>SCALPING SIGNALS (M1-M5)</b>\n";
        $message .= "==========================================\n";
        $message .= "Date/Time: " . now()->format('d M Y H:i:s') . " WIB\n\n";

        foreach ($signals as $index => $signal)
        {
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
            $tp1p = $signal['takeProfit1_percent'];
            $tp2p = $signal['takeProfit2_percent'];
            $rsi = $signal['rsi'];
            $rr = $signal['riskReward'];
            $timestamp = $signal['timestamp'];

            $message .= "<b>#{$num} {$symbol}</b>\n";
            $message .= "Company: {$desc}\n";
            $message .= "Current Price: {$price}\n";
            $message .= "Signal: <b>{$sig}</b> (Score: {$score})\n";
            $message .= "Entry Zone: {$entry1} - {$entry2}\n";
            $message .= "Stop Loss: {$stopLoss}\n";
            $message .= "Take Profit 1: {$tp1} ({$tp1p}%)\n";
            $message .= "Take Profit 2: {$tp2} ({$tp2p}%)\n";
            $message .= "==========================================\n\n";
        }

        $message .= "<i>Please use proper risk management!</i>";

        if ($this->scalpingThreadID)
        {
            TelegramModel::sendMessageThread($this->tokenBot, $this->chatID, $this->scalpingThreadID, $message);
        }
        else
        {
            TelegramModel::sendMessage($this->tokenBot, $this->chatID, $message);
        }
    }

    private function sendSwingSignals($signals)
    {
        $message = "<b>SWING TRADING SIGNALS (H1-D1)</b>\n";
        $message .= "==========================================\n";
        $message .= "Date/Time: " . now()->format('d M Y H:i:s') . " WIB\n\n";

        foreach ($signals as $index => $signal)
        {
            $trendLabel = $signal['trendStrength'] == 2 ? 'Strong Uptrend' : ($signal['trendStrength'] == 1 ? 'Uptrend' : 'Neutral');
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
            $tp1p = $signal['takeProfit1_percent'];
            $tp2p = $signal['takeProfit2_percent'];
            $rsi = $signal['rsi'];
            $macd = $signal['macd'];
            $adx = $signal['adx'];
            $rr = $signal['riskReward'];
            $timestamp = $signal['timestamp'];

            $message .= "<b>#{$num} {$symbol}</b> ({$trendLabel})\n";
            $message .= "Company: {$desc}\n";
            $message .= "Current Price: {$price}\n";
            $message .= "Signal: <b>{$sig}</b> (Score: {$score})\n";
            $message .= "Entry Zone: {$entry1} - {$entry2}\n";
            $message .= "Stop Loss: {$stopLoss}\n";
            $message .= "Take Profit 1: {$tp1} ({$tp1p}%)\n";
            $message .= "Take Profit 2: {$tp2} ({$tp2p}%)\n";
            $message .= "==========================================\n\n";
        }

        $message .= "<i>Please perform additional analysis before entry!</i>";

        if ($this->swingThreadID)
        {
            TelegramModel::sendMessageThread($this->tokenBot, $this->chatID, $this->swingThreadID, $message);
        }
        else
        {
            TelegramModel::sendMessage($this->tokenBot, $this->chatID, $message);
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
                    "EMA9",
                    "EMA21",
                    "EMA50",
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
                    "Stoch.K"
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
                $results[] = [
                    'name' => $item['d'][0] ?? '',
                    'description' => $item['d'][1] ?? '',
                    'close' => $item['d'][2] ?? 0,
                    'open' => $item['d'][3] ?? 0,
                    'high' => $item['d'][4] ?? 0,
                    'low' => $item['d'][5] ?? 0,
                    'volume' => $item['d'][6] ?? 0,
                    'EMA9' => $item['d'][7] ?? 0,
                    'EMA21' => $item['d'][8] ?? 0,
                    'EMA50' => $item['d'][9] ?? 0,
                    'EMA200' => $item['d'][10] ?? null,
                    'RSI' => $item['d'][11] ?? 50,
                    'VWAP' => $item['d'][12] ?? 0,
                    'ATR' => $item['d'][13] ?? 0,
                    'BB.upper' => $item['d'][14] ?? null,
                    'BB.lower' => $item['d'][15] ?? null,
                    'SMA20' => $item['d'][16] ?? 0,
                    'MACD.macd' => $item['d'][17] ?? 0,
                    'MACD.signal' => $item['d'][18] ?? 0,
                    'ADX' => $item['d'][19] ?? 20,
                    'Stoch.K' => $item['d'][20] ?? null,
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
