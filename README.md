<h1 align="center">Signal Stock Indonesia & Crypto – Dashboard</h1>

Dashboard ringan untuk memantau saham Indonesia dan crypto dengan data dari TradingView. Halaman utama menampilkan dua tabel: Top Volume (kandidat beli) dan Technical Ratings (Strong). Data diperbarui otomatis tiap 15 menit tanpa reload halaman.

## Fitur Utama

- Top Volume — Buy Candidates (filter: AnalystRating Buy/Strong Buy)
- Technical Ratings — Strong (kombinasi Tech/MAs/Osc Strong Buy/Buy)
- Auto-refresh 15 menit dengan countdown di header card
- Tabel interaktif (DataTables: search, sort, pagination) tanpa kehilangan state
- Watermark diagonal ala Word (non-intrusif, tidak mengganggu klik)

## Arsitektur & Alur Data

- Route: `GET /` → `DashboardController@index`
	- Memanggil dua method privat untuk ambil data awal:
		- `stock_top_volume_for_buy()` → memanggil TradingView Scanner market "indonesia" (POST scan) dan memfilter baris dengan `AnalystRating` ∈ {Buy, StrongBuy}. Data diolah: logo, name, description, close, currency, change, value, analystRating.
		- `stock_technical_analysis()` → memanggil TradingView Scanner dengan kolom TechRating/MARating/OsRating. Disaring pada kombinasi sinyal kuat (StrongBuy/Buy) dan diubah ke label yang ramah (Strong Buy/Buy).
- API: `GET /api/stock-data` → `ApiController@getStockData`
	- Mengembalikan JSON: `stock_top_volume_for_buy`, `stock_technical_analysis`, `last_updated`.
- View: `resources/views/dashboard.blade.php`
	- Dua tabel DataTables. Auto-refresh via AJAX ke `/api/stock-data` setiap 15 menit. Saat refresh: tabel diberi efek updating/updated, dan countdown di-reset. Countdown tampil di samping tombol collapse setiap card.
	- Proteksi AJAX dengan CSRF meta token.
	- Watermark diagonal: "Data Powered by TradingView" dan "Created by Mahdian (yan043)".

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

## Kustomisasi Singkat

- Interval refresh: ubah di `dashboard.blade.php` (nilai `timeRemaining = 15 * 60;`).
- Countdown/tampilan: CSS ada di `<style>` pada `dashboard.blade.php`.
- Filter logika sinyal: sesuaikan di `DashboardController` dan/atau `ApiController` pada kondisi filtering array `$item['d'][idx]`.
- Route/API: `routes/web.php`.

## Keamanan & Batasan

- AJAX menggunakan CSRF token dari meta tag.
- Data bersumber dari layanan pihak ketiga (TradingView); struktur kolom/endpoint dapat berubah sewaktu-waktu.

## Kredit

- Data Powered by TradingView
- Created by Mahdian (yan043)
