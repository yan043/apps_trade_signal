<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="csrf-token" content="{{ csrf_token() }}" />
		<title>Swiss Army Knife Enhanced - Trading Signals</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
		<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
		<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
		<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
		<style>
			body {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: #212529;
				font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
				min-height: 100vh;
			}

			h1 {
				font-weight: 700;
				color: #fff;
				text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
			}

			.last-updated {
				font-size: 0.9em;
				color: #fff;
				background: rgba(255, 255, 255, 0.2);
				backdrop-filter: blur(10px);
				display: inline-block;
				padding: 8px 20px;
				border-radius: 25px;
				border: 1px solid rgba(255, 255, 255, 0.3);
			}

			#refresh-indicator {
				color: #fff;
				background: rgba(40, 167, 69, 0.8);
				backdrop-filter: blur(10px);
				padding: 5px 15px;
				border-radius: 20px;
				font-size: 0.85rem;
				display: inline-block;
				margin-left: 10px;
			}

			.countdown-timer {
				font-family: 'Courier New', monospace;
				font-weight: 600;
				color: #6c757d;
				font-size: 0.85rem;
				min-width: 40px;
				text-align: center;
			}

			.badge-session-active {
				background-color: rgba(40, 167, 69, 0.9) !important;
				border: 1px solid rgba(40, 167, 69, 1);
				color: #fff !important;
				padding: 6px 12px;
				font-size: 0.75rem;
			}

			.badge-session-closed {
				background-color: rgba(220, 53, 69, 0.9) !important;
				border: 1px solid rgba(220, 53, 69, 1);
				color: #fff !important;
				padding: 6px 12px;
				font-size: 0.75rem;
			}

			.badge-session-break {
				background-color: rgba(255, 193, 7, 0.9) !important;
				border: 1px solid rgba(255, 193, 7, 1);
				color: #212529 !important;
				padding: 6px 12px;
				font-size: 0.75rem;
			}

			.signal-card {
				background: rgba(255, 255, 255, 0.95);
				backdrop-filter: blur(20px);
				border-radius: 15px;
				padding: 0;
				margin-bottom: 25px;
				box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
				border: 1px solid rgba(255, 255, 255, 0.3);
				overflow: hidden;
			}

			.signal-header {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				padding: 18px 25px;
				border-bottom: 1px solid rgba(255, 255, 255, 0.2);
				display: flex;
				justify-content: space-between;
				align-items: center;
			}

			.signal-title {
				font-size: 1.3rem;
				font-weight: 700;
				color: #fff;
				margin: 0;
				text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
			}

			.signal-type-badge {
				padding: 4px 12px;
				font-size: 0.7rem;
				font-weight: 600;
				margin-left: 10px;
				white-space: nowrap;
				background: rgba(255, 255, 255, 0.25);
				color: #fff;
				border-radius: 12px;
			}

			.signal-body {
				padding: 25px;
				max-height: 85vh;
				overflow-y: auto;
			}

			.signal-body::-webkit-scrollbar {
				width: 8px;
			}

			.signal-body::-webkit-scrollbar-track {
				background: #f1f1f1;
				border-radius: 10px;
			}

			.signal-body::-webkit-scrollbar-thumb {
				background: #888;
				border-radius: 10px;
			}

			.signal-body::-webkit-scrollbar-thumb:hover {
				background: #555;
			}

			.logo {
				width: 22px;
				height: 22px;
				border-radius: 50%;
				background: #f8f9fa;
				border: 1px solid #dee2e6;
				margin-right: 6px;
				vertical-align: middle;
				object-fit: contain;
			}

			.symbol-cell {
				display: flex;
				align-items: center;
				gap: 8px;
				font-weight: 600;
			}

			.signal-indicator {
				display: inline-flex;
				align-items: center;
				font-size: 0.65rem;
				font-weight: 600;
				padding: 3px 8px;
				border-radius: 10px;
				margin-left: 8px;
				white-space: nowrap;
			}

			.signal-indicator::before {
				content: "★";
				margin-right: 3px;
				font-size: 0.7rem;
			}

			.signal-buy {
				background-color: #ffc107;
				color: #212529;
			}

			.signal-strong-buy {
				background-color: #28a745;
				color: white;
			}

			.signal-sell {
				background-color: #6c757d;
				color: white;
			}

			.no-signals {
				text-align: center;
				padding: 60px 20px;
				color: #6c757d;
			}

			.no-signals svg {
				opacity: 0.5;
			}

			.no-signals h5 {
				font-weight: 700;
				color: #495057;
				margin-top: 20px;
			}

			.loading-spinner {
				text-align: center;
				padding: 50px;
			}

			.loading-spinner .spinner-border {
				width: 3rem;
				height: 3rem;
				border-width: 4px;
			}

			.timestamp {
				color: #6c757d;
				font-size: 0.75rem;
				text-align: right;
				margin-top: 10px;
				padding-top: 10px;
				border-top: 1px solid rgba(0, 0, 0, 0.1);
			}

			.watermark {
				position: fixed;
				inset: 0;
				display: flex;
				align-items: center;
				justify-content: center;
				pointer-events: none;
				z-index: 1;
			}

			.watermark-content {
				transform: rotate(-30deg);
				text-align: center;
				user-select: none;
			}

			.watermark-content .wm-line {
				font-size: clamp(24px, 8vw, 96px);
				font-weight: 700;
				letter-spacing: 0.08em;
				color: rgba(255, 255, 255, 0.08);
				white-space: nowrap;
			}

			@media (max-width: 768px) {
				.signal-body {
					max-height: 70vh;
				}
			}
		</style>
	</head>

	<body>
		<div class="watermark" aria-hidden="true">
			<div class="watermark-content">
				<div class="wm-line">Data Powered by TradingView</div>
				<div class="wm-line">Github @yan043</div>
			</div>
		</div>

		<div class="container-fluid py-4">
			<h1 class="text-center mb-3">🚀 Swiss Army Knife Enhanced Trading Signals</h1>
			<div class="text-center mb-4">
				<div class="last-updated" id="last-updated">
					<span id="clock"></span>
					<span id="refresh-indicator" class="ms-2" style="display: none;">
						<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
						Updating...
					</span>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-6 mb-4">
					<div class="signal-card">
						<div class="signal-header">
							<div>
								<span class="signal-title">⚡ Scalping Signals</span>
								<span class="signal-type-badge">M1-M5</span>
								<br><small id="trading-session-scalping" class="badge mt-2"></small>
							</div>
						</div>
						<div class="signal-body">
							<div class="table-responsive">
								<table id="scalping-table" class="table table-hover align-middle w-100">
									<thead class="table-light">
										<tr>
											<th>Symbol</th>
											<th>Price</th>
											<th>Entry</th>
											<th>TP1</th>
											<th>TP2</th>
											<th>TP3</th>
											<th>SL</th>
										</tr>
									</thead>
									<tbody id="scalping-table-body">
										<tr>
											<td colspan="7" class="text-center">Loading scalping signals...</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<div class="col-lg-6 mb-4">
					<div class="signal-card">
						<div class="signal-header">
							<div>
								<span class="signal-title">📊 Swing Trading Signals</span>
								<span class="signal-type-badge">H1-D1</span>
								<br><small id="trading-session-swing" class="badge mt-2"></small>
							</div>
						</div>
						<div class="signal-body">
							<div class="table-responsive">
								<table id="swing-table" class="table table-hover align-middle w-100">
									<thead class="table-light">
										<tr>
											<th>Symbol</th>
											<th>Price</th>
											<th>Entry</th>
											<th>TP1</th>
											<th>TP2</th>
											<th>TP3</th>
											<th>SL</th>
										</tr>
									</thead>
									<tbody id="swing-table-body">
										<tr>
											<td colspan="7" class="text-center">Loading swing signals...</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
			var isMob = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

			function formatNumber(num) {
				return new Intl.NumberFormat('id-ID', {
					minimumFractionDigits: 0,
					maximumFractionDigits: 0
				}).format(num);
			}

			function getSignalClass(signal) {
				if (signal === 'STRONG BUY') return 'signal-strong-buy';
				if (signal === 'BUY') return 'signal-buy';
				return 'signal-sell';
			}

			function renderScalpingSignals(signals) {
				var tbody = $('#scalping-table-body');
				if (window.scalpingTable) {
					window.scalpingTable.clear();
					if (!signals || signals.length === 0) {
						window.scalpingTable.draw();
						return;
					}
					signals.forEach(function(signal) {
						var signalClass = getSignalClass(signal.signal);
						var symbolCell = '<div class="symbol-cell">' +
							'<img src="' + signal.logo +
							'" alt="logo" class="logo" onerror=\'this.style.display="none"\' />' +
							'<span>' + signal.symbol + '</span>' +
							'<span class="signal-indicator ' + signalClass + '">' + signal.signal + ' (Score: ' + signal
							.score + ')</span>' +
							'</div>';
						var row = [
							symbolCell,
							formatNumber(signal.price),
							formatNumber(signal.entry1) + ' - ' + formatNumber(signal.entry2),
							formatNumber(signal.takeProfit1),
							formatNumber(signal.takeProfit2),
							formatNumber(signal.takeProfit3),
							formatNumber(signal.stopLoss)
						];
						window.scalpingTable.row.add(row);
					});
					window.scalpingTable.draw();
				} else {
					tbody.empty();
					if (!signals || signals.length === 0) {
						tbody.append('<tr><td colspan="7" class="text-center">No Active Signals</td></tr>');
						return;
					}
					signals.forEach(function(signal) {
						var signalClass = getSignalClass(signal.signal);
						var symbolCell = '<div class="symbol-cell">' +
							'<img src="' + signal.logo +
							'" alt="logo" class="logo" onerror=\'this.style.display="none"\' />' +
							'<span>' + signal.symbol + '</span>' +
							'<span class="signal-indicator ' + signalClass + '">' + signal.signal + ' (Score: ' + signal
							.score + ')</span>' +
							'</div>';
						var row = [
							symbolCell,
							formatNumber(signal.price),
							formatNumber(signal.entry1) + ' - ' + formatNumber(signal.entry2),
							formatNumber(signal.takeProfit1),
							formatNumber(signal.takeProfit2),
							formatNumber(signal.takeProfit3),
							formatNumber(signal.stopLoss)
						];
						tbody.append('<tr><td>' + row.join('</td><td>') + '</td></tr>');
					});
				}
			}

			function renderSwingSignals(signals) {
				var tbody = $('#swing-table-body');
				if (window.swingTable) {
					window.swingTable.clear();
					if (!signals || signals.length === 0) {
						window.swingTable.draw();
						return;
					}
					signals.forEach(function(signal) {
						var signalClass = getSignalClass(signal.signal);
						var symbolCell = '<div class="symbol-cell">' +
							'<img src="' + signal.logo +
							'" alt="logo" class="logo" onerror=\'this.style.display="none"\' />' +
							'<span>' + signal.symbol + '</span>' +
							'<span class="signal-indicator ' + signalClass + '">' + signal.signal + ' (Score: ' + signal
							.score + ')</span>' +
							'</div>';
						var row = [
							symbolCell,
							formatNumber(signal.price),
							formatNumber(signal.entry1) + ' - ' + formatNumber(signal.entry2),
							formatNumber(signal.takeProfit1),
							formatNumber(signal.takeProfit2),
							formatNumber(signal.takeProfit3),
							formatNumber(signal.stopLoss)
						];
						window.swingTable.row.add(row);
					});
					window.swingTable.draw();
				} else {
					tbody.empty();
					if (!signals || signals.length === 0) {
						tbody.append('<tr><td colspan="7" class="text-center">No Active Signals</td></tr>');
						return;
					}
					signals.forEach(function(signal) {
						var signalClass = getSignalClass(signal.signal);
						var symbolCell = '<div class="symbol-cell">' +
							'<img src="' + signal.logo +
							'" alt="logo" class="logo" onerror=\'this.style.display="none"\' />' +
							'<span>' + signal.symbol + '</span>' +
							'<span class="signal-indicator ' + signalClass + '">' + signal.signal + ' (' + signal.score +
							')</span>' +
							'</div>';
						var row = [
							symbolCell,
							formatNumber(signal.price),
							formatNumber(signal.entry1) + ' - ' + formatNumber(signal.entry2),
							formatNumber(signal.takeProfit1),
							formatNumber(signal.takeProfit2),
							formatNumber(signal.takeProfit3),
							formatNumber(signal.stopLoss)
						];
						tbody.append('<tr><td>' + row.join('</td><td>') + '</td></tr>');
					});
				}
			}

			function loadSignalsRealtime() {
				$('#refresh-indicator').show();
				$.ajax({
					url: '/api/trading-signals',
					method: 'GET',
					dataType: 'json',
					success: function(data) {
						renderScalpingSignals(data.scalping_signals);
						renderSwingSignals(data.swing_signals);
						if (window.scalpingTable) window.scalpingTable.draw();
						if (window.swingTable) window.swingTable.draw();
						if (data.last_updated) {
							$("#last-updated #clock").parent().attr('title', 'Last updated: ' + data.last_updated +
								' WIB');
						}
						$('#refresh-indicator').hide();
					},
					error: function(xhr, status, error) {
						console.error('Error loading signals:', error);
						$('#refresh-indicator').hide();
					},
					complete: function() {
						setTimeout(loadSignalsRealtime, 5000);
					}
				});
			}

			function showTime() {
				var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October',
					'November', 'December'
				];
				var myDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

				var jakartaDate = new Date(new Date().toLocaleString("en-US", {
					timeZone: "Asia/Jakarta"
				}));
				var day = jakartaDate.getDate();
				var month = jakartaDate.getMonth();
				var thisDay = jakartaDate.getDay();
				thisDay = myDays[thisDay];
				var yy = jakartaDate.getFullYear();
				var year = yy;
				var curr_hour = jakartaDate.getHours();
				var curr_minute = jakartaDate.getMinutes();
				var curr_second = jakartaDate.getSeconds();
				curr_hour = checkTime(curr_hour);
				curr_minute = checkTime(curr_minute);
				curr_second = checkTime(curr_second);

				if (isMob) {
					document.getElementById('clock').innerHTML = day + ' ' + months[month] + ' ' + year + ' | ' + curr_hour + ":" +
						curr_minute + ":" + curr_second;
				} else {
					document.getElementById('clock').innerHTML = thisDay + ', ' + day + ' ' + months[month] + ' ' + year + ' | ' +
						curr_hour + ":" + curr_minute + ":" + curr_second + ' (Asia/Jakarta)';
				}

				updateTradingSession(jakartaDate);
			}

			function checkTime(i) {
				if (i < 10) i = "0" + i;
				return i;
			}

			function updateTradingSession(jakartaDate) {
				var dayOfWeek = jakartaDate.getDay();
				var hour = jakartaDate.getHours();
				var minute = jakartaDate.getMinutes();
				var currentTime = hour * 100 + minute;

				var sessionElementScalping = document.getElementById('trading-session-scalping');
				var sessionElementSwing = document.getElementById('trading-session-swing');

				if (dayOfWeek === 0 || dayOfWeek === 6) {
					sessionElementScalping.innerHTML = '🔴 Weekend - Market Closed';
					sessionElementScalping.className = 'badge badge-session-closed';
					sessionElementSwing.innerHTML = '🔴 Weekend - Market Closed';
					sessionElementSwing.className = 'badge badge-session-closed';
					return;
				}

				var isFriday = (dayOfWeek === 5);
				var session1Start = 900;
				var session1End = isFriday ? 1130 : 1200;
				var session2Start = isFriday ? 1400 : 1330;
				var session2End = 1630;

				var sessionText = '';
				var sessionClass = '';

				if (currentTime >= session1Start && currentTime < session1End) {
					sessionText = '🟢 Session I - Market Open (' + (isFriday ? '09:00-11:30' : '09:00-12:00') + ')';
					sessionClass = 'badge badge-session-active';
				} else if (currentTime >= session1End && currentTime < session2Start) {
					sessionText = '⏸️ Lunch Break (' + (isFriday ? '11:30-14:00' : '12:00-13:30') + ')';
					sessionClass = 'badge badge-session-break';
				} else if (currentTime >= session2Start && currentTime < session2End) {
					sessionText = '🟢 Session II - Market Open (' + (isFriday ? '14:00-16:30' : '13:30-16:30') + ')';
					sessionClass = 'badge badge-session-active';
				} else {
					sessionText = '🔴 Market Closed';
					sessionClass = 'badge badge-session-closed';
				}

				sessionElementScalping.innerHTML = sessionText;
				sessionElementScalping.className = sessionClass;
				sessionElementSwing.innerHTML = sessionText;
				sessionElementSwing.className = sessionClass;
			}

			$(document).ready(function() {
				$('#scalping-table-body').empty();
				$('#swing-table-body').empty();
				window.scalpingTable = $('#scalping-table').DataTable({
					"pageLength": -1,
					"lengthMenu": [
						[-1, 10, 25, 50, 100],
						["All", 10, 25, 50, 100]
					],
					"order": [],
					"language": {
						"search": "Search:",
						"lengthMenu": "Show _MENU_ entries",
						"info": "Showing _START_ to _END_ of _TOTAL_ entries",
						"paginate": {
							"first": "First",
							"last": "Last",
							"next": "Next",
							"previous": "Previous"
						}
					}
				});
				window.swingTable = $('#swing-table').DataTable({
					"pageLength": -1,
					"lengthMenu": [
						[-1, 10, 25, 50, 100],
						["All", 10, 25, 50, 100]
					],
					"order": [],
					"language": {
						"search": "Search:",
						"lengthMenu": "Show _MENU_ entries",
						"info": "Showing _START_ to _END_ of _TOTAL_ entries",
						"paginate": {
							"first": "First",
							"last": "Last",
							"next": "Next",
							"previous": "Previous"
						}
					}
				});
				loadSignalsRealtime();
				if (!isMob) {
					setInterval(showTime, 500);
				} else {
					setInterval(showTime, 1000);
				}
			});
		</script>
	</body>

</html>
