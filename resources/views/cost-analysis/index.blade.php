@extends('layouts.mainlayout')

@section('title', 'Cost Analysis | ' . config('app.name', 'FoodoCosting'))
@section('page-title', 'Cost Analysis')

@section('content')

{{-- =====================================================================
    SAMPLE DATA: every `?? default` below is placeholder data.
    Pass these from your controller and the defaults are ignored:
    $items (id, item_name, quantity, unit_price, line_total), $createdPacks,
    $targetCostPercent, $otherExpenses
    Items can be arrays or Eloquent models.
    The calculations are only for the preview; move them to the
    controller if you prefer.
====================================================================== --}}
@php
    $isSavedReport = $isSavedReport ?? false;
    $autoPrint = $autoPrint ?? false;

    $items = collect($items ?? [
        ['id' => 1, 'item_name' => 'Chicken',     'quantity' => 5,  'unit_price' => 200, 'line_total' => 1000],
        ['id' => 2, 'item_name' => 'Rice (kg)',   'quantity' => 10, 'unit_price' => 60,  'line_total' => 600],
        ['id' => 3, 'item_name' => 'Cooking Oil', 'quantity' => 3,  'unit_price' => 150, 'line_total' => 450],
        ['id' => 4, 'item_name' => 'Spices',      'quantity' => 1,  'unit_price' => 450, 'line_total' => 450],
    ]);

    $createdPacks      = $createdPacks      ?? 24;
    $targetCostPercent = $targetCostPercent ?? 40;
    $otherExpenses     = $otherExpenses     ?? 500;

    // ---- Calculations ----
    $totalCost    = $items->sum(fn ($i) => data_get(
        $i,
        'line_total',
        data_get($i, 'quantity', 0) * data_get($i, 'unit_price', 0),
    ));
    $unitCost     = $createdPacks > 0 ? $totalCost / $createdPacks : 0;
    $sellingPrice = $targetCostPercent > 0 ? $unitCost / ($targetCostPercent / 100) : 0;
    $totalSales   = $sellingPrice * $createdPacks;
    $grossProfit  = $totalSales - $totalCost;
    $netProfit    = $grossProfit - $otherExpenses;
    $grossMargin  = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;
    $margin       = $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0;

    $isProfit    = $netProfit >= 0;
    $profitClass = $isProfit ? 'success' : 'danger';

    // ---- Brand palette: LandBank-inspired green + gold ----
    $hex = [
        'primary'   => '#0b5d3b', // deep green
        'success'   => '#2e9e5b', // fresh green
        'warning'   => '#f2b705', // gold
        'info'      => '#8cc63f', // light green
        'danger'    => '#c0392b', // red (loss only)
        'secondary' => '#9aa89f', // sage grey
    ];
    $itemKeys  = ['primary', 'warning', 'success', 'info', 'secondary', 'danger'];
    $profitHex = $hex[$profitClass];

    // Reusable inline brand styles (so the look does not depend on theme colors)
    $sGreen    = 'background-color:#0b5d3b;border-color:#0b5d3b;color:#fff';
    $sGold     = 'background-color:#f2b705;border-color:#f2b705;color:#1f2a24';
    $sHero     = 'background:linear-gradient(135deg,#083d27 0%,#0b5d3b 55%,#2e9e5b 100%)';
    $sTableHead = 'background-color:#0b5d3b;color:#fff';

    // Each slice = [label, color key, value]
    $slicesUnit = $items->values()->map(fn ($i, $k) => [
        data_get($i, 'item_name'),
        $itemKeys[$k % count($itemKeys)],
        data_get($i, 'line_total', data_get($i, 'quantity', 0) * data_get($i, 'unit_price', 0)),
    ])->all();

    $slicesSell = [
        ['Food Cost',    'warning', $targetCostPercent],
        ['Gross Margin', 'primary', max(0, 100 - $targetCostPercent)],
    ];

    // Builds the JSON the doughnut and gauge charts read from each canvas.
    $pack = fn (array $slices, string $prefix = '₱', string $suffix = '') => [
        'labels' => array_column($slices, 0),
        'values' => array_map(fn ($s) => round(max(0, $s[2]), 2), $slices),
        'colors' => array_map(fn ($s) => $hex[$s[1]], $slices),
        'prefix' => $prefix,
        'suffix' => $suffix,
        'type'   => 'doughnut',
    ];

    $chartUnit  = $pack($slicesUnit);
    $chartSell  = $pack($slicesSell, '', '%');

    $chartComparison = [
        'type' => 'bar',
        'labels' => ['Sales breakdown', 'Gross profit after expenses'],
        'datasets' => [
            [
                'label' => 'Direct costs',
                'data' => [round($totalCost, 2), 0],
                'backgroundColor' => $hex['primary'],
            ],
            [
                'label' => 'Gross profit',
                'data' => [round($grossProfit, 2), 0],
                'backgroundColor' => $hex['success'],
            ],
            [
                'label' => 'Other expenses',
                'data' => [0, round($otherExpenses, 2)],
                'backgroundColor' => $hex['warning'],
            ],
            [
                'label' => $netProfit < 0 ? 'Net loss' : 'Net profit',
                'data' => [0, round($netProfit, 2)],
                'backgroundColor' => $profitHex,
            ],
        ],
    ];

    $gaugeValue = min(100, max(0, $margin));
    $chartGauge = [
        'labels' => ['Net Margin', 'Remaining'],
        'values' => [round($gaugeValue, 2), round(100 - $gaugeValue, 2)],
        'colors' => [$profitHex, '#e9ecef'],
        'prefix' => '',
        'suffix' => '%',
        'type'   => 'gauge',
    ];
