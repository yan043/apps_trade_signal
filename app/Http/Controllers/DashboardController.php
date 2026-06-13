<?php

namespace App\Http\Controllers;

use App\Services\MarketDataService;

class DashboardController extends Controller
{
    public function index(MarketDataService $marketData)
    {
        $stock_trend_markets         = $marketData->trendingStocks();
        $stock_market_movers_gainers = $marketData->topGainers();
        $stock_most_active           = $marketData->mostActive();

        return view('dashboard', compact('stock_trend_markets', 'stock_market_movers_gainers', 'stock_most_active'));
    }

    public function signal()
    {
        return view('dashboard-signal');
    }
}
