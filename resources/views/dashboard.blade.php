@extends('layouts.mainlayout')

@section('title', 'Dashboard | ' . config('app.name', 'FoodoCosting'))
@section('page-title', 'Dashboard')

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $formatQuantity = fn (float $quantity): string =>
            rtrim(rtrim(number_format($quantity, 3, '.', ','), '0'), '.');
    @endphp

    <section class="dashboard-page">
        <div class="dashboard-hero mb-3">
            <div class="dashboard-hero-copy">
                <span class="dashboard-eyebrow">YOUR KITCHEN OVERVIEW</span>
                <h2>{{ $greeting }}, {{ auth()->user()->name }}</h2>
                <p>Here’s what’s happening with your inventory and costing today.</p>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('inventory.index') }}" class="btn btn-light btn-sm fw-semibold">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <path d="M4 7.5h16M6.5 4.5h11A1.5 1.5 0 0 1 19 6v14H5V6a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="M8 11h8m-8 3.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                        Open Inventory
                    </a>
                    <a href="{{ route('cost-analysis.index') }}" class="btn btn-outline-light btn-sm fw-semibold">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <path d="M12 3.5v17m5-13H9.5a3 3 0 0 0 0 6H14a3 3 0 0 1 0 6H6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                        Open Sales Calculator
                    </a>
                </div>
            </div>
            <div class="dashboard-hero-art" aria-hidden="true">
                <span class="dashboard-hero-orbit dashboard-hero-orbit-one"></span>
                <span class="dashboard-hero-orbit dashboard-hero-orbit-two"></span>
                <svg viewBox="0 0 100 100" fill="none">
                    <circle cx="50" cy="50" r="32" stroke="currentColor" stroke-width="2"/>
                    <circle cx="50" cy="50" r="24" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M15 20v24m-6-24v11m12-11v11m-6 13v31m70-55v55m0-55c-6 7-9 15-9 23h9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <div class="row g-3 mb-3">
            @foreach ([
                ['label' => 'Inventory Items', 'value' => $totalItems, 'hint' => 'Items you track', 'tone' => 'green', 'icon' => 'inventory'],
                ['label' => 'Ingredients', 'value' => $ingredientCount, 'hint' => 'Ready for costing', 'tone' => 'gold', 'icon' => 'ingredients'],
                ['label' => 'Low Stock', 'value' => $lowStockCount, 'hint' => 'Need a restock check', 'tone' => 'orange', 'icon' => 'warning'],
                ['label' => 'Saved Reports', 'value' => $reportCount, 'hint' => 'Completed analyses', 'tone' => 'blue', 'icon' => 'reports'],
            ] as $stat)
                <div class="col-6 col-xl-3">
                    <div class="dashboard-stat-card h-100">
                        <div class="dashboard-stat-icon dashboard-stat-icon-{{ $stat['tone'] }}">
                            @if ($stat['icon'] === 'inventory')
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <path d="m3.5 7 8.5-4 8.5 4v10L12 21l-8.5-4V7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="m3.5 7 8.5 4 8.5-4M12 11v10" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                </svg>
                            @elseif ($stat['icon'] === 'ingredients')
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 12a8 8 0 1 0 16 0H4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="M7 8.5c1.3-1.4 3-2 5-2s3.7.6 5 2M8 16h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                            @elseif ($stat['icon'] === 'warning')
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <path d="m12 3 9 16H3l9-16Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="M12 9v4m0 3h.01" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                                </svg>
                            @else
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <path d="M6 3.5h8l4 4v13H6a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="M14 3.5v5h5M8 12h8m-8 3.5h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @endif
                        </div>
                        <div class="dashboard-stat-label">{{ $stat['label'] }}</div>
                        <div class="dashboard-stat-value">{{ number_format($stat['value']) }}</div>
                        <div class="dashboard-stat-hint">{{ $stat['hint'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="dashboard-panel dashboard-chart-panel h-100">
                    <div class="dashboard-panel-heading">
                        <div>
                            <h3>Inventory activity</h3>
                            <p>Stock movement count over the last 7 days</p>
                        </div>
                    </div>
                    <div class="dashboard-line-chart">
                        <canvas data-dashboard-chart="line" data-chart="{{ json_encode($movementChart) }}"
                                role="img" aria-label="Line chart showing daily stock in, stock out, and damaged movement counts"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="dashboard-panel dashboard-chart-panel h-100">
                    <div class="dashboard-panel-heading">
                        <div>
                            <h3>Inventory mix</h3>
                            <p>Items by category</p>
                        </div>
                    </div>
                    <div class="dashboard-doughnut-chart">
                        <canvas data-dashboard-chart="doughnut" data-chart="{{ json_encode($categoryChart) }}"
                                role="img" aria-label="Doughnut chart showing inventory item counts by category"></canvas>
                        <div class="dashboard-doughnut-center" aria-hidden="true">
                            <strong>{{ $totalItems }}</strong>
                            <span>{{ $totalItems === 1 ? 'item' : 'items' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-6">
                <div class="dashboard-panel h-100">
                    <div class="dashboard-panel-heading">
                        <div>
                            <h3>Stock needs attention</h3>
                            <p>Items at or below their reorder level</p>
                        </div>
                        <a href="{{ route('inventory.index') }}" class="dashboard-panel-link">Inventory <span aria-hidden="true">→</span></a>
                    </div>
                    @forelse ($lowStockItems as $item)
                        <div class="dashboard-list-row">
                            <div class="dashboard-list-main">
                                <span class="dashboard-list-dot dashboard-list-dot-warning"></span>
                                <div>
                                    <div class="dashboard-list-title">{{ $item->name }}</div>
                                    <div class="dashboard-list-subtitle">{{ $item->category ?: 'Uncategorized' }}</div>
                                </div>
                            </div>
                            <div class="dashboard-stock-value">
                                <strong>{{ $formatQuantity((float) $item->stock_quantity) }}</strong>
                                <span>{{ $item->usage_unit }} / {{ $formatQuantity((float) $item->reorder_level) }} reorder</span>
                            </div>
                        </div>
                    @empty
                        <div class="dashboard-empty-state">
                            <span class="dashboard-empty-check" aria-hidden="true">✓</span>
                            <div>
                                <strong>Stock levels look good</strong>
                                <p>No items are currently at or below their reorder level.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="col-xl-6">
                <div class="dashboard-panel h-100">
                    <div class="dashboard-panel-heading">
                        <div>
                            <h3>Expiring soon</h3>
                            <p>{{ $expiringBatchCount }} batch{{ $expiringBatchCount === 1 ? '' : 'es' }} expiring within 30 days or already expired</p>
                        </div>
                        <a href="{{ route('inventory.index') }}" class="dashboard-panel-link">Check stock <span aria-hidden="true">→</span></a>
                    </div>
                    @forelse ($expiringBatches as $batch)
                        @php $isExpired = $batch->expiration_date->lt(today()); @endphp
                        <div class="dashboard-list-row">
                            <div class="dashboard-list-main">
                                <span class="dashboard-list-dot {{ $isExpired ? 'dashboard-list-dot-danger' : 'dashboard-list-dot-gold' }}"></span>
                                <div>
                                    <div class="dashboard-list-title">{{ $batch->inventoryItem?->name ?? 'Removed item' }}</div>
                                    <div class="dashboard-list-subtitle">
                                        {{ $formatQuantity((float) $batch->quantity) }}
                                        {{ $batch->inventoryItem?->usage_unit }}
                                        @if ($batch->batch_number)
                                            · {{ $batch->batch_number }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge {{ $isExpired ? 'text-bg-danger' : 'text-bg-warning' }}">
                                    {{ $isExpired ? 'Expired' : $batch->expiration_date->format('M d') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="dashboard-empty-state">
                            <span class="dashboard-empty-check" aria-hidden="true">✓</span>
                            <div>
                                <strong>No upcoming expirations</strong>
                                <p>No in-stock batches expire within the next 30 days.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="dashboard-panel h-100">
                    <div class="dashboard-panel-heading">
                        <div>
                            <h3>Recent stock activity</h3>
                            <p>Your latest inventory movements</p>
                        </div>
                        <a href="{{ route('inventory.index') }}" class="dashboard-panel-link">View inventory <span aria-hidden="true">→</span></a>
                    </div>
                    @forelse ($recentMovements as $movement)
                        @php
                            $movementLabel = match ($movement->type) {
                                'stock_in' => 'Stock in',
                                'stock_out' => 'Stock out',
                                'damaged' => 'Damaged',
                                default => str_replace('_', ' ', $movement->type),
                            };
                        @endphp
                        <div class="dashboard-list-row">
                            <div class="dashboard-list-main">
                                <span class="dashboard-movement-badge dashboard-movement-{{ $movement->type }}">
                                    {{ $movement->type === 'stock_in' ? '+' : '−' }}
                                </span>
                                <div>
                                    <div class="dashboard-list-title">{{ $movement->inventoryItem?->name ?? 'Removed item' }}</div>
                                    <div class="dashboard-list-subtitle">{{ $movement->occurred_at->format('M d, Y · g:i A') }} · {{ $movementLabel }}</div>
                                </div>
                            </div>
                            <strong class="dashboard-movement-quantity">
                                {{ $formatQuantity((float) $movement->quantity) }}
                            </strong>
                        </div>
                    @empty
                        <div class="dashboard-empty-state">
                            <div>
                                <strong>No stock activity yet</strong>
                                <p>Inventory changes will appear here.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="col-xl-5">
                <div class="dashboard-panel h-100">
                    <div class="dashboard-panel-heading">
                        <div>
                            <h3>Saved reports</h3>
                            <p>Your latest cost analyses</p>
                        </div>
                        <a href="{{ route('cost-analysis.reports.index') }}" class="dashboard-panel-link">All reports <span aria-hidden="true">→</span></a>
                    </div>
                    @forelse ($recentReports as $report)
                        <a href="{{ route('cost-analysis.reports.show', $report) }}" class="dashboard-report-row">
                            <span class="dashboard-report-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M6 3.5h8l4 4v13H6a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="M14 3.5v5h5M8 12h8m-8 3.5h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="dashboard-report-copy">
                                <strong>{{ $report->title }}</strong>
                                <small>{{ $report->created_at->format('M d, Y') }} · {{ count($report->snapshot['items'] ?? []) }} items</small>
                            </span>
                            <span class="dashboard-report-total">
                                ₱{{ number_format(collect($report->snapshot['items'] ?? [])->sum('line_total'), 2) }}
                            </span>
                        </a>
                    @empty
                        <div class="dashboard-empty-state">
                            <div>
                                <strong>No saved reports yet</strong>
                                <p>Save a cost analysis to keep a report here.</p>
                                <a href="{{ route('cost-analysis.index') }}" class="btn btn-sm btn-outline-success mt-1">Start a cost analysis</a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .dashboard-page { --dash-green:#0b5d3b; --dash-green-dark:#073d29; --dash-mint:#dff8eb; --dash-gold:#f2b705; }
        .dashboard-hero {
            position:relative; overflow:hidden; min-height:190px; padding:24px 28px;
            border-radius:20px; color:#fff;
            background:linear-gradient(120deg,#06452d 0%,#0b5d3b 52%,#218550 100%);
            box-shadow:0 12px 28px rgba(11,93,59,.14);
        }
        .dashboard-hero-copy { position:relative; z-index:1; max-width:620px; }
        .dashboard-eyebrow { display:inline-block; padding:5px 8px; color:#f4cd4a; background:rgba(242,183,5,.12); border:1px solid rgba(242,183,5,.24); border-radius:999px; font-size:.64rem; font-weight:750; letter-spacing:.12em; }
        .dashboard-hero h2 { margin:8px 0 5px; color:#fff; font-size:clamp(1.45rem,2.4vw,2rem); font-weight:750; }
        .dashboard-hero p { margin:0; color:rgba(255,255,255,.78); }
        .dashboard-hero .btn svg { width:17px; height:17px; margin-right:5px; vertical-align:-3px; }
        .dashboard-hero .btn-light { color:#0b5d3b; background:#fff; border-color:#fff; box-shadow:0 4px 12px rgba(11,93,59,.08); }
        .dashboard-hero .btn-light:hover { color:#073d29; background:#f7fffa; border-color:#f7fffa; }
        .dashboard-hero-art { position:absolute; right:6%; top:50%; width:150px; height:150px; color:rgba(255,255,255,.28); transform:translateY(-50%); }
        .dashboard-hero-art svg { position:relative; z-index:1; width:100%; height:100%; padding:28px; }
        .dashboard-hero-orbit { position:absolute; inset:0; border:1px solid rgba(255,255,255,.14); border-radius:50%; }
        .dashboard-hero-orbit-two { inset:14px; }
        .dashboard-stat-card,.dashboard-panel { background:#fff; border:1px solid #e8eee9; border-radius:15px; box-shadow:0 4px 16px rgba(19,45,31,.035); }
        .dashboard-stat-card { position:relative; min-height:140px; padding:17px; }
        .dashboard-stat-icon { display:grid; width:38px; height:38px; place-items:center; float:right; border-radius:12px; }
        .dashboard-stat-icon svg { width:20px; height:20px; }
        .dashboard-stat-icon-green { color:#0b7250; background:#e7f5ed; }
        .dashboard-stat-icon-gold { color:#967000; background:#fff6d8; }
        .dashboard-stat-icon-orange { color:#b45309; background:#fff0e0; }
        .dashboard-stat-icon-blue { color:#315ca8; background:#edf2ff; }
        .dashboard-stat-label { margin-top:3px; color:#68756d; font-size:.8rem; font-weight:600; }
        .dashboard-stat-value { margin-top:5px; color:#183528; font-size:1.75rem; line-height:1.15; font-weight:750; }
        .dashboard-stat-hint { margin-top:4px; color:#87928b; font-size:.72rem; }
        .dashboard-panel { padding:17px 18px; }
        .dashboard-panel-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:9px; }
        .dashboard-panel-heading h3 { margin:0; color:#183528; font-size:.98rem; font-weight:700; }
        .dashboard-panel-heading p { margin:3px 0 0; color:#89948d; font-size:.73rem; }
        .dashboard-panel-link { flex:0 0 auto; color:var(--dash-green); font-size:.75rem; font-weight:700; text-decoration:none; }
        .dashboard-panel-link:hover { color:#073d29; }
        .dashboard-list-row { display:flex; justify-content:space-between; align-items:center; gap:12px; min-height:56px; padding:9px 0; border-top:1px solid #f0f3f1; }
        .dashboard-list-main { display:flex; align-items:center; min-width:0; gap:10px; }
        .dashboard-list-dot { flex:0 0 auto; width:9px; height:9px; border-radius:50%; background:#ef9a34; }
        .dashboard-list-dot-warning { background:#e99a2d; }
        .dashboard-list-dot-danger { background:#d94b4b; }
        .dashboard-list-dot-gold { background:#d7a900; }
        .dashboard-list-title { overflow:hidden; color:#263a2f; font-size:.82rem; font-weight:650; text-overflow:ellipsis; white-space:nowrap; }
        .dashboard-list-subtitle { margin-top:2px; color:#89948d; font-size:.7rem; }
        .dashboard-stock-value { flex:0 0 auto; text-align:right; }
        .dashboard-stock-value strong,.dashboard-movement-quantity { color:#263a2f; font-size:.8rem; }
        .dashboard-stock-value span { display:block; color:#89948d; font-size:.67rem; }
        .dashboard-empty-state { display:flex; align-items:center; gap:11px; min-height:100px; color:#68756d; }
        .dashboard-empty-state strong { color:#263a2f; font-size:.82rem; }
        .dashboard-empty-state p { margin:3px 0 0; color:#89948d; font-size:.72rem; }
        .dashboard-empty-check { display:grid; width:32px; height:32px; place-items:center; border-radius:50%; color:#0b7250; background:#e7f5ed; font-weight:700; }
        .dashboard-movement-badge { display:grid; flex:0 0 auto; width:30px; height:30px; place-items:center; border-radius:10px; background:#e7f5ed; color:#0b7250; font-weight:700; }
        .dashboard-movement-stock_out { color:#385fa3; background:#edf2ff; }
        .dashboard-movement-damaged { color:#bd4040; background:#fff0ef; }
        .dashboard-movement-quantity { flex:0 0 auto; }
        .dashboard-report-row { display:flex; align-items:center; gap:10px; padding:11px 0; border-top:1px solid #f0f3f1; color:inherit; text-decoration:none; }
        .dashboard-report-row:hover .dashboard-report-copy strong { color:var(--dash-green); }
        .dashboard-report-icon { display:grid; flex:0 0 auto; width:34px; height:34px; place-items:center; border-radius:10px; color:#0b7250; background:#e7f5ed; }
        .dashboard-report-icon svg { width:18px; height:18px; }
        .dashboard-report-copy { display:grid; min-width:0; flex:1; gap:3px; }
        .dashboard-report-copy strong { overflow:hidden; color:#263a2f; font-size:.78rem; text-overflow:ellipsis; white-space:nowrap; }
        .dashboard-report-copy small { color:#89948d; font-size:.68rem; }
        .dashboard-report-total { flex:0 0 auto; color:#263a2f; font-size:.76rem; font-weight:700; }
        .dashboard-chart-panel { min-height:310px; }
        .dashboard-line-chart { position:relative; height:245px; }
        .dashboard-doughnut-chart { position:relative; width:100%; max-width:280px; height:245px; margin:0 auto; }
        .dashboard-doughnut-center { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none; }
        .dashboard-doughnut-center strong { color:#183528; font-size:1.55rem; line-height:1; }
        .dashboard-doughnut-center span { margin-top:4px; color:#89948d; font-size:.7rem; }
        @media (max-width: 575.98px) {
            .dashboard-hero { padding:20px; }
            .dashboard-hero-art { right:-42px; opacity:.5; }
            .dashboard-stat-card { min-height:130px; padding:13px; }
            .dashboard-stat-value { font-size:1.5rem; }
            .dashboard-panel { padding:14px; }
        }
    </style>
@endpush

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
