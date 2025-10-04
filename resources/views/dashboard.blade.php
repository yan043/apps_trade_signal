<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Signal Auto Trading</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<style>
		body {
			background-color: #121212;
			color: #fff;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			min-height: 100vh;
		}

		h1 {
			font-weight: 700;
			color: #ffc107;
			text-shadow: 1px 1px 4px #000;
		}

		.last-updated {
			font-size: 0.9em;
			color: #fff;
			background: rgba(255, 193, 7, 0.2);
			display: inline-block;
			padding: 5px 15px;
			border-radius: 30px;
		}

		.card {
			background-color: #1e1e1e;
			border: none;
			border-radius: 15px;
			box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.6);
			margin-bottom: 25px;
			overflow: hidden;
		}

		.card-header {
			font-weight: bold;
			letter-spacing: 0.5px;
			color: #fff;
			background-color: #000;
		}

		.table {
			border-radius: 10px;
			overflow: hidden;
		}

		.table thead th {
			text-transform: uppercase;
			font-size: 0.85rem;
			background-color: #000;
			color: #ffc107;
		}

		.table th,
		.table td {
			text-align: center !important;
			vertical-align: middle !important;
			padding: 4px !important;
			font-size: 13px !important;
			color: #fff;
			background-color: #1e1e1e;
		}

		.table-striped tbody tr:nth-of-type(odd) {
			background-color: #2a2a2a !important;
		}

		.table-hover tbody tr:hover {
			background-color: #ffc10733 !important;
			cursor: pointer;
		}

		.badge {
			font-size: 0.85rem;
			padding: 5px 10px;
			border-radius: 12px;
		}

		.badge-success {
			background-color: #ffc107 !important;
			color: #000 !important;
		}

		.badge-danger {
			background-color: #ff5722 !important;
			color: #fff !important;
		}

		.loading-text {
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.loading-text span {
			width: 8px;
			height: 8px;
			margin: 0 2px;
			background: #ffc107;
			border-radius: 50%;
			display: inline-block;
			animation: bounce 1.4s infinite;
		}

		.loading-text span:nth-child(2) {
			animation-delay: 0.2s;
		}

		.loading-text span:nth-child(3) {
			animation-delay: 0.4s;
		}

		@keyframes bounce {

			0%,
			80%,
			100% {
				transform: scale(0);
			}

			40% {
				transform: scale(1);
			}
		}
	</style>

</head>

<body>
	<div class="container-fluid mt-4">
		<h1 class="text-center mb-3">🚀 Signal Auto Trading</h1>
		<div class="text-center mb-4">
			<div class="last-updated" id="last-updated">
				<span id="clock"></span>
			</div>
			@php
				$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
				$isMob = is_numeric(strpos($ua, 'mobile'));
			@endphp
		</div>

		<!-- Scalping Crypto Signals -->
		<div class="card">
			<div class="card-header">
				<h5 class="mb-0">📊 Scalping Crypto Signals</h5>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover text-center" id="scalping-table">
						<thead>
							<tr>
								<th>Symbol</th>
								<th>Price</th>
								<th>EMA9</th>
								<th>EMA21</th>
								<th>RSI</th>
								<th>Action</th>
								<th>TP</th>
								<th>SL</th>
							</tr>
						</thead>
						<tbody id="scalping-table-body">
							<tr>
								<td colspan="8" class="text-center loading-text"><span></span><span></span><span></span></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Crypto Signals -->
		<div class="card">
			<div class="card-header">
				<h5 class="mb-0">💹 Crypto Signals</h5>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover text-center" id="crypto-table">
						<thead>
							<tr>
								<th>Symbol</th>
								<th>Entry Price</th>
								<th>Target</th>
								<th>Stop Loss</th>
								<th>Trend</th>
							</tr>
						</thead>
						<tbody id="crypto-table-body">
							@foreach ($cryptoSignals as $signal)
								<tr>
									<td>{{ $signal->asset->symbol }}</td>
									<td>{{ number_format($signal->entry_price, 2, ',', '.') }}</td>
									<td>
										{{ number_format($signal->target_price, 2, ',', '.') }}
										({{ number_format($signal->expected_gain, 2, ',', '.') }}%)
										<br>
										{{ number_format($signal->target_price_2, 2, ',', '.') }}
										({{ number_format($signal->expected_gain_2, 2, ',', '.') }}%)<br>
										{{ number_format($signal->target_price_3, 2, ',', '.') }}
										({{ number_format($signal->expected_gain_3, 2, ',', '.') }}%)
									</td>
									<td>{{ number_format($signal->stop_loss, 2, ',', '.') }}</td>
									<td>
										@if ($signal->entry_price < $signal->target_price)
											<span class="badge badge-success">🔺 Bullish</span>
										@else
											<span class="badge badge-danger">🔻 Bearish</span>
										@endif
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Stock Signals -->
		<div class="card">
			<div class="card-header">
				<h5 class="mb-0">📈 Stock Signals</h5>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover text-center" id="stock-table">
						<thead>
							<tr>
								<th>Symbol</th>
								<th>Entry Price</th>
								<th>Target</th>
								<th>Stop Loss</th>
								<th>Trend</th>
							</tr>
						</thead>
						<tbody id="stock-table-body">
							@foreach ($stockSignals as $signal)
								<tr>
									<td>{{ $signal->asset->symbol }}</td>
									<td>{{ number_format($signal->entry_price, 2, ',', '.') }}</td>
									<td>
										{{ number_format($signal->target_price, 2, ',', '.') }}
										({{ number_format($signal->expected_gain, 2, ',', '.') }}%)
										<br>
										{{ number_format($signal->target_price_2, 2, ',', '.') }}
										({{ number_format($signal->expected_gain_2, 2, ',', '.') }}%)<br>
										{{ number_format($signal->target_price_3, 2, ',', '.') }}
										({{ number_format($signal->expected_gain_3, 2, ',', '.') }}%)
									</td>
									<td>{{ number_format($signal->stop_loss, 2, ',', '.') }}</td>
									<td>
										@if ($signal->entry_price < $signal->target_price)
											<span class="badge badge-success">🔺 Bullish</span>
										@else
											<span class="badge badge-danger">🔻 Bearish</span>
										@endif
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<script>
		var isMob = {!! json_encode($isMob) !!};

		if (isMob == false) {
			function showTime() {
				var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
					'Oktober', 'November', 'Desember'
				];
				var myDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
				var date = new Date();
				var day = date.getDate();
				var month = date.getMonth();
				var thisDay = date.getDay();
				thisDay = myDays[thisDay];
				var yy = date.getFullYear();
				var year = yy;

				var today = new Date();
				var curr_hour = today.getHours();
				var curr_minute = today.getMinutes();
				var curr_second = today.getSeconds();

				curr_hour = checkTime(curr_hour);
				curr_minute = checkTime(curr_minute);
				curr_second = checkTime(curr_second);

				document.getElementById('clock').innerHTML = thisDay + ', ' + day + ' ' + months[month] + ' ' + year + ' | ' +
					curr_hour + ":" + curr_minute + ":" + curr_second;
			}

			function checkTime(i) {
				if (i < 10) i = "0" + i;
				return i;
			}
			setInterval(showTime, 500);
		}

		function updateScalpingTable(data) {
			let html = '';
			data.scalpingSignals.forEach(signal => {
				let actionBadge = signal.action === 'BUY' ? '<span class="badge badge-success">BUY</span>' : (signal
					.action === 'SELL' ? '<span class="badge badge-danger">SELL</span>' :
					'<span class="badge badge-secondary">HOLD</span>');
				const fmt = num => num.toLocaleString('id-ID', {
					minimumFractionDigits: 2,
					maximumFractionDigits: 2
				});
				html += `<tr>
                    <td>${signal.symbol}</td>
                    <td>${fmt(signal.price)}</td>
                    <td>${signal.ema9?fmt(signal.ema9):'N/A'}</td>
                    <td>${signal.ema21?fmt(signal.ema21):'N/A'}</td>
                    <td>${signal.rsi?fmt(signal.rsi):'N/A'}</td>
                    <td>${actionBadge}</td>
                    <td>${signal.tp1?fmt(signal.tp1)+' ('+fmt(signal.tp1_percentage)+'%)':'-'}<br>
                        ${signal.tp2?fmt(signal.tp2)+' ('+fmt(signal.tp2_percentage)+'%)':'-'}<br>
                        ${signal.tp3?fmt(signal.tp3)+' ('+fmt(signal.tp3_percentage)+'%)':'-'}
                    </td>
                    <td>${signal.sl?fmt(signal.sl)+' ('+fmt(signal.sl_percentage)+'%)':'-'}</td>
                </tr>`;
			});
			document.querySelector('#scalping-table-body').innerHTML = html;
		}

		function updateSignalsTables(data) {
			const fmt = num => parseFloat(num).toLocaleString('id-ID', {
				minimumFractionDigits: 2,
				maximumFractionDigits: 2
			});
			let cryptoHtml = '';
			data.cryptoSignals.forEach(signal => {
				cryptoHtml += `<tr>
                    <td>${signal.asset.symbol}</td>
                    <td>${fmt(signal.entry_price)}</td>
                    <td>${fmt(signal.target_price)} (${fmt(signal.expected_gain)}%)<br>${fmt(signal.target_price_2)} (${fmt(signal.expected_gain_2)}%)<br>${fmt(signal.target_price_3)} (${fmt(signal.expected_gain_3)}%)</td>
                    <td>${fmt(signal.stop_loss)}</td>
                    <td>${signal.entry_price<signal.target_price?'<span class="badge badge-success">🔺 Bullish</span>':'<span class="badge badge-danger">🔻 Bearish</span>'}</td>
                </tr>`;
			});
			document.querySelector('#crypto-table-body').innerHTML = cryptoHtml;

			let stockHtml = '';
			data.stockSignals.forEach(signal => {
				stockHtml += `<tr>
                    <td>${signal.asset.symbol}</td>
                    <td>${fmt(signal.entry_price)}</td>
                    <td>${fmt(signal.target_price)} (${fmt(signal.expected_gain)}%)<br>${fmt(signal.target_price_2)} (${fmt(signal.expected_gain_2)}%)<br>${fmt(signal.target_price_3)} (${fmt(signal.expected_gain_3)}%)</td>
                    <td>${fmt(signal.stop_loss)}</td>
                    <td>${signal.entry_price<signal.target_price?'<span class="badge badge-success">🔺 Bullish</span>':'<span class="badge badge-danger">🔻 Bearish</span>'}</td>
                </tr>`;
			});
			document.querySelector('#stock-table-body').innerHTML = stockHtml;
		}

		function refreshScalping() {
			document.querySelector('#scalping-table-body').innerHTML =
				'<tr><td colspan="8" class="text-center loading-text"><span></span><span></span><span></span></td></tr>';
			fetch('/dashboard/refresh-scalping').then(res => res.json()).then(updateScalpingTable).catch(err => console.error(
				err));
		}

		function refreshSignals() {
			document.querySelector('#crypto-table-body').innerHTML =
				'<tr><td colspan="5" class="text-center loading-text"><span></span><span></span><span></span></td></tr>';
			document.querySelector('#stock-table-body').innerHTML =
				'<tr><td colspan="5" class="text-center loading-text"><span></span><span></span><span></span></td></tr>';
			fetch('/dashboard/refresh-signals').then(res => res.json()).then(updateSignalsTables).catch(err => console.error(
				err));
		}

		document.addEventListener('DOMContentLoaded', () => {
			refreshScalping();
			refreshSignals();
			setInterval(() => {
				refreshScalping();
				refreshSignals();
			}, 900000);
		});
	</script>
</body>

</html>
