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
			background-color: #f8f9fa;
		}

		.last-updated {
			font-size: 0.9em;
			color: #6c757d;
		}
	</style>
</head>

<body>
	<div class="container-fluid mt-4">
		<h1 class="text-center mb-4">Trading Bot Dashboard</h1>
		<div class="last-updated text-center" id="last-updated">Last updated: <span
				id="update-time">{{ now()->format('H:i:s') }}</span></div>

		<div class="card">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0">Scalping Crypto Signals</h5>
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
						<tbody>
							@foreach ($scalpingSignals as $signal)
								<tr>
									<td>{{ $signal['symbol'] }}</td>
									<td>{{ number_format($signal['price'], 2) }}</td>
									<td>{{ $signal['ema9'] ? number_format($signal['ema9'], 2) : 'N/A' }}</td>
									<td>{{ $signal['ema21'] ? number_format($signal['ema21'], 2) : 'N/A' }}</td>
									<td>{{ $signal['rsi'] ? number_format($signal['rsi'], 2) : 'N/A' }}</td>
									<td>
										@if ($signal['action'] == 'BUY')
											<span class="badge bg-success">BUY</span>
										@elseif($signal['action'] == 'SELL')
											<span class="badge bg-danger">SELL</span>
										@else
											<span class="badge bg-secondary">HOLD</span>
										@endif
									</td>
									<td>{{ $signal['tp'] ? number_format($signal['tp'], 2) : '-' }}</td>
									<td>{{ $signal['sl'] ? number_format($signal['sl'], 2) : '-' }}</td>
									<td>{{ $signal['tp_percentage'] ? number_format($signal['tp_percentage'], 2) : '-' }}</td>
									<td>{{ $signal['sl_percentage'] ? number_format($signal['sl_percentage'], 2) : '-' }}</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header bg-success text-white">
				<h5 class="mb-0">Crypto Signals</h5>
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
						<tbody>
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
				<h5 class="mb-0">Stock Signals</h5>
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
						<tbody>
							@foreach ($stockSignals as $index => $signal)
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
		function updateTables(data) {
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
			document.querySelector('#scalping-table tbody').innerHTML = scalpingHtml;

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
			document.querySelector('#crypto-table tbody').innerHTML = cryptoHtml;

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
			document.querySelector('#stock-table tbody').innerHTML = stockHtml;

			const now = new Date();
			document.getElementById('update-time').textContent = now.toLocaleTimeString();
		}

		function refreshData() {
			fetch('/dashboard/refresh')
				.then(response => response.json())
				.then(data => {
					updateTables(data);
				})
				.catch(error => console.error('Error refreshing data:', error));
		}

		setInterval(refreshData, 30000);
	</script>
</body>

</html>
