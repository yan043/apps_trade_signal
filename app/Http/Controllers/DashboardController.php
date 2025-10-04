<?php

namespace App\Http\Controllers;

use App\Services\RefreshSignalService;
use App\Services\ScalpingSignalService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $scalpingService;
    protected $refreshService;

    public function __construct(
        RefreshSignalService $refreshService,
        ScalpingSignalService $scalpingService
    )
    {
        $this->refreshService  = $refreshService;
        $this->scalpingService = $scalpingService;
    }

    public function index()
    {
        $signals = $this->refreshService->refreshSignals();

        $cryptoSignals = $signals['crypto'] ?? [];
        $stockSignals  = $signals['stock'] ?? [];
        $scalpingSignals = [];

        return view('dashboard', compact('cryptoSignals', 'stockSignals', 'scalpingSignals'));
    }

    public function refreshSignals()
    {
        $signals = $this->refreshService->refreshSignals();

        return response()->json([
            'cryptoSignals' => $signals['crypto'] ?? [],
            'stockSignals'  => $signals['stock'] ?? [],
        ]);
    }

    public function refreshScalping()
    {
        $signals = $this->refreshService->refreshSignals();
        $cryptoSymbols = array_map(fn($s) => $s->asset->symbol, $signals['crypto']);

        $scalpingSignals = $this->scalpingService->analyzeScalpingBatch($cryptoSymbols);

        return response()->json([
            'scalpingSignals' => $scalpingSignals,
        ]);
    }
}
