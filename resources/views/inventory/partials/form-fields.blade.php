@php($item = $item ?? null)
@php($isCreate = $isCreate ?? false)
<div class="row g-2">
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Item Name</label>
        <input type="text" name="name" value="{{ $isCreate ? '' : old('name', $item?->name) }}"
               class="form-control form-control-sm" maxlength="255" autocomplete="off" required>
    </div>
    <div class="col-md-6">
        <label class="form-label small fw-semibold">Category</label>
        <select name="category" class="form-select form-select-sm">
            <option value="">Uncategorized</option>
            @foreach (['Ingredients', 'Packaging', 'Supplies'] as $categoryOption)
                <option value="{{ $categoryOption }}" @selected(!$isCreate && old('category', $item?->category) === $categoryOption)>
                    {{ $categoryOption }}
                </option>
            @endforeach
            @if ($item && $item->category && !in_array($item->category, ['Ingredients', 'Packaging', 'Supplies'], true))
                <option value="{{ $item->category }}" selected>{{ $item->category }}</option>
            @endif
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Package Size</label>
        <input type="number" name="purchase_quantity" value="{{ $isCreate ? '1' : old('purchase_quantity', $item?->purchase_quantity) }}" min="0.001" step="0.001" class="form-control form-control-sm" required>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Purchase Unit</label>
        <select name="purchase_unit" class="form-select form-select-sm" required>
            <option value="">Select unit</option>
            @foreach (['kg', 'g', 'liter', 'ml', 'sack', 'pack', 'bottle', 'box', 'can', 'pcs'] as $unitOption)
                <option value="{{ $unitOption }}" @selected(!$isCreate && old('purchase_unit', $item?->purchase_unit) === $unitOption)>
                    {{ $unitOption }}
                </option>
            @endforeach
            @if ($item && $item->purchase_unit && !in_array($item->purchase_unit, ['kg', 'g', 'liter', 'ml', 'sack', 'pack', 'bottle', 'box', 'can', 'pcs'], true))
                <option value="{{ $item->purchase_unit }}" selected>{{ $item->purchase_unit }}</option>
            @endif
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Usage per 1 Purchase Unit</label>
        <input type="number" id="{{ $isCreate ? 'conversionQuantityCreate' : 'conversionQuantity' . $item->id }}"
               name="conversion_quantity"
               value="{{ $isCreate ? '1' : old('conversion_quantity', $item?->conversion_quantity ?? 1) }}"
               min="0.001" step="0.001" class="form-control form-control-sm" required>
        <div class="form-text">Example: 1 sack = 25 kg</div>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Usage Unit</label>
        <select name="usage_unit" class="form-select form-select-sm" required>
            <option value="">Select unit</option>
            @foreach (['kg', 'g', 'liter', 'ml', 'pcs'] as $unitOption)
                <option value="{{ $unitOption }}" @selected(!$isCreate && old('usage_unit', $item?->usage_unit) === $unitOption)>
                    {{ $unitOption }}
                </option>
            @endforeach
            @if ($item && $item->usage_unit && !in_array($item->usage_unit, ['kg', 'g', 'liter', 'ml', 'pcs'], true))
                <option value="{{ $item->usage_unit }}" selected>{{ $item->usage_unit }}</option>
            @endif
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Purchase Price</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text">₱</span>
            <input type="number" name="purchase_price" value="{{ $isCreate ? '' : old('purchase_price', $item?->purchase_price) }}" min="0" step="0.01" class="form-control" required>
        </div>
    </div>
    <div class="col-md-12">
        <label class="form-label small fw-semibold">Reorder Level</label>
        <input type="number" name="reorder_level" value="{{ $isCreate ? '0' : old('reorder_level', $item?->reorder_level) }}" min="0" step="0.001" class="form-control form-control-sm" required>
        <div class="form-text">Current stock will be managed only through Stock In, Stock Out, and Damaged.</div>
    </div>
</div>
