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
        $cryptoSignals = Signal::with('asset')
            ->whereHas('asset', function ($query)
            {
                $query->where('market', 'crypto');
            })
            ->orderByDesc('expected_gain')
            ->get();

        $stockSignals = Signal::with('asset')
            ->whereHas('asset', function ($query)
            {
                $query->where('market', 'stock');
            })
            ->orderByDesc('expected_gain')
            ->get();

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

        $scalpingSignals = []; // Scalping will be loaded via AJAX

        return view('dashboard', compact('cryptoSignals', 'stockSignals', 'scalpingSignals'));
    }

    public function refresh(\App\Services\SignalService $signalService, \App\Services\ScalpingSignalService $scalpingService)
    {
        $signalService->populateAssets();
        $signalService->analyze();

        $cryptoSignals = \App\Models\Signal::with('asset')->whereHas('asset', fn($q) => $q->where('market', 'crypto'))->get();
        $stockSignals  = \App\Models\Signal::with('asset')->whereHas('asset', fn($q) => $q->where('market', 'stock'))->get();

        $scalpingSignals = [];
        foreach ($cryptoSignals as $signal)
        {
            $symbol = $signal->asset->symbol ?? null;
            if ($symbol)
            {
                $scalp = $scalpingService->analyzeScalping($symbol);
                if ($scalp && $scalp['action'] !== 'HOLD')
                {
                    $scalpingSignals[] = $scalp;
                }
            }
        }

        return response()->json([
            'cryptoSignals'   => $cryptoSignals,
            'stockSignals'    => $stockSignals,
            'scalpingSignals' => $scalpingSignals,
        ]);
    }

    public function refreshSignals(\App\Services\SignalService $signalService)
    {
        $signalService->populateAssets();
        $signalService->analyze();

        $cryptoSignals = \App\Models\Signal::with('asset')->whereHas('asset', fn($q) => $q->where('market', 'crypto'))->get();
        $stockSignals  = \App\Models\Signal::with('asset')->whereHas('asset', fn($q) => $q->where('market', 'stock'))->get();

        return response()->json([
            'cryptoSignals' => $cryptoSignals,
            'stockSignals'  => $stockSignals,
        ]);
    }

    public function refreshScalping(\App\Services\ScalpingSignalService $scalpingService, \App\Services\SignalService $signalService)
    {
        $cryptoSymbolsData = $signalService->fetchTopCryptoSymbols();
        $cryptoSymbols = array_column($cryptoSymbolsData, 'symbol');

        $scalpingSignals = [];
        foreach ($cryptoSymbols as $symbol)
        {
            $signal = $scalpingService->analyzeScalping($symbol);
            if ($signal !== null && $signal['action'] !== 'HOLD')
            {
                $scalpingSignals[] = $signal;
            }
        }

        usort($scalpingSignals, function ($a, $b)
        {
            if ($a['action'] === 'BUY' && $b['action'] === 'SELL') return -1;
            if ($a['action'] === 'SELL' && $b['action'] === 'BUY') return 1;

            if ($a['action'] === 'BUY')
            {
                $aStrength = abs($a['rsi'] - 52.5);
                $bStrength = abs($b['rsi'] - 52.5);
            }
            else
            {
                $aStrength = abs($a['rsi'] - 47.5);
                $bStrength = abs($b['rsi'] - 47.5);
            }

            return $aStrength <=> $bStrength;
        });

        return response()->json([
            'scalpingSignals' => $scalpingSignals,
        ]);
    }
}