@endphp

{{-- No icon font needed. Colored dots are plain Bootstrap utilities. --}}
<section class="section cost-analysis-page {{ $isSavedReport ? 'saved-report-screen' : '' }}">

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2 small border-0 shadow-sm" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($isSavedReport)
        <div class="alert border-0 shadow-sm py-2 small d-flex flex-wrap justify-content-between align-items-center gap-2 border-start border-4 no-print"
             style="background-color:#fff8e1;border-left-color:#f2b705 !important" role="status">
            <span>Viewing saved report: <strong>{{ $savedReport->title }}</strong> · {{ $savedReport->created_at->format('M d, Y g:i A') }}</span>
            <span class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-dark rounded-pill px-2 no-print"
                        aria-label="Print report" title="Print report" onclick="window.print()">
                    <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M7 14h10v7H7zM17.5 11h.01" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                </button>
                <a href="{{ route('cost-analysis.reports.index') }}" class="btn btn-sm btn-light border rounded-pill px-2 no-print"
                   aria-label="Back to saved reports" title="Back to saved reports">
                    <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M19 12H5m7 7-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <a href="{{ route('cost-analysis.index') }}" class="btn btn-sm rounded-pill px-2 no-print" style="{{ $sGreen }}"
                   aria-label="Current analysis" title="Current analysis">
                    <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M8 7h8M8 11h2m4 0h2m-8 4h2m4 0h2m-8 3h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </a>
            </span>
        </div>
    @endif

    {{-- =================================================================
         STEP 1: INPUTS
    ================================================================== --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge rounded-pill" style="{{ $sGold }}">1</span>
        <h6 class="text-uppercase fw-bold small mb-0 text-success-emphasis" style="letter-spacing:.08em">Inputs</h6>
        <hr class="flex-grow-1 my-0 opacity-25">
    </div>

    <div class="row g-2 mb-3 p-1">

        {{-- Bought Items --}}
        <div class="col-xl-8">
            <div class="card h-100 mb-0 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-2">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h6 class="card-title fw-bold mb-0 p-0">Bought Items <span class="text-muted fw-normal">| Direct Costs</span></h6>
                        <div class="d-flex gap-1">
                            @if (!$isSavedReport)
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3"
                                        data-bs-toggle="modal" data-bs-target="#saveReportModal"
                                        aria-label="Save report" title="Save report" @disabled($items->isEmpty())>
                                    <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 3h13l4 4v14H3V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <path d="M7 3v6h10V3M7 21v-8h10v8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('cost-analysis.items.reset') }}"
                                      data-confirm="Clear all items in the current table? Saved reports will not be affected."
                                      data-confirm-title="Reset cost analysis"
                                      data-confirm-accept="Reset"
                                      data-confirm-class="btn-danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                            aria-label="Reset current table" title="Reset current table" @disabled($items->isEmpty())>
                                        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm rounded-pill px-2 fw-semibold shadow-sm" style="{{ $sGold }}"
                                        data-bs-toggle="modal" data-bs-target="#addItemModal"
                                        aria-label="Add cost item" title="Add cost item">
                                    <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-sm table-hover align-middle small mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" class="py-2 ps-3 fw-semibold border-0" style="{{ $sTableHead }}">Item</th>
                                    <th scope="col" class="py-2 text-end fw-semibold border-0" style="{{ $sTableHead }}">Qty</th>
                                    <th scope="col" class="py-2 text-end fw-semibold border-0" style="{{ $sTableHead }}">Price</th>
                                    <th scope="col" class="py-2 text-end fw-semibold border-0 {{ $isSavedReport ? 'pe-3' : '' }}" style="{{ $sTableHead }}">Subtotal</th>
                                    @unless ($isSavedReport)
                                        <th scope="col" class="py-2 pe-3 text-end fw-semibold border-0" style="{{ $sTableHead }}">Actions</th>
                                    @endunless
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td class="fw-semibold ps-3">
                                            <span class="d-inline-block rounded-circle p-1 me-2" style="background-color: {{ $hex[$itemKeys[$loop->index % count($itemKeys)]] }}"></span>{{ data_get($item, 'item_name') }}
                                        </td>
                                        <td class="text-end">{{ data_get($item, 'quantity') }}</td>
                                        <td class="text-end">₱{{ number_format(data_get($item, 'unit_price'), 2) }}</td>
                                        <td class="text-end fw-semibold {{ $isSavedReport ? 'pe-3' : '' }}">₱{{ number_format(data_get($item, 'line_total'), 2) }}</td>
                                        @unless ($isSavedReport)
                                            <td class="text-end text-nowrap pe-3">
                                                <button type="button" class="btn btn-outline-success btn-sm py-0 px-2 rounded-pill"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editItemModal{{ data_get($item, 'id') }}"
                                                        aria-label="Edit {{ data_get($item, 'item_name') }}" title="Edit item">
                                                    <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                        <path d="m4 16.5-.8 4.3 4.3-.8L20 7.5 16.5 4 4 16.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                        <path d="m14.5 6 3.5 3.5M13 21h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                                    </svg>
                                                </button>
                                                <form method="POST" action="{{ route('cost-analysis.items.destroy', data_get($item, 'id')) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2 rounded-pill"
                                                            aria-label="Delete {{ data_get($item, 'item_name') }}" title="Delete item">
                                                        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                            <path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        @endunless
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isSavedReport ? 4 : 5 }}" class="text-center text-muted py-4">
                                            No items recorded yet. Use <strong>+ Add Item</strong> to begin.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background-color:#fff8e1">
                                    <td colspan="3" class="text-end fw-semibold text-uppercase ps-3 border-0">Total</td>
                                    <td class="text-end fw-bold border-0 {{ $isSavedReport ? 'pe-3' : '' }}" style="color:#0b5d3b">₱{{ number_format($totalCost, 2) }}</td>
                                    @unless ($isSavedReport)
                                        <td class="border-0"></td>
                                    @endunless
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Assumptions: the only real form on the page --}}
        <div class="col-xl-4">
            <div class="card h-100 mb-0 border-0 shadow-sm rounded-4 overflow-hidden border-top border-4" style="border-top-color:#f2b705 !important">
                <div class="card-body p-2">
                    <h6 class="card-title fw-bold mb-3 p-0">Assumptions <span class="text-muted fw-normal">| Adjust &amp; Recalculate</span></h6>

                    <form method="POST" action="{{ route('cost-analysis.calculate') }}">
                        @csrf

                        <div class="mb-2">
                            <label for="createdPacks" class="form-label small text-muted fw-semibold mb-1">Created Packs</label>
                            <div class="input-group input-group-sm">
                                <input type="number" min="1" step="1" id="createdPacks" name="created_packs"
                                       value="{{ $createdPacks }}" class="form-control">
                                <span class="input-group-text bg-success-subtle text-success-emphasis border-success-subtle">pcs</span>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="targetCostPercent" class="form-label small text-muted fw-semibold mb-1">Target Cost Percent</label>
                            <div class="input-group input-group-sm">
                                <input type="number" min="1" max="100" step="0.01" id="targetCostPercent"
                                       name="target_cost_percent" value="{{ $targetCostPercent }}" class="form-control">
                                <span class="input-group-text bg-success-subtle text-success-emphasis border-success-subtle">%</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="otherExpenses" class="form-label small text-muted fw-semibold mb-1">Other Expenses</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-success-subtle text-success-emphasis border-success-subtle">₱</span>
                                <input type="number" min="0" step="0.01" id="otherExpenses" name="other_expenses"
                                       value="{{ $otherExpenses }}" class="form-control">
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-sm rounded-pill fw-semibold shadow-sm" style="{{ $sGreen }}"
                                    aria-label="Recalculate cost analysis" title="Recalculate" @disabled($isSavedReport)>
                                <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M20 7v5h-5M4 17v-5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M5.5 9A7 7 0 0 1 18 6l2 2M4 16l2 2a7 7 0 0 0 12.5-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg> Recalculate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- =================================================================
         STEP 2: PRICING
    ================================================================== --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge rounded-pill" style="{{ $sGold }}">2</span>
        <h6 class="text-uppercase fw-bold small mb-0 text-success-emphasis" style="letter-spacing:.08em">Pricing</h6>
        <hr class="flex-grow-1 my-0 opacity-25">
    </div>

    <div class="row g-2 mb-3">

        {{-- Unit Costing --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 mb-0 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-2">
                    <h6 class="card-title fw-bold mb-3 p-0">Unit Costing <span class="text-muted fw-normal">| Per Pack</span></h6>

                    <div class="row g-2 align-items-center">
                        <div class="col-5">
                            <div class="position-relative">
                                <canvas data-chart="{{ json_encode($chartUnit) }}"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle text-center pe-none">
                                    <div class="fw-bold lh-1 small" style="color:{{ $hex['danger'] }}">UNIT COST</div>
                                    <div class="fw-bold">₱{{ number_format($unitCost, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="small text-muted mb-1">₱{{ number_format($totalCost, 2) }} ÷ {{ $createdPacks }} packs</div>
                            @foreach ($slicesUnit as $s)
                                <div class="d-flex justify-content-between small py-1 border-bottom border-light">
                                    <span><span class="d-inline-block rounded-circle p-1 me-2" style="background-color: {{ $hex[$s[1]] }}"></span>{{ $s[0] }}</span>
                                    <span class="fw-semibold">₱{{ number_format($s[2], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Selling Costing --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 mb-0 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-2">
                    <h6 class="card-title fw-bold mb-3 p-0">Selling Costing <span class="text-muted fw-normal">| Per Pack</span></h6>

                    <div class="row g-2 align-items-center">
                        <div class="col-5">
                            <div class="position-relative">
                                <canvas data-chart="{{ json_encode($chartSell) }}"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle text-center pe-none">
                                    <div class="fw-bold lh-1 small" style="color:#0b5d3b">PRICE</div>
                                    <div class="fw-bold">₱{{ number_format($sellingPrice, 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-7">
                            <div class="small text-muted mb-1">₱{{ number_format($unitCost, 2) }} ÷ {{ $targetCostPercent }}%</div>
                            @foreach ($slicesSell as $s)
                                <div class="d-flex justify-content-between small py-1 border-bottom border-light">
                                    <span><span class="d-inline-block rounded-circle p-1 me-2" style="background-color: {{ $hex[$s[1]] }}"></span>{{ $s[0] }}</span>
                                    <span class="fw-semibold">{{ number_format($s[2], 2) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Sales Revenue (hero card) --}}
        <div class="col-md-12 col-xl-4">
            <div class="card h-100 mb-0 border-0 shadow rounded-4 overflow-hidden text-white" style="{{ $sHero }}">
                <div class="card-body p-2">
                    <h6 class="card-title fw-bold mb-3 p-0 text-white">Total Sales Revenue <span class="fw-normal text-white-50">| Projected</span></h6>

                    <div class="fs-2 fw-bold lh-1" style="color:#f2b705">₱{{ number_format($totalSales, 2) }}</div>
                    <div class="small text-white-50 mt-1">₱{{ number_format($sellingPrice, 2) }} per pack</div>

                    <hr class="my-2 border-light opacity-25">
                    <div class="d-flex justify-content-between small py-1">
                        <span class="text-white-50">Selling Price</span><span class="fw-semibold">₱{{ number_format($sellingPrice, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small py-1">
                        <span class="text-white-50">Created Packs</span><span class="fw-semibold">× {{ $createdPacks }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =================================================================
         STEP 3: PROFITABILITY
    ================================================================== --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge rounded-pill" style="{{ $sGold }}">3</span>
        <h6 class="text-uppercase fw-bold small mb-0 text-success-emphasis" style="letter-spacing:.08em">Profitability</h6>
        <hr class="flex-grow-1 my-0 opacity-25">
    </div>

    <div class="row g-2 mb-3">

        {{-- Gross Profit --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 mb-0 border-0 shadow-sm rounded-4 overflow-hidden border-start border-4" style="border-left-color:#f2b705 !important">
                <div class="card-body p-2">
                    <h6 class="card-title fw-bold mb-3 p-0">Gross Profit <span class="text-muted fw-normal">| Revenue − Direct Cost</span></h6>

                    <div class="fs-3 fw-bold lh-1" style="color:#0b5d3b">₱{{ number_format($grossProfit, 2) }}</div>
                    <div class="small text-muted mt-1">{{ number_format($grossMargin, 2) }}% of sales</div>

                    <hr class="my-2 opacity-25">
                    <div class="d-flex justify-content-between small py-1">
                        <span class="text-muted">Total Sales</span><span class="fw-semibold">₱{{ number_format($totalSales, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small py-1">
                        <span class="text-muted">Total Direct Cost</span><span class="fw-semibold">− ₱{{ number_format($totalCost, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Net Profit --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 mb-0 border-0 shadow-sm rounded-4 overflow-hidden border-start border-4" style="border-left-color:{{ $profitHex }} !important">
                <div class="card-body p-2">
                    <h6 class="card-title fw-bold mb-3 p-0">Net Profit <span class="text-muted fw-normal">| Gross − Other Expenses</span></h6>

                    <div class="fs-3 fw-bold lh-1" style="color:{{ $profitHex }}">₱{{ number_format($netProfit, 2) }}</div>
                    <div class="small text-muted mt-1">{{ number_format($margin, 2) }}% of sales</div>

                    <hr class="my-2 opacity-25">
                    <div class="d-flex justify-content-between small py-1">
                        <span class="text-muted">Gross Profit</span><span class="fw-semibold">₱{{ number_format($grossProfit, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small py-1">
                        <span class="text-muted">Other Expenses</span><span class="fw-semibold">− ₱{{ number_format($otherExpenses, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Profit Margin gauge --}}
        <div class="col-md-12 col-xl-4">
            <div class="card h-100 mb-0 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-2">
                    <h6 class="card-title fw-bold mb-3 p-0">Profit Margin <span class="text-muted fw-normal">| Net ÷ Sales</span></h6>

                    <div class="position-relative mx-auto w-50 mt-2">
                        <canvas data-chart="{{ json_encode($chartGauge) }}"></canvas>
                        <div class="position-absolute bottom-0 start-50 translate-middle-x text-center pe-none">
                            <div class="fs-5 fw-bold lh-1" style="color:{{ $profitHex }}">{{ number_format($margin, 2) }}%</div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <span class="badge rounded-pill px-3 py-2 {{ $isProfit ? '' : 'text-bg-danger' }}"
                              @if ($isProfit) style="{{ $sGold }}" @endif>
                            {{ $isProfit ? 'Profitable' : 'Loss' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =================================================================
         STEP 4: PROFIT MARGIN REPORT
    ================================================================== --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge rounded-pill" style="{{ $sGold }}">4</span>
        <h6 class="text-uppercase fw-bold small mb-0 text-success-emphasis" style="letter-spacing:.08em">Profit Margin Report</h6>
        <hr class="flex-grow-1 my-0 opacity-25">
    </div>

    <div class="row g-2">

        {{-- Comparison chart --}}
        <div class="col-xl-8">
            <div class="card h-100 mb-0 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-2">
                    <h6 class="card-title fw-bold mb-3 p-0">Revenue &amp; Profit Comparison <span class="text-muted fw-normal">| Stacked Breakdown</span></h6>

                    <div class="position-relative" style="height: 240px">
                        <canvas data-chart="{{ json_encode($chartComparison) }}"></canvas>
                    </div>

                    <p class="small text-muted mb-0 mt-2">
                        The first bar shows direct costs plus gross profit. The second shows other expenses and net profit; a loss extends left of zero.
                    </p>
                </div>
            </div>
        </div>

        {{-- Statement summary --}}
        <div class="col-xl-4">
            <div class="card h-100 mb-0 border-0 shadow-sm rounded-4 overflow-hidden border-top border-4" style="border-top-color:#0b5d3b !important">
                <div class="card-body p-2">
                    <h6 class="card-title fw-bold mb-3 p-0">Summary Statement <span class="text-muted fw-normal">{{ $isSavedReport ? '| Saved Report' : '| This Batch' }}</span></h6>

                    <table class="table table-sm align-middle small mb-3">
                        <tbody>
                            <tr>
                                <td>Total Sales</td>
                                <td class="text-end fw-semibold">₱{{ number_format($totalSales, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Less: Direct Cost</td>
                                <td class="text-end text-muted">(₱{{ number_format($totalCost, 2) }})</td>
                            </tr>
                            <tr style="background-color:#e8f5ee">
                                <td class="fw-semibold">Gross Profit</td>
                                <td class="text-end fw-semibold">₱{{ number_format($grossProfit, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Less: Other Expenses</td>
                                <td class="text-end text-muted">(₱{{ number_format($otherExpenses, 2) }})</td>
                            </tr>
                            <tr style="background-color:{{ $isProfit ? '#fff8e1' : '#fdecea' }}">
                                <td class="fw-bold">Net Profit</td>
                                <td class="text-end fw-bold">₱{{ number_format($netProfit, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Net Margin</td>
                                <td class="text-end fw-bold" style="color:{{ $profitHex }}">{{ number_format($margin, 2) }}%</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert py-2 mb-0 small border-0 border-start border-4 {{ $isProfit ? '' : 'alert-danger' }}"
                         @if ($isProfit) style="background-color:#fff8e1;border-left-color:#f2b705 !important" @endif role="alert">
                        @if ($isProfit)
                            This batch earns <strong>₱{{ number_format($netProfit, 2) }}</strong>
                            on <strong>₱{{ number_format($totalSales, 2) }}</strong> in sales.
                        @else
                            This batch loses <strong>₱{{ number_format(abs($netProfit), 2) }}</strong>.
                            Consider lowering the target cost percent or reducing expenses.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

@if ($isSavedReport)
    <article class="saved-report-print-content">
        <header class="mb-4 pb-3 border-bottom">
            <h1 class="h3 fw-bold mb-1">FOODO COSTING</h1>
            <div class="fs-5 fw-semibold">Saved Cost Analysis</div>
            <div class="text-muted">{{ $savedReport->title }}</div>
            <div class="small text-muted">Saved {{ $savedReport->created_at->format('M d, Y g:i A') }}</div>
        </header>

        <h2 class="h5 fw-bold mb-2">Direct Cost Items</h2>
        <table class="saved-report-print-table mb-4">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ data_get($item, 'item_name') }}</td>
                        <td class="text-end">{{ data_get($item, 'quantity') }}</td>
                        <td class="text-end">₱{{ number_format(data_get($item, 'unit_price'), 2) }}</td>
                        <td class="text-end">₱{{ number_format(data_get($item, 'line_total'), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total Direct Cost</th>
                    <th class="text-end">₱{{ number_format($totalCost, 2) }}</th>
                </tr>
            </tfoot>
        </table>

        <h2 class="h5 fw-bold mb-2">Assumptions</h2>
        <table class="saved-report-print-table mb-4">
            <tbody>
                <tr>
                    <th>Created Packs</th>
                    <td>{{ $createdPacks }}</td>
                    <th>Target Cost</th>
                    <td>{{ number_format($targetCostPercent, 2) }}%</td>
                    <th>Other Expenses</th>
                    <td>₱{{ number_format($otherExpenses, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <h2 class="h5 fw-bold mb-2">Pricing &amp; Profit Summary</h2>
        <table class="saved-report-print-table">
            <tbody>
                <tr>
                    <th>Unit Cost per Pack</th>
                    <td class="text-end">₱{{ number_format($unitCost, 2) }}</td>
                    <th>Selling Price per Pack</th>
                    <td class="text-end">₱{{ number_format($sellingPrice, 2) }}</td>
                </tr>
                <tr>
                    <th>Projected Sales</th>
                    <td class="text-end">₱{{ number_format($totalSales, 2) }}</td>
                    <th>Gross Profit</th>
                    <td class="text-end">₱{{ number_format($grossProfit, 2) }} ({{ number_format($grossMargin, 2) }}%)</td>
                </tr>
                <tr>
                    <th>Net Profit</th>
                    <td class="text-end">₱{{ number_format($netProfit, 2) }}</td>
                    <th>Net Margin</th>
                    <td class="text-end">{{ number_format($margin, 2) }}%</td>
                </tr>
            </tbody>
        </table>
    </article>
@endif

@if (!$isSavedReport)
{{-- ===================== ADD ITEM MODAL ===================== --}}
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered cost-item-modal-dialog">
        <form method="POST" action="{{ route('cost-analysis.items.store') }}" class="modal-content cost-item-modal"
              @if ($errors->has('item_name') || $errors->has('quantity') || $errors->has('unit_price')) data-auto-show="true" @endif>
            @csrf
            <div class="modal-header cost-item-modal-header" style="{{ $sHero }}">
                <span class="cost-item-modal-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 5v14m-7-7h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <div class="cost-item-modal-heading">
                    <h5 class="modal-title" id="addItemModalLabel">Add cost item</h5>
                    <p>Add an inventory ingredient or enter a custom item.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body cost-item-modal-body">
                <div class="cost-item-inventory-prompt">
                    <div class="cost-item-inventory-copy">
                        <span class="cost-item-inventory-mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 8h16l-1.2 11H5.2L4 8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                <path d="M8 8a4 4 0 0 1 8 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>
                            <strong>Use an inventory ingredient</strong>
                            <small>Search your stock list and select an ingredient.</small>
                        </span>
                    </div>
                    <a href="{{ route('cost-analysis.ingredients') }}" class="btn cost-item-browse-button"
                       aria-label="Browse inventory ingredients" title="Browse inventory ingredients">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none">
                            <circle cx="10.5" cy="10.5" r="6.5" stroke="currentColor" stroke-width="1.8"/>
                            <path d="m15.5 15.5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <span>Browse</span>
                    </a>
                </div>

                <div class="cost-item-divider"><span>OR ADD A CUSTOM ITEM</span></div>

                <div class="mb-3">
                    <label for="addName" class="form-label">Item name <span class="text-danger">*</span></label>
                    <input type="text" id="addName" name="item_name"
                           class="form-control @error('item_name') is-invalid @enderror"
                           value="{{ old('item_name') }}" placeholder="e.g. Chicken breast"
                           maxlength="255" required>
                    @error('item_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 cost-item-fields">
                    <div class="col-12 col-sm-6">
                        <label for="addQty" class="form-label">Quantity <span id="addUsageUnit" class="text-muted fw-normal"></span> <span class="text-danger">*</span></label>
                        <input type="number" min="0.001" step="0.001" id="addQty" name="quantity"
                               class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity') }}" placeholder="e.g. 2.5" required>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="addPrice" class="form-label">Unit price <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text cost-item-currency">₱</span>
                            <input type="number" min="0" step="0.01" id="addPrice" name="unit_price"
                                   class="form-control @error('unit_price') is-invalid @enderror"
                                   value="{{ old('unit_price') }}" placeholder="0.00" required>
                            @error('unit_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer cost-item-modal-footer">
                <button type="button" class="btn btn-light border cost-item-icon-button" data-bs-dismiss="modal"
                        aria-label="Cancel" title="Cancel">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none">
                        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
                <button type="submit" class="btn cost-item-icon-button cost-item-submit-button" style="{{ $sGold }}"
                        aria-label="Save cost item" title="Save cost item">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none">
                        <path d="M4 3h13l4 4v14H3V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M7 3v6h10V3M7 21v-8h10v8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== SAVE REPORT MODAL ===================== --}}
<div class="modal fade" id="saveReportModal" tabindex="-1" aria-labelledby="saveReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('cost-analysis.reports.store') }}" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            @csrf
            <div class="modal-header py-2 px-3 border-0" style="{{ $sHero }}">
                <h5 class="modal-title fs-6 text-white" id="saveReportModalLabel">Save Analysis Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <label for="reportTitle" class="form-label small fw-semibold">Report Name</label>
                <input type="text" id="reportTitle" name="title" class="form-control form-control-sm"
                       maxlength="255" value="{{ old('title', 'Cost Analysis ' . now()->format('M d, Y g:i A')) }}" required>
                <div class="form-text">
                    The current items and assumptions will be saved as a separate report.
                    Matching inventory items will be deducted using first-expired, first-out.
                </div>
            </div>
            <div class="modal-footer py-2 px-3 border-0">
                <button type="button" class="btn btn-light border btn-sm rounded-pill px-2" data-bs-dismiss="modal"
                        aria-label="Cancel" title="Cancel">
                    <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
                <button type="submit" class="btn btn-sm rounded-pill px-2 fw-semibold" style="{{ $sGreen }}"
                        aria-label="Save report" title="Save report">
                    <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M4 3h13l4 4v14H3V4a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M7 3v6h10V3M7 21v-8h10v8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== EDIT ITEM MODALS (one per row, server-rendered) ===================== --}}
@foreach ($items as $item)
    @php $id = data_get($item, 'id'); @endphp
    <div class="modal fade" id="editItemModal{{ $id }}" tabindex="-1" aria-labelledby="editItemLabel{{ $id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('cost-analysis.items.update', $id) }}" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                @csrf
                @method('PUT')
                <div class="modal-header py-2 px-3 border-0" style="{{ $sHero }}">
                    <h5 class="modal-title fs-6 text-white" id="editItemLabel{{ $id }}">Edit Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-2">
                    <div class="mb-2">
                        <label for="editName{{ $id }}" class="form-label small fw-semibold">Item Name</label>
                        <input type="text" id="editName{{ $id }}" name="item_name" value="{{ data_get($item, 'item_name') }}" class="form-control form-control-sm" maxlength="255" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="editQty{{ $id }}" class="form-label small fw-semibold">Quantity</label>
                            <input type="number" min="0.001" step="0.001" id="editQty{{ $id }}" name="quantity" value="{{ data_get($item, 'quantity') }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label for="editPrice{{ $id }}" class="form-label small fw-semibold">Unit Price</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-success-subtle text-success-emphasis border-success-subtle">₱</span>
                                <input type="number" min="0" step="0.01" id="editPrice{{ $id }}" name="unit_price" value="{{ data_get($item, 'unit_price') }}" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 border-0">
                    <button type="button" class="btn btn-light border btn-sm rounded-pill px-2" data-bs-dismiss="modal"
                            aria-label="Cancel" title="Cancel">
                        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <button type="submit" class="btn btn-sm rounded-pill px-2 fw-semibold" style="{{ $sGold }}"
                            aria-label="Update cost item" title="Update cost item">
                        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endif

@vite('resources/js/cost-analysis.js')

@endsection

@if ($isSavedReport)
    @push('styles')
        <style>
            .saved-report-print-content { display: none; }
            @media print {
                .app-sidebar, .topbar, .no-print { display: none !important; }
                .app-layout { display: block !important; min-height: 0 !important; }
                .main-panel { width: 100% !important; margin: 0 !important; }
                .page-content { padding: 0 !important; margin: 0 !important; }
                .saved-report-screen { display: none !important; }
                .saved-report-print-content {
                    display: block !important;
                    color: #1f2937;
                    font: 10pt/1.4 Arial, sans-serif;
                }
                .saved-report-print-table { width: 100%; border-collapse: collapse; }
                .saved-report-print-table th,
                .saved-report-print-table td {
                    border: 1px solid #cbd5e1;
                    padding: 6px 8px;
                }
                .saved-report-print-table thead th,
                .saved-report-print-table tfoot th { background: #f1f5f9 !important; }
                body { background: #fff !important; color: #1f2937 !important; }
            }
            @page { size: portrait; margin: 12mm; }
        </style>
    @endpush
    @push('scripts')
        @if ($autoPrint)
            <script>
                window.addEventListener('load', function () {
                    window.print();
                });
            </script>
        @endif
    @endpush
@endif