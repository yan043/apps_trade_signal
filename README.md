<h1 align="center">Signal Stock Indonesia & Crypto – Dashboard</h1>

Lightweight dashboard for monitoring Indonesian stocks and crypto with data from TradingView. The main page displays a single comprehensive table with stock market data including Price to Earnings ratios. Data updates automatically every 15 minutes without page reload with live market session tracking.

## Key Features

- **Stock Market - Price to Earnings** (filter: P/E ratio > 25, AnalystRating: StrongSell/Sell/Buy/StrongBuy)
- **Market Session Indicator** - Real-time IDX trading status with colors (Open: green, Closed: red, Break: yellow)
- **Asia/Jakarta Timezone** - All times use Indonesian timezone consistently  
- **Auto-refresh 15 minutes** with real-time countdown timer in card header
- **Interactive table** (DataTables: search, sort, pagination) without losing state
- **Match Indicator** - Automatic highlighting for stocks appearing in both Top Gainers and Most Active
- **Collapsible Cards** - Toggle show/hide for table with chevron animation
- **Responsive Design** - Optimized for desktop and mobile
- **Visual Feedback** - Loading states and update animations
- **Diagonal watermark** Word-style (non-intrusive, doesn't interfere with clicks)
- **Indonesian Number Formatting** - Displays numbers with dot separators (3890 → 3.890)
- **Plus Signs for Positive Changes** - Shows + prefix for positive price changes

## Architecture & Data Flow

- Route: `GET /` → `DashboardController@index`
	- Calls private method to fetch initial data:
		- `stock_price_to_earnings_ratio()` → calls TradingView Scanner market "indonesia" (POST scan) and filters rows with `price_earnings_ttm` > 25 and `AnalystRating` ∈ {StrongSell, Sell, Buy, StrongBuy}. Data processed: logo, name, description, price, currency, change, open, high, low, volume, price_earnings_ttm, analystRating.
- API: `GET /api/stock-data` → `ApiController@getStockData`
	- Returns JSON: `stock_price_to_earnings_ratio`, `stock_market_movers_gainers`, `stock_most_active`, `last_updated`.
- View: `resources/views/dashboard.blade.php`
	- **Market Session Tracking**: Live monitoring of IDX trading status with Asia/Jakarta timezone
	- **Real-time Clock**: Indonesian time with full format in dashboard header
	- **Color-coded Sessions**: 
		- 🟢 Session I/II: Market Open (green) - when market is active
		- 🔴 Market Closed (red) - when market is closed or outside trading hours
		- ⏸️ Lunch Break (yellow) - during lunch break
		- 📅 Weekend - Market Closed (red) - during weekends
	- **Single DataTables table** with complete features: search, sort, pagination
	- **Auto-refresh** via AJAX to `/api/stock-data` every 15 minutes
	- **Match Detection System**: Automatically detects and highlights stocks appearing in both Top Gainers and Most Active
	- **Visual Effects**: 
		- Loading states with opacity transition when updating
		- Success flash with green background after update
		- Yellow highlight for matching stocks
		- Colored badges "Top Gainers" and "Most Active" for matching stocks
	- **Countdown Timer**: Real-time countdown (15:00 → 00:00) in card header
	- **Collapsible Interface**: Toggle show/hide table with chevron rotation animation
	- **Responsive Layout**: Bootstrap grid system for mobile/desktop
	- **Error Handling**: Fallback methods for DataTable operations
	- **CSRF Protection**: Meta token for all AJAX requests
	- **Watermark**: "Data Powered by TradingView" and "Github @yan043"
	- **Number Formatting**: Indonesian format with dot separators and plus signs for positive changes

## Data Source (TradingView Scanner)

The application makes HTTP POST requests to TradingView Scanner endpoint:

- `https://scanner.tradingview.com/indonesia/scan?label-product=screener-stock`

Payload contains the list of required columns and sorting, then results are filtered/formatted on the server before being sent to the client. Values like `StrongBuy` are kept as-is for consistency in the UI.

## How to Run (Windows/PowerShell)

1. Ensure PHP and Composer are installed (this repo is vendor-ready).
2. Run the Laravel development server:

	 ```powershell
	 php artisan serve
	 ```

3. Open browser to the displayed address (default: http://127.0.0.1:8000).

Note: The application loads DataTables and Bootstrap CSS/JS from CDN.

## IDX Market Session Schedule

Dashboard displays real-time trading status based on official IDX schedule:

**Monday - Thursday:**
- Session I: 09:00 - 12:00 (Market Open - green)
- Lunch Break: 12:00 - 13:30 (Break - yellow)  
- Session II: 13:30 - 16:30 (Market Open - green)
- Outside hours: (Market Closed - red)

**Friday:**
- Session I: 09:00 - 11:30 (Market Open - green)
- Lunch Break: 11:30 - 14:00 (Break - yellow)
- Session II: 14:00 - 16:30 (Market Open - green)
- Outside hours: (Market Closed - red)

**Saturday - Sunday:**
- Weekend - Market Closed (red)

## Credits

- **Data Powered by TradingView**
- **Created by Mahdian (@yan043)**
