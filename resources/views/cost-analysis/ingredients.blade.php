@extends('layouts.mainlayout')

@section('title', 'Choose Ingredient | ' . config('app.name', 'FoodoCosting'))
@section('page-title', 'Choose Ingredient')

@section('content')
    @php
        $formatQuantity = fn (float $quantity): string =>
            rtrim(rtrim(number_format($quantity, 3, '.', ','), '0'), '.');
    @endphp

    <section class="section cost-analysis-ingredients-page">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="mb-1">Inventory Ingredients</h5>
                <p class="text-muted small mb-0">Search your inventory and add an ingredient to the current cost analysis.</p>
            </div>
            <a href="{{ route('cost-analysis.index') }}" class="btn btn-sm btn-outline-success">
                Back to Sales Calculator
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('cost-analysis.ingredients') }}" class="row g-2 align-items-end mb-3">
            <div class="col-sm-8 col-lg-6">
                <label for="ingredientSearch" class="form-label small fw-semibold mb-1">Search ingredients</label>
                <input type="search" id="ingredientSearch" name="search" value="{{ $search }}"
                       class="form-control form-control-sm" placeholder="Ingredient name">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-success">Search</button>
                @if ($search !== '')
                    <a href="{{ route('cost-analysis.ingredients') }}" class="btn btn-sm btn-light border">Clear</a>
                @endif
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 small text-muted">
                @if ($ingredients->isNotEmpty())
                    {{ $ingredients->count() }} ingredients
                @else
                    No ingredients found
                @endif
            </div>
            <div class="table-responsive" role="region" aria-label="Scrollable ingredient list" tabindex="0"
                 style="max-height:calc(100vh - 330px);min-height:240px;overflow:auto">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light" style="position:sticky;top:0;z-index:2">
                        <tr>
                            <th class="ps-3">Ingredient</th>
                            <th class="text-end">Available Stock</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end pe-3">Add Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ingredients as $ingredient)
                            @php $stock = (float) $ingredient->stock_quantity; @endphp
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $ingredient->name }}</td>
                                <td class="text-end">
                                    {{ $formatQuantity($stock) }} {{ $ingredient->usage_unit }}
                                    @if ($stock <= 0)
                                        <span class="badge text-bg-secondary ms-1">Out</span>
                                    @endif
                                </td>
                                <td class="text-end">₱{{ number_format($ingredient->unitCost(), 2) }} / {{ $ingredient->usage_unit }}</td>
                                <td class="text-end pe-3">
                                    <form method="POST" action="{{ route('cost-analysis.items.store') }}"
                                          class="d-inline-flex align-items-center justify-content-end gap-1">
                                        @csrf
                                        <input type="hidden" name="item_name" value="{{ $ingredient->name }}">
                                        <input type="hidden" name="unit_price" value="{{ number_format($ingredient->unitCost(), 2, '.', '') }}">
                                        <input type="number" name="quantity" min="0.001" step="0.001"
                                               max="{{ $stock }}" value="1"
                                               class="form-control form-control-sm" style="width:100px"
                                               aria-label="Quantity of {{ $ingredient->name }}"
                                               @disabled($stock <= 0)>
                                        <button type="submit" class="btn btn-sm btn-success" @disabled($stock <= 0)>
                                            Add
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    @if ($search !== '')
                                        No ingredients match “{{ $search }}”.
                                    @else
                                        No inventory ingredients have been added yet.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
