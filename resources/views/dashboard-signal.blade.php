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

			.signal-item {
				background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
				border: 2px solid transparent;
				border-left: 5px solid #28a745;
				padding: 18px;
				margin-bottom: 18px;
				border-radius: 12px;
				transition: all 0.3s ease;
				box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
			}

			.signal-item:hover {
				background: linear-gradient(135deg, #ffffff 0%, #e0e7ef 100%);
				box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
				transform: translateX(5px);
			}

			.signal-item.strong-buy {
				border-left-color: #dc3545;
				background: linear-gradient(135deg, #fff5f5 0%, #ffe0e0 100%);
			}

			.signal-item.strong-buy:hover {
				background: linear-gradient(135deg, #ffffff 0%, #ffd0d0 100%);
			}

			.signal-item-header {
				display: flex;
				justify-content: space-between;
				align-items: flex-start;
				margin-bottom: 15px;
				padding-bottom: 12px;
				border-bottom: 2px solid rgba(0, 0, 0, 0.1);
			}

			.signal-symbol {
				font-size: 1.25rem;
				font-weight: 700;
				color: #212529;
				margin-bottom: 4px;
			}

			.signal-desc {
				color: #6c757d;
				font-size: 0.85rem;
				font-weight: 500;
			}

			.signal-badge {
				display: inline-block;
				padding: 5px 15px;
				border-radius: 20px;
				font-size: 0.75rem;
				font-weight: 700;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
			}

			.badge-strong-buy {
				background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
				color: white;
			}

			.badge-buy {
				background: linear-gradient(135deg, #28a745 0%, #218838 100%);
				color: white;
			}

			.signal-details {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
				gap: 12px;
			}

			.detail-item {
				background: rgba(255, 255, 255, 0.8);
				padding: 10px 12px;
				border-radius: 8px;
				border: 1px solid rgba(0, 0, 0, 0.1);
				transition: all 0.2s ease;
			}

			.detail-item:hover {
				background: rgba(255, 255, 255, 1);
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
			}

			.detail-label {
				font-size: 0.7rem;
				color: #6c757d;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				margin-bottom: 4px;
				font-weight: 600;
			}

			.detail-value {
				font-size: 0.95rem;
				font-weight: 700;
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

			.signal-indicators {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
				margin-top: 12px;
				padding-top: 12px;
				border-top: 1px solid rgba(0, 0, 0, 0.1);
			}

			.indicator-badge {
				padding: 4px 10px;
				border-radius: 12px;
				font-size: 0.7rem;
				font-weight: 600;
				display: inline-flex;
				align-items: center;
				gap: 4px;
			}

			.indicator-badge.market-trending {
				background: rgba(40, 167, 69, 0.2);
				color: #28a745;
				border: 1px solid rgba(40, 167, 69, 0.4);
			}

			.indicator-badge.market-mild {
				background: rgba(255, 193, 7, 0.2);
				color: #ffc107;
				border: 1px solid rgba(255, 193, 7, 0.4);
			}

			.indicator-badge.market-ranging {
				background: rgba(108, 117, 125, 0.2);
				color: #6c757d;
				border: 1px solid rgba(108, 117, 125, 0.4);
			}

			.indicator-badge.divergence-bull {
				background: rgba(40, 167, 69, 0.2);
				color: #28a745;
				border: 1px solid rgba(40, 167, 69, 0.4);
			}

			.indicator-badge.divergence-bear {
				background: rgba(220, 53, 69, 0.2);
				color: #dc3545;
				border: 1px solid rgba(220, 53, 69, 0.4);
			}

			.indicator-badge.sr-support {
				background: rgba(40, 167, 69, 0.2);
				color: #28a745;
				border: 1px solid rgba(40, 167, 69, 0.4);
			}

			.indicator-badge.sr-resistance {
				background: rgba(220, 53, 69, 0.2);
				color: #dc3545;
				border: 1px solid rgba(220, 53, 69, 0.4);
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
				.signal-details {
					grid-template-columns: 1fr;
				}

				.signal-body {
					max-height: 70vh;
				}
			}
		</style>
	</head>

	<body>
		<div class="watermark" aria-hidden="true">
			<div class="watermark-content">
				<div class="wm-line">Swiss Army Knife Enhanced</div>
				<div class="wm-line">Data Powered by TradingView</div>
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
						<div class="signal-body" id="scalping-signals-container">
							<div class="loading-spinner">
								<div class="spinner-border text-primary" role="status">
									<span class="visually-hidden">Loading...</span>
								</div>
								<p class="mt-3 text-muted fw-bold">Loading scalping signals...</p>
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
						<div class="signal-body" id="swing-signals-container">
							<div class="loading-spinner">
								<div class="spinner-border text-primary" role="status">
									<span class="visually-hidden">Loading...</span>
								</div>
								<p class="mt-3 text-muted fw-bold">Loading swing signals...</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
			var isMob = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

			function formatNumber(num) {
				return new Intl.NumberFormat('id-ID').format(num);
			}

			function renderScalpingSignals(signals) {
				const container = $('#scalping-signals-container');

				if (signals.length === 0) {
					container.html(`
                    <div class="no-signals">
                        <svg width="64" height="64" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <h5 class="mt-3">No Active Signals</h5>
                        <p>Waiting for strong BUY signals...</p>
                    </div>
                `);
					return;
				}

				const buySignals = signals.filter(s => s.signal === 'STRONG BUY' || s.signal === 'BUY').slice(0, 10);

				if (buySignals.length === 0) {
					container.html(`
                    <div class="no-signals">
                        <svg width="64" height="64" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
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

					let marketStateClass = 'market-ranging';
					let marketStateIcon = '〰️';
					if (signal.marketState === 'TRENDING') {
						marketStateClass = 'market-trending';
						marketStateIcon = '📈';
					} else if (signal.marketState === 'MILD') {
						marketStateClass = 'market-mild';
						marketStateIcon = '📊';
					}

					let indicatorBadges =
						`<span class="indicator-badge ${marketStateClass}">${marketStateIcon} ${signal.marketState}</span>`;
					indicatorBadges +=
						`<span class="indicator-badge" style="background: rgba(13, 110, 253, 0.2); color: #0d6efd; border: 1px solid rgba(13, 110, 253, 0.4);">📊 Vol: ${signal.volumeRatio}x</span>`;

					if (signal.bullishDivergence) {
						indicatorBadges += `<span class="indicator-badge divergence-bull">✅ Bullish Div</span>`;
					}
					if (signal.bearishDivergence) {
						indicatorBadges += `<span class="indicator-badge divergence-bear">⚠️ Bearish Div</span>`;
					}
					if (signal.nearSupport) {
						indicatorBadges += `<span class="indicator-badge sr-support">🟢 Near Support</span>`;
					}
					if (signal.nearResistance) {
						indicatorBadges += `<span class="indicator-badge sr-resistance">🔴 Near Resistance</span>`;
					}

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
                                <div class="detail-label">💰 Current Price</div>
                                <div class="detail-value price">${formatNumber(signal.price)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">🎯 Entry Zone</div>
                                <div class="detail-value entry">${formatNumber(signal.entry1)} - ${formatNumber(signal.entry2)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">🛑 Stop Loss</div>
                                <div class="detail-value stop-loss">${formatNumber(signal.stopLoss)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">✅ Take Profit 1</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit1)} <small class="text-muted">(+${signal.takeProfit1_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">✅ Take Profit 2</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit2)} <small class="text-muted">(+${signal.takeProfit2_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">✅ Take Profit 3</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit3)} <small class="text-muted">(+${signal.takeProfit3_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">📈 RSI</div>
                                <div class="detail-value">${signal.rsi}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">📊 MACD</div>
                                <div class="detail-value">${signal.macd}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">📊 ADX</div>
                                <div class="detail-value">${signal.adx}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">⚖️ Risk/Reward</div>
                                <div class="detail-value">1:${signal.riskReward}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">🎯 Score</div>
                                <div class="detail-value">${signal.score}</div>
                            </div>
                        </div>
                        
                        <div class="signal-indicators">
                            ${indicatorBadges}
                        </div>
                        
                        <div class="timestamp">⏰ ${signal.timestamp}</div>
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
                        <svg width="64" height="64" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <h5 class="mt-3">No Active Signals</h5>
                        <p>Waiting for strong BUY signals...</p>
                    </div>
                `);
					return;
				}

				const buySignals = signals.filter(s => s.signal === 'STRONG BUY' || s.signal === 'BUY').slice(0, 10);

				if (buySignals.length === 0) {
					container.html(`
                    <div class="no-signals">
                        <svg width="64" height="64" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
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

					let trendLabel = '〰️ Neutral';
					let trendColor = '#6c757d';
					if (signal.trendStrength == 2) {
						trendLabel = '🚀 Strong Uptrend';
						trendColor = '#28a745';
					} else if (signal.trendStrength == 1) {
						trendLabel = '📈 Uptrend';
						trendColor = '#198754';
					} else if (signal.trendStrength == -1) {
						trendLabel = '📉 Downtrend';
						trendColor = '#dc3545';
					} else if (signal.trendStrength == -2) {
						trendLabel = '🔻 Strong Downtrend';
						trendColor = '#c82333';
					}

					let indicatorBadges =
						`<span class="indicator-badge" style="background: rgba(${trendColor === '#28a745' || trendColor === '#198754' ? '40, 167, 69' : trendColor === '#6c757d' ? '108, 117, 125' : '220, 53, 69'}, 0.2); color: ${trendColor}; border: 1px solid rgba(${trendColor === '#28a745' || trendColor === '#198754' ? '40, 167, 69' : trendColor === '#6c757d' ? '108, 117, 125' : '220, 53, 69'}, 0.4);">${trendLabel}</span>`;
					indicatorBadges +=
						`<span class="indicator-badge" style="background: rgba(13, 110, 253, 0.2); color: #0d6efd; border: 1px solid rgba(13, 110, 253, 0.4);">📊 Vol: ${signal.volumeRatio}x</span>`;

					if (signal.bullishDivergence) {
						indicatorBadges += `<span class="indicator-badge divergence-bull">✅ Bullish Div</span>`;
					}
					if (signal.bearishDivergence) {
						indicatorBadges += `<span class="indicator-badge divergence-bear">⚠️ Bearish Div</span>`;
					}
					if (signal.nearSupport) {
						indicatorBadges += `<span class="indicator-badge sr-support">🟢 Near Support</span>`;
					}
					if (signal.nearResistance) {
						indicatorBadges += `<span class="indicator-badge sr-resistance">🔴 Near Resistance</span>`;
					}

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
                                <div class="detail-label">💰 Current Price</div>
                                <div class="detail-value price">${formatNumber(signal.price)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">🎯 Entry Zone</div>
                                <div class="detail-value entry">${formatNumber(signal.entry1)} - ${formatNumber(signal.entry2)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">🛑 Stop Loss</div>
                                <div class="detail-value stop-loss">${formatNumber(signal.stopLoss)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">✅ Take Profit 1</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit1)} <small class="text-muted">(+${signal.takeProfit1_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">✅ Take Profit 2</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit2)} <small class="text-muted">(+${signal.takeProfit2_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">✅ Take Profit 3</div>
                                <div class="detail-value take-profit">${formatNumber(signal.takeProfit3)} <small class="text-muted">(+${signal.takeProfit3_percent}%)</small></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">📈 RSI</div>
                                <div class="detail-value">${signal.rsi}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">📊 MACD</div>
                                <div class="detail-value">${signal.macd}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">📊 ADX</div>
                                <div class="detail-value">${signal.adx}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">⚖️ Risk/Reward</div>
                                <div class="detail-value">1:${signal.riskReward}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">🎯 Score</div>
                                <div class="detail-value">${signal.score}</div>
                            </div>
                        </div>
                        
                        <div class="signal-indicators">
                            ${indicatorBadges}
                        </div>
                        
                        <div class="timestamp">⏰ ${signal.timestamp}</div>
                    </div>
                `;
				});

				container.html(html);
			}

			function loadSignalsRealtime() {
				$('#refresh-indicator').show();
				$('#scalping-signals-container, #swing-signals-container').addClass('opacity-50');

				$.ajax({
					url: '/api/trading-signals',
					method: 'GET',
					dataType: 'json',
					success: function(data) {
						renderScalpingSignals(data.scalping_signals);
						renderSwingSignals(data.swing_signals);
						if (data.last_updated) {
							$("#last-updated #clock").parent().attr('title', 'Last updated: ' + data.last_updated +
								' WIB');
						}
						$('#refresh-indicator').hide();
						$('#scalping-signals-container, #swing-signals-container').removeClass('opacity-50');
					},
					error: function(xhr, status, error) {
						console.error('Error loading signals:', error);
						$('#refresh-indicator').hide();
						$('#scalping-signals-container, #swing-signals-container').removeClass('opacity-50');
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
