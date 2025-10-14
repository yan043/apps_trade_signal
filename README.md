<h1 align="center">Signal Stock Indonesia & Crypto – Dashboard</h1>

Dashboard ringan untuk memantau saham Indonesia dan crypto dengan data dari TradingView. Halaman utama menampilkan dua tabel: Top Volume (kandidat beli) dan Technical Ratings (Strong). Data diperbarui otomatis tiap 15 menit tanpa reload halaman dengan live market session tracking.

## Fitur Utama

- **Top Volume — Buy Candidates** (filter: AnalystRating Buy/Strong Buy)
- **Technical Ratings — Strong** (kombinasi Tech/MAs/Osc Strong Buy/Buy)
- **Market Session Indicator** - Real-time status perdagangan IDX dengan warna (Open: hijau, Close: merah, Break: kuning)
- **Asia/Jakarta Timezone** - Semua waktu menggunakan zona waktu Indonesia secara konsisten
- **Auto-refresh 15 menit** dengan countdown timer real-time di header card
- **Tabel interaktif** (DataTables: search, sort, pagination) tanpa kehilangan state
- **Match Indicator** - Highlighting otomatis untuk stocks yang muncul di kedua tabel
- **Collapsible Cards** - Toggle show/hide untuk setiap tabel dengan animasi chevron
- **Responsive Design** - Optimized untuk desktop dan mobile
- **Visual Feedback** - Loading states dan update animations
- **Watermark diagonal** ala Word (non-intrusif, tidak mengganggu klik)

## Arsitektur & Alur Data

- Route: `GET /` → `DashboardController@index`
	- Memanggil dua method privat untuk ambil data awal:
		- `stock_top_volume_for_buy()` → memanggil TradingView Scanner market "indonesia" (POST scan) dan memfilter baris dengan `AnalystRating` ∈ {Buy, StrongBuy}. Data diolah: logo, name, description, close, currency, change, value, analystRating.
		- `stock_technical_analysis()` → memanggil TradingView Scanner dengan kolom TechRating/MARating/OsRating. Disaring pada kombinasi sinyal kuat (StrongBuy/Buy) dan diubah ke label yang ramah (Strong Buy/Buy).
- API: `GET /api/stock-data` → `ApiController@getStockData`
	- Mengembalikan JSON: `stock_top_volume_for_buy`, `stock_technical_analysis`, `last_updated`.
- View: `resources/views/dashboard.blade.php`
	- **Market Session Tracking**: Live monitoring status perdagangan IDX (Session 2) dengan timezone Asia/Jakarta
	- **Real-time Clock**: Waktu Indonesia dengan format lengkap di header dashboard
	- **Color-coded Sessions**: 
		- 🟢 Session 2: Open (hijau) - saat pasar sedang aktif
		- 🔴 Session 2: Close (merah) - saat pasar tutup atau di luar jam trading
		- 🟡 Session 2: Break (kuning) - saat istirahat siang
	- **Dua tabel DataTables** dengan fitur lengkap: search, sort, pagination
	- **Auto-refresh** via AJAX ke `/api/stock-data` setiap 15 menit
	- **Match Detection System**: Otomatis mendeteksi dan highlight stocks yang muncul di kedua tabel
	- **Visual Effects**: 
		- Loading states dengan opacity transition saat updating
		- Success flash dengan background hijau setelah update
		- Yellow highlight untuk matching stocks
		- Golden badge "★ MATCH" untuk stocks yang cocok
	- **Countdown Timer**: Real-time countdown (15:00 → 00:00) di header setiap card
	- **Collapsible Interface**: Toggle show/hide tabel dengan chevron rotation animation
	- **Responsive Layout**: Bootstrap grid system untuk mobile/desktop
	- **Error Handling**: Fallback methods untuk DataTable operations
	- **Proteksi CSRF**: Meta token untuk semua AJAX requests
	- **Watermark**: "Data Powered by TradingView" dan "Github @yan043"

## Sumber Data (TradingView Scanner)

Aplikasi melakukan HTTP POST ke endpoint TradingView Scanner:

- `https://scanner.tradingview.com/indonesia/scan?label-product=markets-screener`

Payload berisi daftar kolom yang dibutuhkan dan pengurutan, lalu hasil dipilah/di-format di server sebelum dikirim ke klien. Nilai seperti `StrongBuy` diubah menjadi label "Strong Buy" agar konsisten di UI.

## Cara Menjalankan (Windows/PowerShell)

1. Pastikan PHP dan Composer sudah terpasang (repo ini sudah vendor-ready).
2. Jalankan server pengembangan Laravel:

	 ```powershell
	 php artisan serve
	 ```

3. Buka browser ke alamat yang ditampilkan (default: http://127.0.0.1:8000).

Catatan: Aplikasi memuat CSS/JS DataTables dan Bootstrap dari CDN.

## Jadwal Market Session IDX

Dashboard menampilkan status perdagangan real-time berdasarkan jadwal resmi IDX:

**Senin - Kamis:**
- Session 2: 13:30 - 16:30 (Open - hijau)
- Break: 12:00 - 13:30 (Break - kuning)  
- Outside hours: (Close - merah)

**Jumat:**
- Session 2: 14:00 - 16:30 (Open - hijau)
- Break: 11:30 - 14:00 (Break - kuning)
- Outside hours: (Close - merah)

**Sabtu - Minggu:**
- Weekend (Close - merah)

## Kustomisasi Singkat

- **Market session timing**: ubah jam trading di function `updateMarketSessions()` 
- **Interval refresh**: ubah di `dashboard.blade.php` (nilai `timeRemaining = 15 * 60;`)
- **Session colors**: sesuaikan class `.value-up` (hijau), `.value-down` (merah), `.text-warning` (kuning)
- **Countdown/tampilan**: CSS ada di `<style>` pada `dashboard.blade.php`
- **Match indicator styling**: sesuaikan class `.match-indicator` dan `.stock-match-highlight` di CSS
- **Filter logika sinyal**: sesuaikan di `DashboardController` dan/atau `ApiController` pada kondisi filtering array `$item['d'][idx]`
- **Timezone settings**: ubah "Asia/Jakarta" di function `updateMarketSessions()` dan `showTime()`
- **Collapsible behavior**: modify Bootstrap collapse classes di HTML dan JavaScript event listeners
- **Loading animations**: customize `.table-updating` dan `.table-updated` CSS classes
- **Route/API**: `routes/web.php`

## Fitur Match Detection

Sistem otomatis yang mendeteksi stocks yang muncul di kedua tabel:

- **Real-time Detection**: Berjalan setiap kali data di-refresh atau halaman dimuat
- **Visual Highlighting**: Background kuning dengan border kiri emas
- **Match Badge**: Icon bintang dengan label "MATCH" 
- **Cross-table Sync**: Highlight muncul simultan di kedua tabel
- **Error Resilient**: Fallback method jika DataTable API gagal

## Kredit

- **Data Powered by TradingView**
- **Created by Mahdian (@yan043)**
