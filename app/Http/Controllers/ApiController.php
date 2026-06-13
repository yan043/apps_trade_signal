<?php

namespace App\Http\Controllers;

use App\Services\MarketDataService;

class ApiController extends Controller
{
    public function stock_data(MarketDataService $marketData)
    {
        return response()->json([
            'stock_trend_markets'         => $marketData->trendingStocks(),
            'stock_market_movers_gainers' => $marketData->topGainers(),
            'stock_most_active'           => $marketData->mostActive(),
            'last_updated'                => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);
    }
}
