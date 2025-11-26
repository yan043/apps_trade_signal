<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="csrf-token" content="{{ csrf_token() }}" />
		<title>Best Stock Market Trends</title>
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

			.card {
				background-color: #fff;
				border: 1px solid #dee2e6;
				border-radius: 12px;
				box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
				margin-bottom: 25px;
				overflow: hidden;
			}

			.card-header {
				font-weight: 600;
				letter-spacing: .3px;
				color: #495057;
				background-color: #f8f9fa;
				border-bottom: 1px solid #dee2e6;
			}

			.logo {
				width: 22px;
				height: 22px;
				border-radius: 50%;
				background: #f8f9fa;
				border: 1px solid #dee2e6;
				margin-right: 6px;
			}

			.symbol-cell {
				display: flex;
				align-items: center;
				gap: 8px;
				font-weight: 600;
			}

			.value-up {
				color: #22ab94 !important;
				font-weight: 600;
			}

			.value-down {
				color: #f7525f !important;
				font-weight: 600;
			}

			.ratingIcon-ibwgrGVw {
				display: inline-flex;
				vertical-align: -2px;
				margin-right: 4px;
				line-height: 0;
				color: inherit;
			}

			table tbody tr td.value-up {
				color: #22ab94 !important;
			}

			table tbody tr td.value-down {
				color: #f7525f !important;
			}

			.dataTables_wrapper .dataTables_paginate .paginate_button.current {
				background: #0d6efd !important;
				border-color: #0d6efd !important;
			}

			.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
				background: #0b5ed7 !important;
				border-color: #0a58ca !important;
			}

			.card-header .btn-toggle {
				border: 1px solid #dee2e6;
				color: #6c757d;
				background: #fff;
				padding: 2px 8px;
				font-size: .85rem;
				border-radius: 6px;
			}

			.btn-toggle .chevron {
				width: 14px;
				height: 14px;
				transition: transform .2s ease;
			}

			.btn-toggle[aria-expanded="false"] .chevron {
				transform: rotate(-90deg);
			}

			.table-updating {
				opacity: 0.6;
				transition: opacity 0.3s ease;
			}

			.table-updated {
				background-color: rgba(40, 167, 69, 0.1);
				transition: background-color 0.5s ease;
			}

			#countdown-timer-1 {
				font-family: 'Courier New', monospace;
				font-weight: 600;
				color: #6c757d;
				font-size: 0.85rem;
				min-width: 40px;
				text-align: center;
			}

			.watermark {
				position: fixed;
				inset: 0;
				display: flex;
				align-items: center;
				justify-content: center;
				pointer-events: none;
				z-index: 10;
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
				color: rgba(0, 0, 0, 0.06);
				white-space: nowrap;
			}

			.stock-match-highlight {
				background-color: rgba(255, 193, 7, 0.15) !important;
				border-left: 4px solid #ffc107 !important;
			}

			.stock-match-highlight:hover {
				background-color: rgba(255, 193, 7, 0.25) !important;
			}

			.match-indicator {
				display: inline-flex;
				align-items: center;
				background-color: #ffc107;
				color: #212529;
				font-size: 0.65rem;
				font-weight: 600;
				padding: 2px 6px;
				border-radius: 10px;
				margin-left: 8px;
				white-space: nowrap;
			}

			.match-indicator::before {
				content: "★";
				margin-right: 3px;
				font-size: 0.7rem;
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
		</style>

	</head>

	<body>
		<div class="watermark" aria-hidden="true">
			<div class="watermark-content">
				<div class="wm-line">Data Powered by TradingView</div>
				<div class="wm-line">Github @yan043</div>
			</div>
		</div>
		<div class="container-fluid mt-4">
			<h1 class="text-center mb-3">🚀 Best Stock Market Trends</h1>
			<div class="text-center mb-4">
				<div class="last-updated" id="last-updated">
					<span id="clock"></span>
					<span id="refresh-indicator" class="ms-2" style="display: none;">
						<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
						Updating...
					</span>
				</div>
				@php
					$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
					$isMob = is_numeric(strpos($ua, 'mobile'));
				@endphp
			</div>

			<div class="row g-3">
				<div class="col-12">
					<div class="card">
						<div class="card-header d-flex align-items-center justify-content-between">
							<div>
								<span>Stock Trend Market</span>
								<br><small id="trading-session" class="badge"></small>
							</div>
							<div class="d-flex align-items-center gap-2">
								<span id="countdown-timer-1" class="badge bg-light">15:00</span>
								<button class="btn btn-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStockTable"
									aria-expanded="true" aria-controls="collapseStockTable">
									<svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
										<path fill-rule="evenodd"
											d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z"
											clip-rule="evenodd" />
									</svg>
								</button>
							</div>
						</div>
						<div id="collapseStockTable" class="collapse show">
							<div class="card-body">
								<div class="table-responsive">
									<table id="stock-table" class="table table-hover align-middle">
										<thead class="table-light">
											<tr>
												<th>Stock Symbol</th>
												<th class="text-center">Price</th>
												<th class="text-center">Change</th>
												<th class="text-center">Change %</th>
												<th class="text-center">Open</th>
												<th class="text-center">High</th>
												<th class="text-center">Low</th>
												<th class="text-center">Volume</th>
												<th class="text-center">P/E Ratio</th>
												<th class="text-center">Dividen Yield %</th>
												<th class="text-center">Analyst Rating</th>
											</tr>
										</thead>
										<tbody>
											@forelse ($stock_trend_markets as $row)
												<tr>
													<td class="symbol-cell">
														<img src="{{ $row['logo'] }}" alt="logo" class="logo" />
														<span>{{ $row['name'] }}</span>
														<small class="text-muted">{{ $row['description'] }}</small>
													</td>
													<td class="text-center">
														{{ number_format((float) str_replace(',', '', $row['price']), 0, ',', '.') }}
														<small class="text-muted">{{ $row['currency'] }}</small>
													</td>
													<td
														class="text-center {{ (float) str_replace(',', '', $row['change']) == 0 ? 'text-muted' : (str_starts_with($row['change'], '-') ? 'value-down' : 'value-up') }}">
														@if ((float) str_replace(',', '', $row['change']) == 0)
															{{ number_format((float) str_replace(',', '', $row['change']), 0, ',', '.') }}
														@elseif(str_starts_with($row['change'], '-'))
															{{ number_format((float) str_replace(',', '', $row['change']), 0, ',', '.') }}
														@else
															+{{ number_format((float) str_replace(',', '', $row['change']), 0, ',', '.') }}
														@endif
													</td>
													<td
														class="text-center {{ (float) $row['price_change'] == 0 ? 'text-muted' : (str_starts_with($row['price_change'], '-') ? 'value-down' : 'value-up') }}">
														@if ((float) $row['price_change'] == 0)
															{{ $row['price_change'] }}%
														@elseif(str_starts_with($row['price_change'], '-'))
															{{ $row['price_change'] }}%
														@else
															+{{ $row['price_change'] }}%
														@endif
													</td>
													<td class="text-center">{{ number_format((float) str_replace(',', '', $row['open']), 0, ',', '.') }}</td>
													<td class="text-center">{{ number_format((float) str_replace(',', '', $row['high']), 0, ',', '.') }}</td>
													<td class="text-center">{{ number_format((float) str_replace(',', '', $row['low']), 0, ',', '.') }}</td>
													<td class="text-center">{{ $row['volume'] }}</td>
													<td class="text-center">{{ $row['price_earnings_ttm'] }}</td>
													<td class="text-center">{{ $row['div_yield'] }} %</td>
													<td class="text-center">
														@php $ar = $row['analystRating'] ?? null; @endphp
														@if ($ar === 'StrongBuy')
															<span class="value-up">
																<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg
																		xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18">
																		<path fill="currentColor"
																			d="M9 3.3 13.7 8l-.7.7-4-4-4 4-.7-.7L9 3.3Zm0 6 4.7 4.7-.7.7-4-4-4 4-.7-.7L9 9.3Z"></path>
																	</svg></span>
																Strong Buy
															</span>
														@elseif ($ar === 'Buy')
															<span class="value-up">
																<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg
																		xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18">
																		<path fill="currentColor" d="m4.67 10.62.66.76L9 8.16l3.67 3.22.66-.76L9 6.84l-4.33 3.78Z"></path>
																	</svg></span>
																Buy
															</span>
														@elseif ($ar === 'Sell')
															<span class="value-down">
																<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg
																		xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18">
																		<path fill="currentColor" d="m4.67 7.38.66-.76L9 9.84l3.67-3.22.66.76L9 11.16 4.67 7.38Z"></path>
																	</svg></span>
																Sell
															</span>
														@elseif ($ar === 'StrongSell')
															<span class="value-down">
																<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg
																		xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18">
																		<path fill="currentColor"
																			d="m5 3.3 4 4 4-4 .7.7L9 8.7 4.3 4l.7-.7Zm0 6 4 4 4-4 .7.7L9 14.7 4.3 10l.7-.7Z"></path>
																	</svg></span>
																Strong Sell
															</span>
														@else
															<span>{{ $ar }}</span>
														@endif
													</td>
												</tr>
											@empty
												<tr>
													<td colspan="9" class="text-center">No data</td>
												</tr>
											@endforelse
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<script>
				var isMob = {!! json_encode($isMob) !!};
				var stockTable;

				var countdownInterval;
				var timeRemaining = 15 * 60;

				var stockData = [];
				var gainersData = [];
				var mostActiveData = [];

				function updateTables() {
					$('#refresh-indicator').show();

					$('#stock-table').addClass('table-updating');

					$.ajax({
						url: '/api/stock-data',
						method: 'GET',
						dataType: 'json',
						success: function(data) {
							if (data.stock_trend_markets && stockTable) {
								stockData = data.stock_trend_markets;
								gainersData = data.stock_market_movers_gainers || [];
								mostActiveData = data.stock_most_active || [];

								updateStockTable(stockData);

								setTimeout(function() {
									highlightMatchingRows();
								}, 300);
							}

							$('#stock-table').removeClass('table-updating');
							$('#stock-table').addClass('table-updated');

							setTimeout(function() {
								$('#stock-table').removeClass('table-updated');
							}, 2000);

							$('#refresh-indicator').hide();

							timeRemaining = 15 * 60;
						},
						error: function(xhr, status, error) {
							$('#stock-table').removeClass('table-updating');
							$('#refresh-indicator').hide();
						}
					});
				}

				function updateCountdown() {
					var minutes = Math.floor(timeRemaining / 60);
					var seconds = timeRemaining % 60;
					var timeString = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

					$('#countdown-timer-1').text(timeString);

					if (timeRemaining <= 0) {
						updateTables();
					} else {
						timeRemaining--;
					}
				}

				function getTopGainersMatches() {
					var matches = [];

					if (stockData.length === 0 || gainersData.length === 0) {
						return matches;
					}

					stockData.forEach(function(stock) {
						var match = gainersData.find(function(gainer) {
							return gainer.symbol === stock.name;
						});

						if (match) {
							matches.push(stock.name);
						}
					});

					return matches;
				}

				function getMostActiveMatches() {
					var matches = [];

					if (stockData.length === 0 || mostActiveData.length === 0) {
						return matches;
					}

					stockData.forEach(function(stock) {
						var match = mostActiveData.find(function(active) {
							return active.symbol === stock.name;
						});

						if (match) {
							matches.push(stock.name);
						}
					});

					return matches;
				}

				function highlightMatchingRows() {
					$('#stock-table tbody tr').removeClass('stock-match-highlight');
					$('.match-indicator').remove();

					var topGainersMatches = getTopGainersMatches();
					var mostActiveMatches = getMostActiveMatches();

					if (stockTable && stockTable.rows) {
						stockTable.rows().every(function(rowIdx, tableLoop, rowLoop) {
							var data = this.data();
							if (data && data[0]) {
								var tempDiv = $('<div>').html(data[0]);
								var rowStockName = tempDiv.find('span').first().text().trim();
								var node = this.node();
								var symbolCell = $(node).find('.symbol-cell').first();

								if (topGainersMatches.includes(rowStockName)) {
									$(node).addClass('stock-match-highlight');
									if (symbolCell.find('.match-indicator').length === 0) {
										symbolCell.append(
											'<span class="match-indicator" style="background-color: #28a745; color: white;">Top Gainers</span>'
										);
									}
								}

								if (mostActiveMatches.includes(rowStockName)) {
									$(node).addClass('stock-match-highlight');
									if (symbolCell.find('.match-indicator').length === 0) {
										symbolCell.append(
											'<span class="match-indicator" style="background-color: #007bff; color: white;">Most Active</span>'
										);
									} else {
										symbolCell.append(
											'<span class="match-indicator" style="background-color: #007bff; color: white; margin-left: 4px;">Most Active</span>'
										);
									}
								}
							}
						});
					}
				}

				function formatNumber(number) {
					if (number === null || number === undefined || number === '') {
						return number;
					}
					let numStr = number.toString().replace(/[^\d.-]/g, '');
					let num = parseFloat(numStr);
					if (isNaN(num)) {
						return number;
					}
					return num.toLocaleString('id-ID').replace(/,/g, '.');
				}

				function updateStockTable(data) {
					stockTable.clear();
					stockData = data;

					data.forEach(function(row) {
						var logo = row.logo || '';
						var name = row.name || '';
						var description = row.description || '';
						var price = row.price || '';
						var currency = row.currency || '';
						var change = (typeof row.change !== 'undefined' && row.change !== null) ? row.change.toString() : '';
						var price_change = (typeof row.price_change !== 'undefined' && row.price_change !== null) ? row
							.price_change.toString() : '';
						var open = row.open || '';
						var high = row.high || '';
						var low = row.low || '';
						var volume = row.volume || '';
						var price_earnings_ttm = row.price_earnings_ttm || '';
						var div_yield = row.div_yield || '';
						var analystRating = row.analystRating || '';

						var changeClass = change.startsWith('-') ? 'value-down' : 'value-up';
						var changeDisplay = change.startsWith('-') ? formatNumber(change) : (change !== '' ? '+' +
							formatNumber(change) : '');
						var priceChangeClass = price_change.startsWith('-') ? 'value-down' : 'value-up';
						var priceChangeDisplay = price_change.startsWith('-') ? price_change : (price_change !== '' ? '+' +
							price_change : '');
						var analystRatingHtml = getAnalystRatingHtml(analystRating);

						var rowArr = [
							'<div class="symbol-cell"><img src="' + logo + '" alt="logo" class="logo" /><span>' + name +
							'</span><small class="text-muted">' + description + '</small></div>',
							formatNumber(price) + ' <small class="text-muted">' + currency + '</small>',
							'<span class="' + changeClass + '">' + changeDisplay + '</span>',
							'<span class="' + priceChangeClass + '">' + priceChangeDisplay + '%</span>',
							formatNumber(open),
							formatNumber(high),
							formatNumber(low),
							volume,
							price_earnings_ttm,
							div_yield + ' %',
							analystRatingHtml
						];

						while (rowArr.length < 11) {
							rowArr.push('');
						}
						if (rowArr.length > 11) {
							rowArr = rowArr.slice(0, 11);
						}

						stockTable.row.add(rowArr);
					});

					stockTable.draw();
				}

				function getAnalystRatingHtml(rating) {
					if (rating === 'StrongBuy') {
						return '<span class="value-up"><span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="M9 3.3 13.7 8l-.7.7-4-4-4 4-.7-.7L9 3.3Zm0 6 4.7 4.7-.7.7-4-4-4 4-.7-.7L9 9.3Z"></path></svg></span>Strong Buy</span>';
					} else if (rating === 'Buy') {
						return '<span class="value-up"><span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="m4.67 10.62.66.76L9 8.16l3.67 3.22.66-.76L9 6.84l-4.33 3.78Z"></path></svg></span>Buy</span>';
					} else if (rating === 'Sell') {
						return '<span class="value-down"><span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="m4.67 7.38.66-.76L9 9.84l3.67-3.22.66.76L9 11.16 4.67 7.38Z"></path></svg></span>Sell</span>';
					} else if (rating === 'StrongSell') {
						return '<span class="value-down"><span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="m5 3.3 4 4 4-4 .7.7L9 8.7 4.3 4l.7-.7Zm0 6 4 4 4-4 .7.7L9 14.7 4.3 10l.7-.7Z"></path></svg></span>Strong Sell</span>';
					}
					return '<span>' + rating + '</span>';
				}

				if (isMob == false) {
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
						document.getElementById('clock').innerHTML = thisDay + ', ' + day + ' ' + months[month] + ' ' + year + ' | ' +
							curr_hour + ":" + curr_minute + ":" + curr_second + ' (Asia/Jakarta)';

						updateTradingSession(jakartaDate);
					}

					function checkTime(i) {
						if (i < 10) i = "0" + i;
						return i;
					}
					setInterval(showTime, 500);
				} else {
					function updateMobileTradingSession() {
						var jakartaDate = new Date(new Date().toLocaleString("en-US", {
							timeZone: "Asia/Jakarta"
						}));
						updateTradingSession(jakartaDate);
					}
					setInterval(updateMobileTradingSession, 30000);
					updateMobileTradingSession();
				}

				function updateTradingSession(jakartaDate) {
					var dayOfWeek = jakartaDate.getDay();
					var hour = jakartaDate.getHours();
					var minute = jakartaDate.getMinutes();
					var currentTime = hour * 100 + minute;

					var sessionElement = document.getElementById('trading-session');

					if (dayOfWeek === 0 || dayOfWeek === 6) {
						sessionElement.innerHTML = '📅 Weekend - Market Closed';
						sessionElement.className = 'badge badge-session-closed';
						return;
					}

					var isFriday = (dayOfWeek === 5);
					var session1Start = 900;
					var session1End = isFriday ? 1130 : 1200;
					var session2Start = isFriday ? 1400 : 1330;
					var session2End = 1630;

					if (currentTime >= session1Start && currentTime < session1End) {
						sessionElement.innerHTML = '🟢 Session I - Market Open (' + (isFriday ? '09:00-11:30' : '09:00-12:00') + ')';
						sessionElement.className = 'badge badge-session-active';
					} else if (currentTime >= session1End && currentTime < session2Start) {
						sessionElement.innerHTML = '⏸️ Lunch Break (' + (isFriday ? '11:30-14:00' : '12:00-13:30') + ')';
						sessionElement.className = 'badge badge-session-break';
					} else if (currentTime >= session2Start && currentTime < session2End) {
						sessionElement.innerHTML = '🟢 Session II - Market Open (' + (isFriday ? '14:00-16:30' : '13:30-16:30') + ')';
						sessionElement.className = 'badge badge-session-active';
					} else {
						sessionElement.innerHTML = '🔴 Market Closed';
						sessionElement.className = 'badge badge-session-closed';
					}
				}

				$(document).ready(function() {
					$.ajaxSetup({
						headers: {
							'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
						}
					});

					stockTable = $('#stock-table').DataTable({
						"pageLength": 50,
						"lengthMenu": [
							[10, 25, 50, 100, -1],
							[10, 25, 50, 100, "All"]
						],
						"order": [],
						"columnDefs": [{
							"orderable": false,
							"targets": 0
						}],
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

					var collapseEls = document.querySelectorAll('.collapse');
					collapseEls.forEach(function(el) {
						el.addEventListener('shown.bs.collapse', function(e) {
							if (e.target && e.target.id === 'collapseStockTable') {
								stockTable.columns.adjust();
							}
						});
					});

					@if (isset($stock_trend_markets) && isset($stock_market_movers_gainers) && isset($stock_most_active))
						stockData = {!! json_encode($stock_trend_markets) !!};
						gainersData = {!! json_encode($stock_market_movers_gainers) !!};
						mostActiveData = {!! json_encode($stock_most_active) !!};

						var checkTable = function() {
							if (stockTable && $('#stock-table tbody tr').length > 0) {
								highlightMatchingRows();
							} else {
								setTimeout(checkTable, 200);
							}
						};

						setTimeout(checkTable, 500);
					@endif

					setInterval(updateTables, 900000);

					updateCountdown();
					countdownInterval = setInterval(updateCountdown, 1000);
				});
			</script>
	</body>

</html>
