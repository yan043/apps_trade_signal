<?php

namespace App\Http\Controllers;

use App\Models\Signal;
use App\Models\Asset;
use App\Services\ScalpingSignalService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $scalpingService;

    public function __construct(ScalpingSignalService $scalpingService)
    {
        $this->scalpingService = $scalpingService;
    }

    public function index()
    {
        // Get all crypto signals ordered by expected_gain descending
        $cryptoSignals = Signal::with('asset')
            ->whereHas('asset', function ($query)
            {
                $query->where('market', 'crypto');
            })
            ->orderByDesc('expected_gain')
            ->get();

        // Get all stock signals ordered by expected_gain descending
        $stockSignals = Signal::with('asset')
            ->whereHas('asset', function ($query)
            {
                $query->where('market', 'stock');
            })
            ->orderByDesc('expected_gain')
            ->get();

        // Define crypto symbols for scalping analysis - expanded list for more variety
        $cryptoSymbols = [
            'BTCUSDT',
            'ETHUSDT',
            'BNBUSDT',
            'XRPUSDT',
            'ADAUSDT',
            'SOLUSDT',
            'DOGEUSDT',
            'DOTUSDT',
            'MATICUSDT',
            'LTCUSDT',
            'AVAXUSDT',
            'LINKUSDT',
            'UNIUSDT',
            'ALGOUSDT',
            'VETUSDT',
            'ICPUSDT',
            'FILUSDT',
            'TRXUSDT',
            'ETCUSDT',
            'XLMUSDT',
            'THETAUSDT',
            'FTMUSDT',
            'HBARUSDT',
            'NEARUSDT',
            'FLOWUSDT',
            'MANAUSDT',
            'SANDUSDT',
            'AXSUSDT',
            'CHZUSDT',
            'ENJUSDT'
        ];

        $scalpingSignals = [];
        foreach ($cryptoSymbols as $symbol)
        {
            $signal = $this->scalpingService->analyzeScalping($symbol);
            if ($signal !== null && $signal['action'] !== 'HOLD')
            {
                $scalpingSignals[] = $signal;
            }
        }

        // Sort scalping signals by technical strength (prioritize BUY over SELL, then by RSI deviation from neutral)
        usort($scalpingSignals, function ($a, $b)
        {
            // BUY signals first
            if ($a['action'] === 'BUY' && $b['action'] === 'SELL') return -1;
            if ($a['action'] === 'SELL' && $b['action'] === 'BUY') return 1;

            // For same action type, sort by RSI strength (closer to optimal range first)
            if ($a['action'] === 'BUY')
            {
                $aStrength = abs($a['rsi'] - 52.5); // Optimal BUY RSI around 52.5
                $bStrength = abs($b['rsi'] - 52.5);
            }
            else
            {
                $aStrength = abs($a['rsi'] - 47.5); // Optimal SELL RSI around 47.5
                $bStrength = abs($b['rsi'] - 47.5);
            }

            return $aStrength <=> $bStrength;
        });

        return view('dashboard', compact('cryptoSignals', 'stockSignals', 'scalpingSignals'));
    }

    public function refreshData()
    {
        // Only refresh scalping signals dynamically, remove crypto and stock signals to keep data up-to-date

        $cryptoSymbols = [
            'BTCUSDT',
            'ETHUSDT',
            'BNBUSDT',
            'XRPUSDT',
            'ADAUSDT',
            'SOLUSDT',
            'DOGEUSDT',
            'DOTUSDT',
            'MATICUSDT',
            'LTCUSDT',
            'AVAXUSDT',
            'LINKUSDT',
            'UNIUSDT',
            'ALGOUSDT',
            'VETUSDT',
            'ICPUSDT',
            'FILUSDT',
            'TRXUSDT',
            'ETCUSDT',
            'XLMUSDT',
            'THETAUSDT',
            'FTMUSDT',
            'HBARUSDT',
            'NEARUSDT',
            'FLOWUSDT',
            'MANAUSDT',
            'SANDUSDT',
            'AXSUSDT',
            'CHZUSDT',
            'ENJUSDT'
        ];

        $scalpingSignals = [];
        foreach ($cryptoSymbols as $symbol)
        {
            $signal = $this->scalpingService->analyzeScalping($symbol);
            if ($signal !== null && $signal['action'] !== 'HOLD')
            {
                $scalpingSignals[] = $signal;
            }
        }

        // Sort scalping signals by technical strength (prioritize BUY over SELL, then by RSI deviation from neutral)
        usort($scalpingSignals, function ($a, $b)
        {
            // BUY signals first
            if ($a['action'] === 'BUY' && $b['action'] === 'SELL') return -1;
            if ($a['action'] === 'SELL' && $b['action'] === 'BUY') return 1;

            // For same action type, sort by RSI strength (closer to optimal range first)
            if ($a['action'] === 'BUY')
            {
                $aStrength = abs($a['rsi'] - 52.5); // Optimal BUY RSI around 52.5
                $bStrength = abs($b['rsi'] - 52.5);
            }
            else
            {
                $aStrength = abs($a['rsi'] - 47.5); // Optimal SELL RSI around 47.5
                $bStrength = abs($b['rsi'] - 47.5);
            }

            return $aStrength <=> $bStrength;
        });

        return response()->json([
            'scalpingSignals' => $scalpingSignals,
        ]);
    }
}
