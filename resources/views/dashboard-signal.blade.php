<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="csrf-token" content="{{ csrf_token() }}" />
		<title>Scalping & Swing Trading Signals</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
		<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
		<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
		<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
		<style>
			body {
				background-color: #f8f9fa;
				color: #212529;
				font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
				min-height: 100vh;
			}

			h1 {
				font-weight: 700;
				color: #0d6efd;
				text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
			}

			.last-updated {
				font-size: 0.9em;
				color: #fff;
				background: #0d6efd;
				display: inline-block;
				padding: 5px 15px;
				border-radius: 20px;
			}

			#refresh-indicator {
				color: #fff;
				background: #28a745;
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
				background-color: rgba(40, 167, 69, 0.15) !important;
				border-color: rgba(40, 167, 69, 0.4) !important;
				color: #28a745 !important;
			}

			.badge-session-closed {
				background-color: rgba(220, 53, 69, 0.15) !important;
				border-color: rgba(220, 53, 69, 0.4) !important;
				color: #dc3545 !important;
			}

			.badge-session-break {
				background-color: rgba(255, 193, 7, 0.15) !important;
				border-color: rgba(255, 193, 7, 0.4) !important;
				color: #ffc107 !important;
			}

			.signal-card {
				background: white;
				border-radius: 12px;
				padding: 0;
				margin-bottom: 25px;
				box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
				border: 1px solid #dee2e6;
				overflow: hidden;
			}

			.signal-header {
				background-color: #f8f9fa;
				padding: 15px 20px;
				border-bottom: 1px solid #dee2e6;
				display: flex;
				justify-content: space-between;
				align-items: center;
			}

			.signal-title {
				font-size: 1.1rem;
				font-weight: 600;
				color: #495057;
				margin: 0;
			}

			.signal-type-badge {
				padding: 2px 8px;
				font-size: 0.65rem;
				font-weight: 600;
				margin-left: 8px;
				white-space: nowrap;
			}

			.signal-body {
				padding: 20px;
			}

			.signal-item {
				background: #f8f9fa;
				border: 1px solid #dee2e6;
				border-left: 3px solid #28a745;
				padding: 15px;
				margin-bottom: 15px;
				border-radius: 6px;
				transition: all 0.2s ease;
			}

			.signal-item:hover {
				background: #fff;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
				transform: translateX(2px);
			}

			.signal-item.strong-buy {
				border-left-color: #dc3545;
			}

			.signal-item-header {
				display: flex;
				justify-content: space-between;
				align-items: flex-start;
				margin-bottom: 12px;
				padding-bottom: 10px;
				border-bottom: 1px solid #e9ecef;
			}

			.signal-symbol {
				font-size: 1.15rem;
				font-weight: 600;
				color: #212529;
				margin-bottom: 3px;
			}

			.signal-desc {
				color: #6c757d;
				font-size: 0.85rem;
			}

			.signal-badge {
				display: inline-block;
				padding: 3px 10px;
				border-radius: 4px;
				font-size: 0.75rem;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: 0.3px;
			}

			.badge-strong-buy {
				background-color: #dc3545;
				color: white;
			}

			.badge-buy {
				background-color: #28a745;
				color: white;
			}

			.signal-details {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
				gap: 10px;
			}

			.detail-item {
				background: white;
				padding: 8px 10px;
				border-radius: 4px;
				border: 1px solid #e9ecef;
			}

			.detail-label {
				font-size: 0.7rem;
				color: #6c757d;
				text-transform: uppercase;
				letter-spacing: 0.3px;
				margin-bottom: 3px;
				font-weight: 500;
			}

			.detail-value {
				font-size: 0.95rem;
				font-weight: 600;
				color: #212529;
			}

			.detail-value.price {
				color: #0d6efd;
			}

			.detail-value.entry {
				color: #198754;
			}

			.detail-value.stop-loss {
				color: #dc3545;
			}

			.detail-value.take-profit {
				color: #fd7e14;
			}

			.no-signals {
				text-align: center;
				padding: 50px 20px;
				color: #6c757d;
			}

			.no-signals h5 {
				font-weight: 600;
				color: #495057;
				margin-top: 15px;
			}

			.loading-spinner {
				text-align: center;
				padding: 40px;
			}

			.loading-spinner .spinner-border {
				width: 2.5rem;
				height: 2.5rem;
				color: #0d6efd;
			}

			.timestamp {
				color: #6c757d;
				font-size: 0.75rem;
				text-align: right;
				margin-top: 8px;
				padding-top: 8px;
				border-top: 1px solid #e9ecef;
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
				color: rgba(0, 0, 0, 0.04);
				white-space: nowrap;
			}

			@media (max-width: 768px) {
				.signal-details {
					grid-template-columns: 1fr;
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
			<h1 class="text-center mb-3">🚀 Scalping & Swing Trading Signals</h1>
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
								<span class="signal-title">Scalping Signals</span>
								<br><small id="trading-session-scalping" class="badge"></small>
							</div>
							<div class="d-flex align-items-center gap-2">
								<span id="countdown-timer-scalping" class="badge bg-light countdown-timer">15:00</span>
							</div>
						</div>
						<div class="signal-body" id="scalping-signals-container">
							<div class="loading-spinner">
								<div class="spinner-border" role="status">
									<span class="visually-hidden">Loading...</span>
								</div>
								<p class="mt-3 text-muted">Loading scalping signals...</p>
							</div>
						</div>
					</div>
				</div>

				<div class="col-lg-6 mb-4">
					<div class="signal-card">
						<div class="signal-header">
							<div>
								<span class="signal-title">Swing Trading Signals</span>
								<br><small id="trading-session-swing" class="badge"></small>
							</div>
							<div class="d-flex align-items-center gap-2">
								<span id="countdown-timer-swing" class="badge bg-light countdown-timer">60:00</span>
							</div>
						</div>
						<div class="signal-body" id="swing-signals-container">
							<div class="loading-spinner">
								<div class="spinner-border" role="status">
									<span class="visually-hidden">Loading...</span>
								</div>
								<p class="mt-3 text-muted">Loading swing signals...</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script>
			var countdownIntervalScalping;
			var countdownIntervalSwing;
			var timeRemainingScalping = 15 * 60;
			var timeRemainingSwing = 60 * 60;
			var isMob = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

			function formatNumber(num) {
				return new Intl.NumberFormat('id-ID').format(num);
			}

			function renderScalpingSignals(signals) {
				const container = $('#scalping-signals-container');

				if (signals.length === 0) {
					container.html(`
                    <div class="no-signals">
                        <svg width="64" height="64" fill="currentColor" class="text-muted opacity-50" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <h5 class="mt-3">No Active Signals</h5>
                        <p>Waiting for strong BUY signals...</p>
                    </div>
                `);
					return;
				}

				const buySignals = signals.filter(s => s.signal === 'STRONG BUY' || s.signal === 'BUY').slice(0, 5);

				if (buySignals.length === 0) {
					container.html(`
                    <div class="no-signals">
                        <svg width="64" height="64" fill="currentColor" class="text-muted opacity-50" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <h5 class="mt-3">No BUY Signals</h5>
                        <p>Market conditions not favorable for entry</p>
                    </div>
                `);
					return;
				}

				let html = '';
				buySignals.forEach((signal, index) => {
					const isStrongBuy = signal.signal === 'STRONG BUY';
					const itemClass = isStrongBuy ? 'signal-item strong-buy' : 'signal-item';
					const badgeClass = isStrongBuy ? 'badge-strong-buy' : 'badge-buy';

					html += `
                    <div class="${itemClass}">
                        <div class="signal-item-header">
                            <div>
                                <div class="signal-symbol">#${index + 1} ${signal.symbol}</div>
                                <div class="signal-desc">${signal.description}</div>
                            </div>
                            <span class="signal-badge ${badgeClass}">${signal.signal}</span>
                        </div>
                        
                        <div class="signal-details">
                            <div class="detail-item">
                                <div class="detail-label">Current Price</div>
                                <div class="detail-value price">${formatNumber(signal.price)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Entry Zone</div>
                                <div class="detail-value entry">${formatNumber(signal.entry1)} - ${formatNumber(signal.entry2)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Stop Loss</div>
                                <div class="detail-value stop-loss">${formatNumber(signal.stopLoss)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Take Profit 1</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit1)} <small class="text-muted">(${signal.takeProfit1_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Take Profit 2</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit2)} <small class="text-muted">(${signal.takeProfit2_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">RSI</div>
                                <div class="detail-value">${signal.rsi}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Risk/Reward</div>
                                <div class="detail-value">1:${signal.riskReward}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Score</div>
                                <div class="detail-value">${signal.score}</div>
                            </div>
                        </div>                        
                    </div>
                `;
				});

				container.html(html);
			}

			function renderSwingSignals(signals) {
				const container = $('#swing-signals-container');

				if (signals.length === 0) {
					container.html(`
                    <div class="no-signals">
                        <svg width="64" height="64" fill="currentColor" class="text-muted opacity-50" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <h5 class="mt-3">No Active Signals</h5>
                        <p>Waiting for strong BUY signals...</p>
                    </div>
                `);
					return;
				}

				const buySignals = signals.filter(s => s.signal === 'STRONG BUY' || s.signal === 'BUY').slice(0, 5);

				if (buySignals.length === 0) {
					container.html(`
                    <div class="no-signals">
                        <svg width="64" height="64" fill="currentColor" class="text-muted opacity-50" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <h5 class="mt-3">No BUY Signals</h5>
                        <p>Market conditions not favorable for entry</p>
                    </div>
                `);
					return;
				}

				let html = '';
				buySignals.forEach((signal, index) => {
					const isStrongBuy = signal.signal === 'STRONG BUY';
					const itemClass = isStrongBuy ? 'signal-item strong-buy' : 'signal-item';
					const badgeClass = isStrongBuy ? 'badge-strong-buy' : 'badge-buy';
					const trendLabel = signal.trendStrength == 2 ? 'Strong Uptrend' : (signal.trendStrength == 1 ?
						'Uptrend' : 'Neutral');

					html += `
                    <div class="${itemClass}">
                        <div class="signal-item-header">
                            <div>
                                <div class="signal-symbol">#${index + 1} ${signal.symbol} <small class="text-muted">(${trendLabel})</small></div>
                                <div class="signal-desc">${signal.description}</div>
                            </div>
                            <span class="signal-badge ${badgeClass}">${signal.signal}</span>
                        </div>
                        
                        <div class="signal-details">
                            <div class="detail-item">
                                <div class="detail-label">Current Price</div>
                                <div class="detail-value price">${formatNumber(signal.price)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Entry Zone</div>
                                <div class="detail-value entry">${formatNumber(signal.entry1)} - ${formatNumber(signal.entry2)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Stop Loss</div>
                                <div class="detail-value stop-loss">${formatNumber(signal.stopLoss)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Take Profit 1</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit1)} <small class="text-muted">(${signal.takeProfit1_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Take Profit 2</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit2)} <small class="text-muted">(${signal.takeProfit2_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">RSI</div>
                                <div class="detail-value">${signal.rsi}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">MACD</div>
                                <div class="detail-value">${signal.macd}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">ADX</div>
                                <div class="detail-value">${signal.adx}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Risk/Reward</div>
                                <div class="detail-value">1:${signal.riskReward}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Score</div>
                                <div class="detail-value">${signal.score}</div>
                            </div>
                        </div>                        
                    </div>
                `;
				});

				container.html(html);
			}

			function loadSignals() {
				$('#refresh-indicator').show();

				$('#scalping-signals-container, #swing-signals-container').addClass('opacity-50');

				$.ajax({
					url: '/api/trading-signals',
					method: 'GET',
					dataType: 'json',
					success: function(data) {
						renderScalpingSignals(data.scalping_signals);
						renderSwingSignals(data.swing_signals);

						$('#scalping-signals-container, #swing-signals-container').removeClass('opacity-50');
						$('#refresh-indicator').hide();

						timeRemainingScalping = 15 * 60;
					},
					error: function(xhr, status, error) {
						$('#scalping-signals-container, #swing-signals-container').html(`
                        <div class="no-signals">
                            <svg width="64" height="64" fill="currentColor" class="text-danger opacity-50" viewBox="0 0 16 16">
                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                            </svg>
                            <h5 class="mt-3 text-danger">Error Loading Signals</h5>
                            <p>Please try again later</p>
                        </div>
                    `);
						$('#scalping-signals-container, #swing-signals-container').removeClass('opacity-50');
						$('#refresh-indicator').hide();
					}
				});
			}

			function loadSwingSignals() {
				$.ajax({
					url: '/api/trading-signals',
					method: 'GET',
					dataType: 'json',
					success: function(data) {
						renderSwingSignals(data.swing_signals);

						timeRemainingSwing = 60 * 60;
					},
					error: function(xhr, status, error) {
						console.error('Error loading swing signals:', error);
					}
				});
			}

			function updateCountdownScalping() {
				var minutes = Math.floor(timeRemainingScalping / 60);
				var seconds = timeRemainingScalping % 60;
				var timeString = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

				$('#countdown-timer-scalping').text(timeString);

				if (timeRemainingScalping <= 0) {
					loadSignals();
				} else {
					timeRemainingScalping--;
				}
			}

			function updateCountdownSwing() {
				var minutes = Math.floor(timeRemainingSwing / 60);
				var seconds = timeRemainingSwing % 60;
				var timeString = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

				$('#countdown-timer-swing').text(timeString);

				if (timeRemainingSwing <= 0) {
					loadSwingSignals();
				} else {
					timeRemainingSwing--;
				}
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
					sessionElementScalping.innerHTML = 'Weekend - Market Closed';
					sessionElementScalping.className = 'badge badge-session-closed';
					sessionElementSwing.innerHTML = 'Weekend - Market Closed';
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
				loadSignals();

				if (!isMob) {
					setInterval(showTime, 500);
				} else {
					setInterval(showTime, 1000);
				}

				updateCountdownScalping();
				countdownIntervalScalping = setInterval(updateCountdownScalping, 1000);

				updateCountdownSwing();
				countdownIntervalSwing = setInterval(updateCountdownSwing, 1000);
			});
		</script>
	</body>

</html>
