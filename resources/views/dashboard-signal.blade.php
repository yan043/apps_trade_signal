<!DOCTYPE html>
<html lang="id">

	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="csrf-token" content="{{ csrf_token() }}" />
		<title>IDX Trading Signals - Professional Dashboard</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
		<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
		<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
		<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
		<style>
			:root {
				--bg-main: #f5f7fa;
				--bg-panel: #ffffff;
				--bg-panel-soft: #f1f3f5;
				--border-soft: #e3e8ef;
				--text-main: #1f2937;
				--text-muted: #6b7280;
				--green: #16a34a;
				--green-strong: #15803d;
				--green-soft: rgba(34, 197, 94, 0.12);
				--red: #e0405e;
				--red-strong: #c0334e;
				--red-soft: rgba(224, 64, 94, 0.10);
				--amber: #c98a00;
				--amber-soft: rgba(217, 151, 10, 0.13);
				--blue: #2563eb;
				--card-shadow: 0 2px 12px rgba(30, 41, 59, 0.06);
			}

			body {
				background: var(--bg-main);
				color: var(--text-main);
				font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
				min-height: 100vh;
			}

			.page-header {
				border-bottom: 1px solid var(--border-soft);
				padding: 18px 0 14px;
				margin-bottom: 20px;
			}

			.page-title {
				font-size: 1.4rem;
				font-weight: 700;
				color: var(--blue);
				margin: 0;
			}

			.page-subtitle {
				color: var(--text-muted);
				font-size: 0.85rem;
			}

			.clock-box {
				font-family: 'Courier New', monospace;
				font-size: 0.85rem;
				color: var(--text-muted);
				text-align: right;
			}

			.badge-session {
				font-size: 0.75rem;
				padding: 5px 12px;
				border-radius: 12px;
				font-weight: 600;
			}

			.badge-session.open {
				background: var(--green-soft);
				color: var(--green-strong);
				border: 1px solid rgba(34, 197, 94, 0.35);
			}

			.badge-session.break {
				background: var(--amber-soft);
				color: var(--amber);
				border: 1px solid rgba(217, 151, 10, 0.35);
			}

			.badge-session.closed {
				background: var(--red-soft);
				color: var(--red-strong);
				border: 1px solid rgba(224, 64, 94, 0.3);
			}

			.stat-card {
				background: var(--bg-panel);
				border: 1px solid var(--border-soft);
				border-radius: 12px;
				padding: 14px 18px;
				height: 100%;
				box-shadow: var(--card-shadow);
			}

			.stat-card .stat-label {
				color: var(--text-muted);
				font-size: 0.75rem;
				text-transform: uppercase;
				letter-spacing: 0.05em;
			}

			.stat-card .stat-value {
				font-size: 1.6rem;
				font-weight: 700;
			}

			.stat-card .stat-value.green { color: var(--green); }
			.stat-card .stat-value.blue { color: var(--blue); }
			.stat-card .stat-value.muted { color: var(--text-muted); }

			.signal-panel {
				background: var(--bg-panel);
				border: 1px solid var(--border-soft);
				border-radius: 12px;
				margin-bottom: 24px;
				overflow: hidden;
				box-shadow: var(--card-shadow);
			}

			.signal-panel-header {
				padding: 14px 20px;
				border-bottom: 1px solid var(--border-soft);
				display: flex;
				justify-content: space-between;
				align-items: center;
				flex-wrap: wrap;
				gap: 10px;
			}

			.signal-panel-title {
				font-weight: 700;
				font-size: 1.05rem;
			}

			.signal-panel-title small {
				color: var(--text-muted);
				font-weight: 400;
				margin-left: 8px;
			}

			.filter-group .btn {
				font-size: 0.72rem;
				padding: 3px 10px;
				border-radius: 8px;
				background: var(--bg-panel-soft);
				border: 1px solid var(--border-soft);
				color: var(--text-muted);
			}

			.filter-group .btn.active {
				background: var(--blue);
				border-color: var(--blue);
				color: #fff;
			}

			.signal-panel-body {
				padding: 12px 16px 16px;
			}

			table.dataTable {
				color: var(--text-main) !important;
				font-size: 0.83rem;
			}

			table.dataTable thead th {
				color: var(--text-muted) !important;
				font-size: 0.72rem;
				text-transform: uppercase;
				letter-spacing: 0.04em;
				border-bottom: 1px solid var(--border-soft) !important;
				white-space: nowrap;
			}

			table.dataTable tbody td {
				border-bottom: 1px solid var(--border-soft) !important;
				vertical-align: middle;
				white-space: nowrap;
			}

			table.dataTable tbody tr {
				background: transparent !important;
			}

			table.dataTable tbody tr:hover {
				background: var(--bg-panel-soft) !important;
			}

			.dataTables_wrapper .dataTables_length,
			.dataTables_wrapper .dataTables_filter,
			.dataTables_wrapper .dataTables_info,
			.dataTables_wrapper .dataTables_paginate {
				color: var(--text-muted) !important;
				font-size: 0.8rem;
			}

			.dataTables_wrapper .dataTables_filter input,
			.dataTables_wrapper .dataTables_length select {
				background: var(--bg-panel-soft);
				border: 1px solid var(--border-soft);
				color: var(--text-main);
				border-radius: 6px;
			}

			.dataTables_wrapper .dataTables_paginate .paginate_button {
				color: var(--text-muted) !important;
			}

			.dataTables_wrapper .dataTables_paginate .paginate_button.current {
				background: var(--blue) !important;
				border-color: var(--blue) !important;
				color: #fff !important;
			}

			.symbol-cell {
				display: flex;
				align-items: center;
				gap: 8px;
			}

			.symbol-cell .logo {
				width: 22px;
				height: 22px;
				border-radius: 50%;
				background: #fff;
				object-fit: contain;
			}

			.symbol-cell .symbol-code {
				font-weight: 700;
			}

			.symbol-cell .symbol-name {
				color: var(--text-muted);
				font-size: 0.7rem;
				max-width: 140px;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.signal-badge {
				font-size: 0.68rem;
				font-weight: 700;
				padding: 3px 9px;
				border-radius: 9px;
				white-space: nowrap;
			}

			.signal-badge.strong-buy { background: var(--green); color: #fff; }
			.signal-badge.buy { background: var(--green-soft); color: var(--green-strong); border: 1px solid rgba(34,197,94,.35); }
			.signal-badge.neutral { background: var(--bg-panel-soft); color: var(--text-muted); border: 1px solid var(--border-soft); }
			.signal-badge.sell { background: var(--amber-soft); color: var(--amber); border: 1px solid rgba(217,151,10,.35); }
			.signal-badge.strong-sell { background: var(--red); color: #fff; }

			.score-cell {
				display: flex;
				align-items: center;
				gap: 8px;
				min-width: 92px;
			}

			.score-bar {
				flex: 1;
				height: 5px;
				background: var(--bg-panel-soft);
				border-radius: 3px;
				overflow: hidden;
			}

			.score-bar-fill {
				height: 100%;
				border-radius: 3px;
			}

			.value-up { color: var(--green); font-weight: 600; }
			.value-down { color: var(--red); font-weight: 600; }
			.value-flat { color: var(--text-muted); }

			.detail-toggle {
				cursor: pointer;
				color: var(--text-muted);
				user-select: none;
			}

			.detail-row-content {
				background: var(--bg-panel-soft);
				border-radius: 8px;
				padding: 14px 16px;
				margin: 4px 0;
				font-size: 0.8rem;
				white-space: normal;
			}

			.detail-row-content h6 {
				font-size: 0.72rem;
				text-transform: uppercase;
				letter-spacing: 0.05em;
				color: var(--text-muted);
				margin-bottom: 8px;
			}

			.reason-list {
				list-style: none;
				padding: 0;
				margin: 0;
			}

			.reason-list li {
				padding: 2px 0;
				display: flex;
				justify-content: space-between;
				gap: 12px;
			}

			.reason-points {
				font-weight: 700;
				white-space: nowrap;
			}

			.metric-chip {
				display: inline-block;
				background: var(--bg-panel);
				border: 1px solid var(--border-soft);
				border-radius: 8px;
				padding: 4px 10px;
				margin: 2px 4px 2px 0;
				font-size: 0.75rem;
				color: var(--text-muted);
			}

			.metric-chip b {
				color: var(--text-main);
			}

			.legend-box {
				background: var(--bg-panel);
				border: 1px solid var(--border-soft);
				border-radius: 10px;
				padding: 12px 18px;
				font-size: 0.78rem;
				color: var(--text-muted);
				margin-bottom: 30px;
			}

			.stale-notice {
				font-size: 0.75rem;
				color: var(--amber);
			}

			.empty-state {
				text-align: center;
				color: var(--text-muted);
				padding: 30px 10px;
			}
		</style>
	</head>

	<body>
		<div class="container-fluid px-4">
			<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
				<div>
					<h1 class="page-title">IDX Trading Signals</h1>
					<div class="page-subtitle">Analisa multi-timeframe saham Bursa Efek Indonesia &middot; Data: TradingView</div>
				</div>
				<div class="d-flex align-items-center gap-3 flex-wrap">
					<span id="session-badge" class="badge-session closed">Memuat...</span>
					<div class="clock-box">
						<div id="clock">--:--:--</div>
						<div>Refresh dalam <span id="refresh-countdown">30</span>s</div>
					</div>
				</div>
			</div>

			<div class="row g-3 mb-4">
				<div class="col-6 col-lg-3">
					<div class="stat-card">
						<div class="stat-label">Scalping &middot; Strong Buy</div>
						<div class="stat-value green" id="stat-scalp-strong">-</div>
					</div>
				</div>
				<div class="col-6 col-lg-3">
					<div class="stat-card">
						<div class="stat-label">Scalping &middot; Buy</div>
						<div class="stat-value blue" id="stat-scalp-buy">-</div>
					</div>
				</div>
				<div class="col-6 col-lg-3">
					<div class="stat-card">
						<div class="stat-label">Swing &middot; Strong Buy</div>
						<div class="stat-value green" id="stat-swing-strong">-</div>
					</div>
				</div>
				<div class="col-6 col-lg-3">
					<div class="stat-card">
						<div class="stat-label">Swing &middot; Buy</div>
						<div class="stat-value blue" id="stat-swing-buy">-</div>
					</div>
				</div>
			</div>

			<div class="signal-panel">
				<div class="signal-panel-header">
					<div class="signal-panel-title">
						&#128200; Performa Sinyal <small>win-rate &amp; expectancy dari hasil nyata yang sudah tertutup</small>
					</div>
				</div>
				<div class="signal-panel-body">
					<div id="performance-content" class="row g-3">
						<div class="col-12 empty-state">Memuat performa...</div>
					</div>
				</div>
			</div>

			<div class="signal-panel">
				<div class="signal-panel-header">
					<div class="signal-panel-title">
						&#9889; Scalping <small>M5-M15 &middot; intraday, tutup posisi sebelum 16:15 WIB</small>
						<span id="scalping-stale" class="stale-notice ms-2" style="display:none;">&#9888; Market tutup - data indikatif</span>
					</div>
					<div class="filter-group btn-group" data-table="scalping">
						<button type="button" class="btn active" data-filter="">Semua</button>
						<button type="button" class="btn" data-filter="STRONG BUY">Strong Buy</button>
						<button type="button" class="btn" data-filter="BUY">Buy</button>
						<button type="button" class="btn" data-filter="SELL">Sell</button>
					</div>
				</div>
				<div class="signal-panel-body">
					<div class="table-responsive">
						<table id="scalping-table" class="table w-100">
							<thead>
								<tr>
									<th>Saham</th>
									<th>Sinyal</th>
									<th>Skor</th>
									<th>Harga</th>
									<th>Chg%</th>
									<th>Entry</th>
									<th>TP1</th>
									<th>SL</th>
									<th>R:R</th>
									<th></th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="signal-panel">
				<div class="signal-panel-header">
					<div class="signal-panel-title">
						&#128202; Swing <small>D1 &middot; hold beberapa hari sampai minggu</small>
					</div>
					<div class="filter-group btn-group" data-table="swing">
						<button type="button" class="btn active" data-filter="">Semua</button>
						<button type="button" class="btn" data-filter="STRONG BUY">Strong Buy</button>
						<button type="button" class="btn" data-filter="BUY">Buy</button>
						<button type="button" class="btn" data-filter="SELL">Sell</button>
					</div>
				</div>
				<div class="signal-panel-body">
					<div class="table-responsive">
						<table id="swing-table" class="table w-100">
							<thead>
								<tr>
									<th>Saham</th>
									<th>Sinyal</th>
									<th>Skor</th>
									<th>Harga</th>
									<th>Chg%</th>
									<th>Entry</th>
									<th>TP1</th>
									<th>SL</th>
									<th>R:R</th>
									<th></th>
								</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="legend-box">
				<b>Cara membaca:</b>
				Skor 0-100 = 50 + (skor bull - skor bear) / 2. Label:
				<span class="signal-badge strong-buy">STRONG BUY &ge; 75</span>
				<span class="signal-badge buy">BUY &ge; 60</span>
				<span class="signal-badge neutral">NEUTRAL 41-59</span>
				<span class="signal-badge sell">SELL &le; 40</span>
				<span class="signal-badge strong-sell">STRONG SELL &le; 25</span>.
				Hanya saham yang lolos filter likuiditas (nilai transaksi), kapitalisasi pasar, jarak aman ARA/ARB, dan volatilitas sehat yang ditampilkan.
				Semua level Entry/TP/SL sudah dibulatkan ke fraksi harga IDX dan TP dibatasi di bawah ARA.
				SELL = sinyal keluar/hindari (short ritel tidak tersedia di IDX). Klik baris untuk rincian skor.
				<br><b>Disclaimer:</b> bukan rekomendasi jual-beli; selalu lakukan analisa mandiri.
			</div>
		</div>

		<script>
			var REFRESH_SECONDS = 30;
			var refreshCountdown = REFRESH_SECONDS;
			var tables = {};

			function esc(value)
			{
				return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (ch)
				{
					return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
				});
			}

			function formatNumber(num)
			{
				if (num === null || num === undefined) return '-';
				return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num);
			}

			function formatBillions(num)
			{
				if (num === null || num === undefined) return '-';
				return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(num / 1000000000) + ' M';
			}

			function signalBadgeClass(signal)
			{
				if (signal === 'STRONG BUY') return 'strong-buy';
				if (signal === 'BUY') return 'buy';
				if (signal === 'SELL') return 'sell';
				if (signal === 'STRONG SELL') return 'strong-sell';
				return 'neutral';
			}

			function scoreColor(score)
			{
				if (score >= 60) return '#16a34a';
				if (score <= 40) return '#e0405e';
				return '#6b7280';
			}

			function changeClass(change)
			{
				if (change > 0) return 'value-up';
				if (change < 0) return 'value-down';
				return 'value-flat';
			}

			function sortValue(value)
			{
				return value === null || value === undefined ? -1 : value;
			}

			function renderSymbol(data, type, signal)
			{
				if (type !== 'display')
				{
					return signal.symbol + ' ' + signal.description;
				}

				return '<div class="symbol-cell">'
					+ '<img src="' + esc(signal.logo) + '" alt="" class="logo" onerror="this.style.display=\'none\'" />'
					+ '<div><div class="symbol-code">' + esc(signal.symbol) + '</div>'
					+ '<div class="symbol-name">' + esc(signal.description) + '</div></div>'
					+ '</div>';
			}

			function renderSignal(data, type)
			{
				if (type !== 'display')
				{
					return data;
				}

				return '<span class="signal-badge ' + signalBadgeClass(data) + '">' + esc(data) + '</span>';
			}

			function renderScore(data, type)
			{
				if (type !== 'display')
				{
					return data;
				}

				return '<div class="score-cell">'
					+ '<span style="color:' + scoreColor(data) + '; font-weight:700;">' + esc(data) + '</span>'
					+ '<div class="score-bar"><div class="score-bar-fill" style="width:' + Math.min(100, Math.max(0, data)) + '%; background:' + scoreColor(data) + ';"></div></div>'
					+ '</div>';
			}

			function renderPrice(data, type)
			{
				return type !== 'display' ? data : formatNumber(data);
			}

			function renderChange(data, type)
			{
				if (type !== 'display')
				{
					return data;
				}

				var text = data > 0 ? '+' + esc(data) + '%' : esc(data) + '%';
				return '<span class="' + changeClass(data) + '">' + text + '</span>';
			}

			function renderEntry(data, type, signal)
			{
				if (type !== 'display')
				{
					return sortValue(data);
				}

				return signal.entry1 !== null ? formatNumber(signal.entry1) + ' - ' + formatNumber(signal.entry2) : '-';
			}

			function renderTp1(data, type, signal)
			{
				if (type !== 'display')
				{
					return sortValue(data);
				}

				return signal.takeProfit1 !== null
					? formatNumber(signal.takeProfit1) + ' <span class="value-up">(+' + esc(signal.takeProfit1_percent) + '%)</span>'
					: '-';
			}

			function renderSl(data, type, signal)
			{
				if (type !== 'display')
				{
					return sortValue(data);
				}

				return signal.stopLoss !== null
					? formatNumber(signal.stopLoss) + ' <span class="value-down">(' + esc(signal.stopLoss_percent) + '%)</span>'
					: '-';
			}

			function renderRiskReward(data, type)
			{
				if (type !== 'display')
				{
					return sortValue(data);
				}

				return data !== null ? esc(data) : '-';
			}

			function renderToggle()
			{
				return '<span class="detail-toggle">&#9660;</span>';
			}

			function buildReasonList(reasons, colorClass)
			{
				if (!reasons || reasons.length === 0)
				{
					return '<li class="value-flat">Tidak ada komponen aktif</li>';
				}

				return reasons.map(function (reason)
				{
					return '<li><span>' + esc(reason.label) + '</span><span class="reason-points ' + colorClass + '">+' + esc(reason.points) + '</span></li>';
				}).join('');
			}

			function buildDetail(signal)
			{
				var chips = '<span class="metric-chip">RSI <b>' + esc(signal.rsi ?? '-') + '</b></span>'
					+ '<span class="metric-chip">ADX <b>' + esc(signal.adx ?? '-') + '</b></span>'
					+ '<span class="metric-chip">Vol Relatif <b>' + esc(signal.volumeRatio ?? '-') + 'x</b></span>'
					+ '<span class="metric-chip">Nilai Transaksi <b>' + esc(formatBillions(signal.valueTraded)) + '</b></span>'
					+ '<span class="metric-chip">Ruang ke ARA <b>' + esc(signal.roomToAra) + '%</b></span>'
					+ '<span class="metric-chip">Timeframe <b>' + esc(signal.timeframe) + '</b></span>';

				if (signal.takeProfit2 !== null)
				{
					chips += '<span class="metric-chip">TP2 <b>' + formatNumber(signal.takeProfit2) + ' (+' + esc(signal.takeProfit2_percent) + '%)</b></span>'
						+ '<span class="metric-chip">TP3 <b>' + formatNumber(signal.takeProfit3) + ' (+' + esc(signal.takeProfit3_percent) + '%)</b></span>';
				}

				return '<div class="detail-row-content">'
					+ '<div class="mb-2">' + chips + '</div>'
					+ '<div class="row g-3">'
					+ '<div class="col-md-6"><h6>Skor Bull: ' + esc(signal.bull_score) + '</h6><ul class="reason-list">' + buildReasonList(signal.bull_reasons, 'value-up') + '</ul></div>'
					+ '<div class="col-md-6"><h6>Skor Bear: ' + esc(signal.bear_score) + '</h6><ul class="reason-list">' + buildReasonList(signal.bear_reasons, 'value-down') + '</ul></div>'
					+ '</div></div>';
			}

			function renderTable(key, signals)
			{
				var table = tables[key];
				table.clear();
				table.rows.add(signals);
				table.draw(false);
			}

			function updateStats(data)
			{
				function countBy(list, label)
				{
					return list.filter(function (s) { return s.signal === label; }).length;
				}

				$('#stat-scalp-strong').text(countBy(data.scalping_signals, 'STRONG BUY'));
				$('#stat-scalp-buy').text(countBy(data.scalping_signals, 'BUY'));
				$('#stat-swing-strong').text(countBy(data.swing_signals, 'STRONG BUY'));
				$('#stat-swing-buy').text(countBy(data.swing_signals, 'BUY'));
			}

			var isLoading = false;

			function loadSignals()
			{
				if (isLoading)
				{
					return;
				}

				isLoading = true;

				$.ajax({
					url: '/api/trading-signals',
					method: 'GET',
					dataType: 'json',
					success: function (data)
					{
						renderTable('scalping', data.scalping_signals || []);
						renderTable('swing', data.swing_signals || []);
						updateStats(data);

						$('#scalping-stale').toggle(!data.scalping_session_open);
					},
					error: function ()
					{
						console.error('Gagal memuat sinyal');
					},
					complete: function ()
					{
						isLoading = false;
						refreshCountdown = REFRESH_SECONDS;
					}
				});
			}

			function performanceCard(title, stats)
			{
				if (!stats || stats.total === 0)
				{
					return '<div class="col-md-6"><div class="stat-card">'
						+ '<div class="stat-label">' + esc(title) + '</div>'
						+ '<div class="empty-state" style="padding:14px 0">Belum ada sinyal tertutup &mdash; perlu akumulasi data.</div>'
						+ '</div></div>';
				}

				var winColor = stats.win_rate >= 50 ? 'green' : 'muted';
				var expColor = stats.expectancy > 0 ? 'green' : 'muted';

				return '<div class="col-md-6"><div class="stat-card">'
					+ '<div class="stat-label">' + esc(title) + ' &middot; ' + esc(stats.total) + ' sinyal selesai</div>'
					+ '<div class="d-flex gap-4 my-2">'
					+ '<div><div class="stat-label">Win-rate</div><div class="stat-value ' + winColor + '">' + esc(stats.win_rate) + '%</div></div>'
					+ '<div><div class="stat-label">Expectancy</div><div class="stat-value ' + expColor + '">' + (stats.expectancy > 0 ? '+' : '') + esc(stats.expectancy) + 'R</div></div>'
					+ '</div>'
					+ '<div>'
					+ '<span class="metric-chip">Menang <b class="value-up">' + esc(stats.wins) + '</b></span>'
					+ '<span class="metric-chip">Kalah <b class="value-down">' + esc(stats.losses) + '</b></span>'
					+ '<span class="metric-chip">TP1+ <b>' + esc(stats.tp1_rate) + '%</b></span>'
					+ '<span class="metric-chip">TP2+ <b>' + esc(stats.tp2_rate) + '%</b></span>'
					+ '<span class="metric-chip">TP3 <b>' + esc(stats.tp3_rate) + '%</b></span>'
					+ '<span class="metric-chip">SL <b>' + esc(stats.sl_rate) + '%</b></span>'
					+ '</div></div></div>';
			}

			function renderPerformance(data)
			{
				var container = $('#performance-content');

				if (!data || !data.scalping || !data.swing)
				{
					container.html('<div class="col-12 empty-state">Belum ada data performa.</div>');
					return;
				}

				var html = performanceCard('Scalping (M5-M15)', data.scalping)
					+ performanceCard('Swing (D1)', data.swing)
					+ '<div class="col-12"><small style="color:var(--text-muted)">Posisi masih terbuka: ' + esc(data.open_positions)
					+ ' &middot; diperbarui ' + esc(data.updated_at) + ' WIB. Perhitungan konservatif: bila SL dan TP tersentuh di hari yang sama, dihitung kena SL.</small></div>';

				container.html(html);
			}

			function loadPerformance()
			{
				$.ajax({
					url: '/api/signal-performance',
					method: 'GET',
					dataType: 'json',
					success: renderPerformance,
					error: function ()
					{
						$('#performance-content').html('<div class="col-12 empty-state">Performa belum tersedia (perlu koneksi database aktif).</div>');
					}
				});
			}

			function tickClock()
			{
				var jakarta = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
				var pad = function (n) { return n < 10 ? '0' + n : n; };

				$('#clock').text(
					jakarta.getDate() + '/' + pad(jakarta.getMonth() + 1) + '/' + jakarta.getFullYear()
					+ ' ' + pad(jakarta.getHours()) + ':' + pad(jakarta.getMinutes()) + ':' + pad(jakarta.getSeconds()) + ' WIB'
				);

				updateSessionBadge(jakarta);
			}

			function updateSessionBadge(jakarta)
			{
				var badge = $('#session-badge');
				var day = jakarta.getDay();
				var minutes = jakarta.getHours() * 60 + jakarta.getMinutes();

				if (day === 0 || day === 6)
				{
					badge.attr('class', 'badge-session closed').text('Weekend - Market Tutup');
					return;
				}

				var friday = (day === 5);
				var session1End = friday ? 690 : 720;
				var session2Start = friday ? 840 : 810;

				if (minutes >= 540 && minutes < session1End)
				{
					badge.attr('class', 'badge-session open').text('Sesi I - Market Buka');
				}
				else if (minutes >= session1End && minutes < session2Start)
				{
					badge.attr('class', 'badge-session break').text('Istirahat');
				}
				else if (minutes >= session2Start && minutes < 990)
				{
					badge.attr('class', 'badge-session open').text('Sesi II - Market Buka');
				}
				else
				{
					badge.attr('class', 'badge-session closed').text('Market Tutup');
				}
			}

			function tickRefresh()
			{
				refreshCountdown--;

				if (refreshCountdown <= 0)
				{
					refreshCountdown = REFRESH_SECONDS;
					loadSignals();
				}

				$('#refresh-countdown').text(refreshCountdown);
			}

			function initTable(selector)
			{
				return $(selector).DataTable({
					data: [],
					pageLength: 25,
					lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
					order: [[2, 'desc']],
					columns: [
						{ data: null, orderable: false, render: renderSymbol },
						{ data: 'signal', render: renderSignal },
						{ data: 'score', searchable: false, render: renderScore },
						{ data: 'price', searchable: false, render: renderPrice },
						{ data: 'change', searchable: false, render: renderChange },
						{ data: 'entry1', orderable: false, searchable: false, render: renderEntry },
						{ data: 'takeProfit1', searchable: false, render: renderTp1 },
						{ data: 'stopLoss', searchable: false, render: renderSl },
						{ data: 'riskReward', searchable: false, render: renderRiskReward },
						{ data: null, orderable: false, searchable: false, render: renderToggle }
					],
					language: {
						search: 'Cari:',
						lengthMenu: 'Tampilkan _MENU_',
						info: 'Menampilkan _START_-_END_ dari _TOTAL_ saham',
						infoEmpty: 'Tidak ada sinyal',
						zeroRecords: 'Tidak ada saham yang cocok',
						paginate: { first: '&laquo;', last: '&raquo;', next: '&rsaquo;', previous: '&lsaquo;' }
					}
				});
			}

			$(document).ready(function ()
			{
				tables.scalping = initTable('#scalping-table');
				tables.swing = initTable('#swing-table');

				$('.filter-group .btn').on('click', function ()
				{
					var group = $(this).closest('.filter-group');
					group.find('.btn').removeClass('active');
					$(this).addClass('active');

					var key = group.data('table');
					var filter = $(this).data('filter');
					var exact = filter === '' ? '' : '^' + filter + '$';

					tables[key].column(1).search(exact, true, false).draw();
				});

				$('#scalping-table tbody, #swing-table tbody').on('click', 'tr', function ()
				{
					var tableKey = $(this).closest('table').attr('id') === 'scalping-table' ? 'scalping' : 'swing';
					var table = tables[tableKey];
					var row = table.row(this);

					if (row.length === 0 || $(this).hasClass('detail-child')) return;

					var signal = row.data();

					if (!signal) return;

					if (row.child.isShown())
					{
						row.child.hide();
						$(this).find('.detail-toggle').html('&#9660;');
					}
					else
					{
						row.child(buildDetail(signal), 'detail-child').show();
						$(this).find('.detail-toggle').html('&#9650;');
					}
				});

				loadSignals();
				loadPerformance();
				tickClock();
				setInterval(tickClock, 1000);
				setInterval(tickRefresh, 1000);
				setInterval(loadPerformance, 60000);
			});
		</script>
	</body>

</html>
