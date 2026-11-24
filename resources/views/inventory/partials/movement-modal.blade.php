@php
    $formatMovementQuantity = fn (float $quantity): string =>
        rtrim(rtrim(number_format($quantity, 3, '.', ','), '0'), '.');
@endphp
<div class="modal fade" id="movementModal{{ $item->id }}" tabindex="-1" aria-labelledby="movementModalLabel{{ $item->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('inventory.movements.store', $item) }}" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="movementModalLabel{{ $item->id }}">Update Stock - {{ $item->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="small text-muted mb-2">
                    Current stock: <strong>{{ $formatMovementQuantity((float) $item->stock_quantity) }} {{ $item->usage_unit }}</strong>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Movement</label>
                        <select name="type" class="form-select form-select-sm movement-type" required>
                            <option value="stock_in">Stock In</option>
                            <option value="stock_out">Stock Out</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Quantity</label>
                        <input type="number" name="quantity" min="0.001" step="0.001" class="form-control form-control-sm movement-quantity" required>
                    </div>
                    <div class="col-md-6 stock-in-fields">
                        <label class="form-label small fw-semibold">Packages Purchased</label>
                        <input type="number" name="packages_purchased" value="1" min="0.001" step="0.001" class="form-control form-control-sm movement-packages">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Date and Time</label>
                        <input type="datetime-local" name="occurred_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Reason</label>
                        <input type="text" name="reason" maxlength="255" class="form-control form-control-sm" placeholder="Delivery, issue, spoilage">
                    </div>
                    <div class="col-md-6 stock-in-fields">
                        <label class="form-label small fw-semibold">Expiration Date <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="date" name="expiration_date" min="{{ now()->toDateString() }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6 stock-in-fields">
                        <label class="form-label small fw-semibold">Purchase Total <span class="text-muted fw-normal">(optional)</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="purchase_price" min="0" step="0.01" class="form-control" placeholder="Different purchase price">
                        </div>
                    </div>
                </div>
            </div>
            <script>
            (function () {
                const modal = document.getElementById('movementModal{{ $item->id }}');
                if (!modal) return;

                const quantity = modal.querySelector('.movement-quantity');
                const packages = modal.querySelector('.movement-packages');
                const conversion = {{ (float) ($item->conversion_quantity ?? 1) }};
                if (quantity) quantity.readOnly = true;

                function updateQuantity() {
                    if (!quantity || !packages) return;
                    quantity.value = ((parseFloat(packages.value) || 0) * conversion)
                        .toFixed(3)
                        .replace(/\.?0+$/, '');
                }

                packages?.addEventListener('input', updateQuantity);
                updateQuantity();
            })();

            document.addEventListener('change', function (event) {
                if (!event.target.matches('#movementModal{{ $item->id }} .movement-type')) {
                    return;
                }

                document.querySelectorAll('#movementModal{{ $item->id }} .stock-in-fields').forEach(function (field) {
                    field.classList.toggle('d-none', event.target.value !== 'stock_in');
                });

                const quantity = document.querySelector('#movementModal{{ $item->id }} .movement-quantity');
                if (quantity) quantity.readOnly = event.target.value === 'stock_in';

                if (event.target.value === 'stock_in') {
                    const packages = document.querySelector('#movementModal{{ $item->id }} .movement-packages');
                    packages?.dispatchEvent(new Event('input'));
                }
            });
            </script>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-success">Save Movement</button>
            </div>
        </form>
    </div>
</div>
