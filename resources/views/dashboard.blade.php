<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Trading Bot Dashboard</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<style>
		body {
			background: linear-gradient(135deg, #0d6efd, #0dcaf0);
			min-height: 100vh;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		}

		h1 {
			font-weight: 700;
			color: #fff;
			text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
		}

		.last-updated {
			font-size: 0.9em;
			color: #f8f9fa;
			background: rgba(0, 0, 0, 0.2);
			display: inline-block;
			padding: 5px 15px;
			border-radius: 30px;
		}

		.card {
			border: none;
			border-radius: 15px;
			box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
			margin-bottom: 25px;
			overflow: hidden;
		}

		.card-header {
			font-weight: bold;
			letter-spacing: 0.5px;
		}

		.table {
			border-radius: 10px;
			overflow: hidden;
		}

		.table thead th {
			text-transform: uppercase;
			font-size: 0.85rem;
		}

		.table-hover tbody tr:hover {
			background-color: rgba(13, 202, 240, 0.1);
			cursor: pointer;
		}

		.badge {
			font-size: 0.85rem;
			padding: 6px 12px;
			border-radius: 12px;
		}

		/* Animasi Loading */
		.loading-text {
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.loading-text span {
			width: 8px;
			height: 8px;
			margin: 0 2px;
			background: #0d6efd;
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
		<h1 class="text-center mb-3">🚀 Trading Bot Dashboard</h1>
		<div class="text-center mb-4">
			<div class="last-updated" id="last-updated">
				<span id="clock"></span>
			</div>
			@php
				$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
				$isMob = is_numeric(strpos($ua, 'mobile'));
			@endphp
		</div>

		<div class="card">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0">📊 Scalping Crypto Signals</h5>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover text-center" id="scalping-table">
						<thead class="table-dark">
							<tr>
								<th>Symbol</th>
								<th>Price</th>
								<th>EMA9</th>
								<th>EMA21</th>
								<th>RSI</th>
								<th>Action</th>
								<th>TP</th>
								<th>SL</th>
								<th>TP (%)</th>
								<th>SL (%)</th>
							</tr>
						</thead>
						<tbody id="scalping-table-body">
							<tr>
								<td colspan="10" class="text-center loading-text">
									<span></span><span></span><span></span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header bg-success text-white">
				<h5 class="mb-0">💹 Crypto Signals</h5>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover text-center" id="crypto-table">
						<thead class="table-dark">
							<tr>
								<th>Symbol</th>
								<th>Entry Price</th>
								<th>Target 1</th>
								<th>Target 2</th>
								<th>Target 3</th>
								<th>Stop Loss</th>
								<th>Gain 1 (%)</th>
								<th>Gain 2 (%)</th>
								<th>Gain 3 (%)</th>
								<th>Expired At</th>
							</tr>
						</thead>
						<tbody id="crypto-table-body">
							@foreach ($cryptoSignals as $signal)
								<tr>
									<td>{{ $signal->asset->symbol }}</td>
									<td>{{ number_format($signal->entry_price, 2) }}</td>
									<td>{{ number_format($signal->target_price, 2) }}</td>
									<td>{{ number_format($signal->target_price_2, 2) }}</td>
									<td>{{ number_format($signal->target_price_3, 2) }}</td>
									<td>{{ number_format($signal->stop_loss, 2) }}</td>
									<td>{{ number_format($signal->expected_gain, 2) }}</td>
									<td>{{ number_format($signal->expected_gain_2, 2) }}</td>
									<td>{{ number_format($signal->expected_gain_3, 2) }}</td>
									<td>{{ $signal->expired_at }}</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header bg-info text-white">
				<h5 class="mb-0">📈 Stock Signals</h5>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover text-center" id="stock-table">
						<thead class="table-dark">
							<tr>
								<th>Symbol</th>
								<th>Entry Price</th>
								<th>Target 1</th>
								<th>Target 2</th>
								<th>Target 3</th>
								<th>Stop Loss</th>
								<th>Gain 1 (%)</th>
								<th>Gain 2 (%)</th>
								<th>Gain 3 (%)</th>
								<th>Expired At</th>
							</tr>
						</thead>
						<tbody id="stock-table-body">
							@foreach ($stockSignals as $signal)
								<tr>
									<td>{{ $signal->asset->symbol }}</td>
									<td>{{ number_format($signal->entry_price, 2) }}</td>
									<td>{{ number_format($signal->target_price, 2) }}</td>
									<td>{{ number_format($signal->target_price_2, 2) }}</td>
									<td>{{ number_format($signal->target_price_3, 2) }}</td>
									<td>{{ number_format($signal->stop_loss, 2) }}</td>
									<td>{{ number_format($signal->expected_gain, 2) }}</td>
									<td>{{ number_format($signal->expected_gain_2, 2) }}</td>
									<td>{{ number_format($signal->expected_gain_3, 2) }}</td>
									<td>{{ $signal->expired_at }}</td>
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
				var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
					'September', 'Oktober', 'November', 'Desember'
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

				document.getElementById('clock').innerHTML = thisDay + ', ' + day + ' ' + months[month] + ' ' +
					year + ' | ' + curr_hour + ":" + curr_minute + ":" + curr_second;
			}

			function checkTime(i) {
				if (i < 10) {
					i = "0" + i;
				}
				return i;
			}
			setInterval(showTime, 500);
		}

		function updateScalpingTable(data) {
			let scalpingHtml = '';
			data.scalpingSignals.forEach(signal => {
				let actionBadge = '';
				if (signal.action === 'BUY') {
					actionBadge = '<span class="badge bg-success">BUY</span>';
				} else if (signal.action === 'SELL') {
					actionBadge = '<span class="badge bg-danger">SELL</span>';
				} else {
					actionBadge = '<span class="badge bg-secondary">HOLD</span>';
				}
				scalpingHtml += `
                    <tr>
                        <td>${signal.symbol}</td>
                        <td>${parseFloat(signal.price).toFixed(2)}</td>
                        <td>${signal.ema9 ? parseFloat(signal.ema9).toFixed(2) : 'N/A'}</td>
                        <td>${signal.ema21 ? parseFloat(signal.ema21).toFixed(2) : 'N/A'}</td>
                        <td>${signal.rsi ? parseFloat(signal.rsi).toFixed(2) : 'N/A'}</td>
                        <td>${actionBadge}</td>
                        <td>${signal.tp ? parseFloat(signal.tp).toFixed(2) : '-'}</td>
                        <td>${signal.sl ? parseFloat(signal.sl).toFixed(2) : '-'}</td>
                        <td>${signal.tp_percentage ? parseFloat(signal.tp_percentage).toFixed(2) : '-'}</td>
                        <td>${signal.sl_percentage ? parseFloat(signal.sl_percentage).toFixed(2) : '-'}</td>
                    </tr>
                `;
			});
			document.querySelector('#scalping-table-body').innerHTML = scalpingHtml;
		}

		function updateSignalsTables(data) {
			let cryptoHtml = '';
			data.cryptoSignals.forEach(signal => {
				cryptoHtml += `
                    <tr>
                        <td>${signal.asset.symbol}</td>
                        <td>${parseFloat(signal.entry_price).toFixed(2)}</td>
                        <td>${parseFloat(signal.target_price).toFixed(2)}</td>
                        <td>${parseFloat(signal.target_price_2).toFixed(2)}</td>
                        <td>${parseFloat(signal.target_price_3).toFixed(2)}</td>
                        <td>${parseFloat(signal.stop_loss).toFixed(2)}</td>
                        <td>${parseFloat(signal.expected_gain).toFixed(2)}</td>
                        <td>${parseFloat(signal.expected_gain_2).toFixed(2)}</td>
                        <td>${parseFloat(signal.expected_gain_3).toFixed(2)}</td>
                        <td>${signal.expired_at}</td>
                    </tr>
                `;
			});
			document.querySelector('#crypto-table-body').innerHTML = cryptoHtml;

			let stockHtml = '';
			data.stockSignals.forEach(signal => {
				stockHtml += `
                    <tr>
                        <td>${signal.asset.symbol}</td>
                        <td>${parseFloat(signal.entry_price).toFixed(2)}</td>
                        <td>${parseFloat(signal.target_price).toFixed(2)}</td>
                        <td>${parseFloat(signal.target_price_2).toFixed(2)}</td>
                        <td>${parseFloat(signal.target_price_3).toFixed(2)}</td>
                        <td>${parseFloat(signal.stop_loss).toFixed(2)}</td>
                        <td>${parseFloat(signal.expected_gain).toFixed(2)}</td>
                        <td>${parseFloat(signal.expected_gain_2).toFixed(2)}</td>
                        <td>${parseFloat(signal.expected_gain_3).toFixed(2)}</td>
                        <td>${signal.expired_at}</td>
                    </tr>
                `;
			});
			document.querySelector('#stock-table-body').innerHTML = stockHtml;
		}

		function refreshScalping() {
			document.querySelector('#scalping-table-body').innerHTML =
				'<tr><td colspan="10" class="text-center loading-text"><span></span><span></span><span></span></td></tr>';

			fetch('/dashboard/refresh-scalping')
				.then(response => response.json())
				.then(data => {
					updateScalpingTable(data);
				})
				.catch(error => console.error('Error refreshing scalping data:', error));
		}

		function refreshSignals() {
			document.querySelector('#crypto-table-body').innerHTML =
				'<tr><td colspan="10" class="text-center loading-text"><span></span><span></span><span></span></td></tr>';
			document.querySelector('#stock-table-body').innerHTML =
				'<tr><td colspan="10" class="text-center loading-text"><span></span><span></span><span></span></td></tr>';

			fetch('/dashboard/refresh-signals')
				.then(response => response.json())
				.then(data => {
					updateSignalsTables(data);
				})
				.catch(error => console.error('Error refreshing signals data:', error));
		}

		document.addEventListener('DOMContentLoaded', function() {
			refreshScalping();
			refreshSignals();
		});

		setInterval(() => {
			refreshScalping();
			refreshSignals();
		}, 900000);
	</script>
</body>

</html>
