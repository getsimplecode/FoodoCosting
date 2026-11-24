@extends('layouts.mainlayout')

@section('title', 'Inventory | ' . config('app.name', 'FoodoCosting'))
@section('page-title', 'Inventory')

@section('content')
    @php
        $formatQuantity = fn (float $quantity): string =>
            rtrim(rtrim(number_format($quantity, 3, '.', ','), '0'), '.');
    @endphp
    <section class="section inventory-page">

        <div class="d-flex flex-wrap gap-1 mb-2 no-print" aria-label="Inventory sections">
            <a href="{{ route('inventory.index', array_filter(['search' => $search])) }}"
               class="btn btn-sm {{ $category === '' && !$report ? 'btn-success' : 'btn-light border' }}">
                All <span class="badge {{ $category === '' && !$report ? 'text-bg-light text-success' : 'text-bg-secondary' }} ms-1">
                    {{ $categoryCounts->sum() }}
                </span>
            </a>
            @foreach ([
                'Ingredients' => 'success',
                'Packaging' => 'primary',
                'Supplies' => 'secondary',
            ] as $section => $color)
                <a href="{{ route('inventory.index', array_filter(['search' => $search, 'category' => $section])) }}"
                   class="btn btn-sm {{ $category === $section ? 'btn-' . $color : 'btn-light border' }}">
                    {{ $section }}
                    <span class="badge {{ $category === $section ? 'text-bg-light' : 'text-bg-secondary' }} ms-1">
                        {{ $categoryCounts->get($section, 0) }}
                    </span>
                </a>
            @endforeach
            <a href="{{ route('inventory.index', ['view' => 'report']) }}"
               class="btn btn-sm {{ $report ? 'btn-dark' : 'btn-light border' }}">
                Report
            </a>
        </div>

        @if ($report)
            @php
                $stockValue = $items->sum(fn ($item) => (float) $item->stock_quantity * $item->unitCost());
                $lowStockCount = $items->filter(fn ($item) => (float) $item->stock_quantity <= (float) $item->reorder_level)->count();
                $damagedQuantity = $reportMovements->where('type', 'damaged')->sum(fn ($movement) => (float) $movement->quantity);
                $reportSections = [
                    'Ingredients' => $items->where('category', 'Ingredients')->values(),
                    'Packaging' => $items->where('category', 'Packaging')->values(),
                    'Supplies' => $items->where('category', 'Supplies')->values(),
                    'Uncategorized' => $items->filter(fn ($item) => blank($item->category))->values(),
                ];
                $reportSections = match ($reportCategory) {
                    'ingredients' => ['Ingredients' => $reportSections['Ingredients']],
                    'packaging' => ['Packaging' => $reportSections['Packaging']],
                    'supplies' => ['Supplies' => $reportSections['Supplies']],
                    'both' => [
                        'Ingredients' => $reportSections['Ingredients'],
                        'Packaging' => $reportSections['Packaging'],
                    ],
                    default => $reportSections,
                };
            @endphp

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2 no-print">
                <div class="d-flex flex-wrap gap-1" aria-label="Report category">
                    @foreach ([
                        'all' => 'All',
                        'ingredients' => 'Ingredients',
                        'packaging' => 'Packaging',
                        'supplies' => 'Supplies',
                        'both' => 'Both',
                    ] as $filterValue => $filterLabel)
                        <a href="{{ route('inventory.index', ['view' => 'report', 'report_category' => $filterValue]) }}"
                           class="btn btn-sm {{ $reportCategory === $filterValue ? 'btn-dark' : 'btn-light border' }}">
                            {{ $filterLabel }}
                            @if ($filterValue === 'both')
                                <span class="small fw-normal ms-1">(Ingredients + Packaging)</span>
                            @endif
                        </a>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-dark" onclick="window.print()">Print / Save PDF</button>
            </div>

            <div class="inventory-print-header d-none text-center border-bottom pb-2 mb-3">
                <h3 class="fw-bold mb-1">FOODO INVENTORY REPORT</h3>
                <div>Date: {{ now()->format('F d, Y') }}</div>
                <div class="small">Generated at {{ now()->format('g:i A') }}</div>
            </div>

            <div class="row g-2 mb-2 no-print">
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm"><div class="card-body py-2"><div class="small text-muted">Inventory Items</div><div class="fw-bold fs-5">{{ $items->count() }}</div></div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm"><div class="card-body py-2"><div class="small text-muted">Low Stock</div><div class="fw-bold fs-5">{{ $lowStockCount }}</div></div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm"><div class="card-body py-2"><div class="small text-muted">Estimated Stock Value</div><div class="fw-bold fs-5">₱{{ number_format($stockValue, 2) }}</div></div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm"><div class="card-body py-2"><div class="small text-muted">Damaged Quantity</div><div class="fw-bold fs-5">{{ $formatQuantity((float) $damagedQuantity) }}</div></div></div>
                </div>
            </div>

            @foreach ($reportSections as $sectionName => $sectionItems)
                @if ($sectionItems->isNotEmpty())
                    @php
                        $sectionStockValue = $sectionItems->sum(fn ($item) => (float) $item->stock_quantity * $item->unitCost());
                    @endphp
                    <div class="card border-0 shadow-sm mb-2">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fs-6">{{ strtoupper($sectionName) }}</h6>
                            <span class="small text-muted">{{ $sectionItems->count() }} items · ₱{{ number_format($sectionStockValue, 2) }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Item</th>
                                        <th class="text-end">Current Stock</th>
                                        <th class="text-end">Reorder Level</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end pe-3">Stock Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sectionItems as $item)
                                        <tr class="{{ (float) $item->stock_quantity <= (float) $item->reorder_level ? 'table-warning' : '' }}">
                                            <td class="fw-semibold ps-3">{{ $item->name }}</td>
                                            <td class="text-end">{{ $formatQuantity((float) $item->stock_quantity) }} {{ $item->usage_unit }}</td>
                                            <td class="text-end">{{ $formatQuantity((float) $item->reorder_level) }} {{ $item->usage_unit }}</td>
                                            <td class="text-end">₱{{ number_format($item->unitCost(), 2) }}</td>
                                            <td class="text-end pe-3">₱{{ number_format((float) $item->stock_quantity * $item->unitCost(), 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2"><h6 class="mb-0 fs-6">Stock Movement Summary</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Item</th>
                                <th>Movement</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end pe-3">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportMovements->take(100) as $movement)
                                <tr>
                                    <td class="ps-3">{{ $movement->occurred_at->format('M d, Y g:i A') }}</td>
                                    <td>{{ $movement->inventoryItem?->name ?? 'Removed item' }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $movement->type) }}</td>
                                    <td class="text-end">{{ $formatQuantity((float) $movement->quantity) }}</td>
                                    <td class="text-end pe-3">{{ $movement->reason ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No stock movements recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @push('styles')
                <style>
                    @media print {
                        .sidebar, .app-sidebar, .topbar, .page-heading, .topbar-user,
                        .menu-toggle, .no-print, .modal, .d-lg-none { display: none !important; }
                        .app-layout, .main-panel, .main-content { display: block !important; width: 100% !important; margin: 0 !important; }
                        header, .topbar, #header, .main-header,
                        .browser-header, .report-browser-header { display: none !important; }
                        .inventory-print-header { display: block !important; margin: 0 0 16px 0; }
                        .inventory-print-header h3 { font-size: 20px; letter-spacing: 1px; }
                        .card { box-shadow: none !important; break-inside: avoid; border: 1px solid #d9d9d9 !important; }
                        .card-header { border-bottom: 1px solid #d9d9d9 !important; }
                        .table { font-size: 11px; }
                        .table-warning { background: #fff !important; }
                        body { background: #fff !important; }
                    }
                    @page { size: portrait; margin: 12mm; }
                </style>
            @endpush
        @else
        <form method="GET" action="{{ route('inventory.index') }}" class="d-flex flex-wrap gap-2 align-items-end mb-2">
            <div class="flex-grow-1" style="min-width:180px;max-width:360px">
                <label for="inventorySearch" class="form-label small text-muted fw-semibold mb-1">Search</label>
                <input id="inventorySearch" type="search" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Item name">
            </div>
            <div style="min-width:170px">
                <label for="inventoryCategory" class="form-label small text-muted fw-semibold mb-1">Category</label>
                <select id="inventoryCategory" name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption }}" @selected($category === $categoryOption)>
                            {{ $categoryOption }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-success">Filter</button>
            @if ($search !== '' || $category !== '')
                <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-light border">Clear</a>
            @endif
            <button type="button" class="btn btn-sm btn-success ms-lg-auto" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                + Add Item
            </button>
        </form>

        <div class="row g-2">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="table-responsive" role="region" aria-label="Scrollable inventory table" tabindex="0"
                         style="max-height:calc(100vh - 330px);min-height:240px;overflow:auto">
                        <table class="table table-sm table-hover align-middle small mb-0">
                    <thead class="table-light" style="position:sticky;top:0;z-index:2">
                        <tr>
                            <th class="ps-3">Ingredient</th>
                            <th>Category</th>
                            <th class="text-end">Purchase</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="{{ (float) $item->stock_quantity <= (float) $item->reorder_level ? 'table-warning' : '' }}">
                                <td class="fw-semibold ps-3">{{ $item->name }}</td>
                                <td>{{ $item->category ?: 'Uncategorized' }}</td>
                                <td class="text-end">
                                    ₱{{ number_format((float) $item->purchase_price, 2) }}
                                    / {{ $formatQuantity((float) $item->purchase_quantity) }} {{ $item->purchase_unit }}
                                </td>
                                <td class="text-end">₱{{ number_format($item->unitCost(), 2) }} / {{ $item->usage_unit }}</td>
                                <td class="text-end">
                                    {{ $formatQuantity((float) $item->stock_quantity) }} {{ $item->usage_unit }}
                                    @if ((float) $item->stock_quantity <= (float) $item->reorder_level)
                                        <span class="badge text-bg-warning ms-1">Low</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2"
                                            data-bs-toggle="modal" data-bs-target="#batchViewModal{{ $item->id }}"
                                            aria-label="View batches for {{ $item->name }}" title="View batches">
                                        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm py-0 px-2"
                                            data-bs-toggle="modal" data-bs-target="#movementModal{{ $item->id }}"
                                            aria-label="Manage stock for {{ $item->name }}" title="Manage stock">
                                        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M12 21v-9m8-4.5-8 4.5-8-4.5M12 7v5m-2.5-2.5h5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2"
                                            data-bs-toggle="modal" data-bs-target="#editInventoryModal{{ $item->id }}"
                                            aria-label="Edit {{ $item->name }}" title="Edit item">
                                        <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="m4 16.5-.8 4.3 4.3-.8L20 7.5 16.5 4 4 16.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="m14.5 6 3.5 3.5M13 21h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('inventory.destroy', $item) }}" class="d-inline"
                                          data-confirm="Remove {{ $item->name }} from inventory?"
                                          data-confirm-title="Delete inventory item"
                                          data-confirm-accept="Delete"
                                          data-confirm-class="btn-danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2"
                                                aria-label="Delete {{ $item->name }}" title="Delete item">
                                            <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No inventory items found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 fs-6">Recent Stock Movements</h6>
                    </div>
                    <div class="table-responsive" style="max-height: 420px;">
                        <table class="table table-sm table-hover align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-2">Item</th>
                                    <th>Type</th>
                                    <th class="text-end pe-2">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movements as $movement)
                                    <tr>
                                        <td class="ps-2">
                                            <div class="fw-semibold">{{ $movement->inventoryItem?->name ?? 'Removed item' }}</div>
                                            <div class="text-muted" style="font-size:.68rem">{{ $movement->occurred_at->format('M d, g:i A') }}</div>
                                        </td>
                                        <td>
                                            @if ($movement->type === 'stock_in')
                                                <span class="badge text-bg-success">In</span>
                                            @elseif ($movement->type === 'stock_out')
                                                <span class="badge text-bg-primary">Out</span>
                                            @else
                                                <span class="badge text-bg-danger">Damaged</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-2">{{ $formatQuantity((float) $movement->quantity) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No stock movements.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </section>

    @if (!$report)
    @include('inventory.partials.add-modal')

    @foreach ($items as $item)
        @include('inventory.partials.edit-modal', ['item' => $item])
        @include('inventory.partials.movement-modal', ['item' => $item])
        @include('inventory.partials.batch-summary-modal', ['item' => $item])
    @endforeach
    @endif
@endsection
