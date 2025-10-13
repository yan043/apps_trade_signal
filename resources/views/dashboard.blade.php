<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Signal Stock Indonesia & Crypto</title>
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
			text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
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
			box-shadow: 0 2px 10px rgba(0,0,0,.1);
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

		.badge-buy {
			background-color: rgba(34, 171, 148, .15) !important;
			border-color: rgba(34, 171, 148, .4) !important;
			color: #22ab94 !important;
		}

		.badge-strong {
			background-color: rgba(34, 171, 148, .15) !important;
			border-color: rgba(34, 171, 148, .4) !important;
			color: #22ab94 !important;
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

		.brand-icon {
			display: inline-flex;
			vertical-align: -2px;
			margin-right: 6px;
			line-height: 0;
			color: inherit;
		}
		.brand-icon svg { width: 16px; height: 16px; }

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
		.btn-toggle .chevron { width: 14px; height: 14px; transition: transform .2s ease; }
		.btn-toggle[aria-expanded="false"] .chevron { transform: rotate(-90deg); }
	</style>

</head>

<body>
	<div class="container-fluid mt-4">
		<h1 class="text-center mb-3">🚀 Signal Stock Indonesia & Crypto</h1>
		<div class="text-center mb-3">
			<small class="text-muted d-block">
				<span class="brand-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M3 21V3"/>
						<path d="M3 21h18"/>
						<path d="M7 14l4-4 3 3 5-6"/>
					</svg>
				</span>
				Data Powered by TradingView
			</small>
			<small class="text-muted d-block">
				<span class="brand-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
						<path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.01.08-2.11 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.91.08 2.11.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38C13.71 14.53 16 11.54 16 8c0-4.42-3.58-8-8-8z"/>
					</svg>
				</span>
				Created by Mahdian (yan043)
			</small>
		</div>
		<div class="text-center mb-4">
			<div class="last-updated" id="last-updated">
				<span id="clock"></span>
			</div>
			@php
				$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
				$isMob = is_numeric(strpos($ua, 'mobile'));
			@endphp
		</div>

		<div class="row g-3">
			<div class="col-12 col-md-6">
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between">
						<span>Top Volume — Buy Candidates</span>
						<button class="btn btn-sm btn-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTopVolume" aria-expanded="true" aria-controls="collapseTopVolume">
							<svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"><path d="M11.354 10.354 8 7 4.646 10.354l-.708-.708L8 5.586l4.062 4.06-.708.708z"/></svg>
						</button>
					</div>
					<div id="collapseTopVolume" class="collapse show">
					<div class="card-body">
						<div class="table-responsive">
							<table class="table table-striped table-hover" id="top-volume-table">
								<thead>
									<tr>
										<th>Symbol</th>
										<th>Close</th>
										<th>Change</th>
										<th>Value</th>
										<th>Analyst Rating</th>
									</tr>
								</thead>
								<tbody>
									@forelse ($stock_top_volume_for_buy as $row)
									<tr>
										<td class="symbol-cell">
											<img src="{{ $row['logo'] }}" alt="logo" class="logo" />
											<span>{{ $row['name'] }}</span>
											<small class="text-muted">{{ $row['description'] }}</small>
										</td>
										<td>
											{{ number_format($row['close']) }}
											<small class="text-muted">{{ $row['currency'] }}</small>
										</td>
										<td class="{{ str_starts_with($row['change'], '+') ? 'value-up' : 'value-down' }}">{{ $row['change'] }}</td>
										<td>{{ $row['value'] }}</td>
										<td>
											@php $ar = $row['analystRating'] ?? null; @endphp
											@if ($ar === 'Strong Buy')
												<span class="value-up">
													<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="M9 3.3 13.7 8l-.7.7-4-4-4 4-.7-.7L9 3.3Zm0 6 4.7 4.7-.7.7-4-4-4 4-.7-.7L9 9.3Z"></path></svg></span>
													Strong Buy
												</span>
											@elseif ($ar === 'Buy')
												<span class="value-up">
													<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="m4.67 10.62.66.76L9 8.16l3.67 3.22.66-.76L9 6.84l-4.33 3.78Z"></path></svg></span>
													Buy
												</span>
											@else
												<span>{{ $ar }}</span>
											@endif
										</td>
									</tr>
									@empty
									<tr><td colspan="5" class="text-center">No data</td></tr>
									@endforelse
								</tbody>
							</table>
						</div>
					</div>
					</div>
				</div>
			</div>

			<div class="col-12 col-md-6">
				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between">
						<span>Technical Ratings — Strong</span>
						<button class="btn btn-sm btn-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTechRatings" aria-expanded="true" aria-controls="collapseTechRatings">
							<svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"><path d="M11.354 10.354 8 7 4.646 10.354l-.708-.708L8 5.586l4.062 4.06-.708.708z"/></svg>
						</button>
					</div>
					<div id="collapseTechRatings" class="collapse show">
					<div class="card-body">
						<div class="table-responsive">
							<table class="table table-striped table-hover" id="tech-rating-table">
								<thead>
									<tr>
										<th>Symbol</th>
										<th>Tech</th>
										<th>MAs</th>
										<th>Osc</th>
									</tr>
								</thead>
								<tbody>
									@forelse ($stock_technical_analysis as $row)
									<tr>
										<td class="symbol-cell">
											<img src="{{ $row['logo'] }}" alt="logo" class="logo" />
											<span>{{ $row['name'] }}</span>
											<small class="text-muted">{{ $row['description'] }}</small>
										</td>
										<td>
											@php $tech = $row['techRating_1D']; @endphp
											@if ($tech === 'Strong Buy')
												<span class="value-up">
													<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="M9 3.3 13.7 8l-.7.7-4-4-4 4-.7-.7L9 3.3Zm0 6 4.7 4.7-.7.7-4-4-4 4-.7-.7L9 9.3Z"></path></svg></span>
													Strong Buy
												</span>
											@elseif ($tech === 'Buy')
												<span class="value-up">
													<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="m4.67 10.62.66.76L9 8.16l3.67 3.22.66-.76L9 6.84l-4.33 3.78Z"></path></svg></span>
													Buy
												</span>
											@else
												<span>{{ $tech }}</span>
											@endif
										</td>
										<td>
											@php $ma = $row['maRating_1D']; @endphp
											@if ($ma === 'Strong Buy')
												<span class="value-up">
													<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="M9 3.3 13.7 8l-.7.7-4-4-4 4-.7-.7L9 3.3Zm0 6 4.7 4.7-.7.7-4-4-4 4-.7-.7L9 9.3Z"></path></svg></span>
													Strong Buy
												</span>
											@elseif ($ma === 'Buy')
												<span class="value-up">
													<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="m4.67 10.62.66.76L9 8.16l3.67 3.22.66-.76L9 6.84l-4.33 3.78Z"></path></svg></span>
													Buy
												</span>
											@else
												<span>{{ $ma }}</span>
											@endif
										</td>
										<td>
											@php $osc = $row['osRating_1D']; @endphp
											@if ($osc === 'Strong Buy')
												<span class="value-up">
													<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="M9 3.3 13.7 8l-.7.7-4-4-4 4-.7-.7L9 3.3Zm0 6 4.7 4.7-.7.7-4-4-4 4-.7-.7L9 9.3Z"></path></svg></span>
													Strong Buy
												</span>
											@elseif ($osc === 'Buy')
												<span class="value-up">
													<span role="img" class="ratingIcon-ibwgrGVw" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18"><path fill="currentColor" d="m4.67 10.62.66.76L9 8.16l3.67 3.22.66-.76L9 6.84l-4.33 3.78Z"></path></svg></span>
													Buy
												</span>
											@else
												<span>{{ $osc }}</span>
											@endif
										</td>
									</tr>
									@empty
									<tr><td colspan="4" class="text-center">No data</td></tr>
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
		if (isMob == false) {
			function showTime() {
				var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September','Oktober', 'November', 'Desember'];
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
				document.getElementById('clock').innerHTML = thisDay + ', ' + day + ' ' + months[month] + ' ' + year + ' | ' + curr_hour + ":" + curr_minute + ":" + curr_second;
			}
			function checkTime(i) { if (i < 10) i = "0" + i; return i; }
			setInterval(showTime, 500);
		}

		$(document).ready(function() {
			var tvTable = $('#top-volume-table').DataTable({
				"pageLength": 50,
				"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
				"order": [],
				"columnDefs": [
					{ "orderable": false, "targets": 0 }
				],
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

			var trTable = $('#tech-rating-table').DataTable({
				"pageLength": 50,
				"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
				"order": [],
				"columnDefs": [
					{ "orderable": false, "targets": 0 }
				],
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
			collapseEls.forEach(function(el){
				el.addEventListener('shown.bs.collapse', function(e){
					if (e.target && e.target.id === 'collapseTopVolume') {
						tvTable.columns.adjust();
					}
					if (e.target && e.target.id === 'collapseTechRatings') {
						trTable.columns.adjust();
					}
				});
			});
		});
	</script>
</body>

</html>
