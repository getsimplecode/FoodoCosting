<div class="modal fade" id="addInventoryModal" tabindex="-1" aria-labelledby="addInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('inventory.store') }}" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="addInventoryModalLabel">Add Inventory Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('addInventoryModal');
                if (!modal) return;

                const packageQuantity = modal.querySelector('[name="purchase_quantity"]');
                const conversion = modal.querySelector('[name="conversion_quantity"]');
                const preview = document.getElementById('initialStockPreview');
                const hidden = document.getElementById('initialStockValue');

                function updateInitialStock() {
                    const initial = (parseFloat(packageQuantity?.value) || 0) * (parseFloat(conversion?.value) || 0);
                    preview.value = initial > 0 ? initial.toString() : '0';
                    hidden.value = initial > 0 ? initial.toString() : '0';
                }

                packageQuantity?.addEventListener('input', updateInitialStock);
                conversion?.addEventListener('input', updateInitialStock);
                updateInitialStock();
            });
            </script>
            <div class="modal-body p-3">
                @include('inventory.partials.form-fields', ['item' => null, 'isCreate' => true])
                <hr class="my-2">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Initial Stock</label>
                        <input type="text" id="initialStockPreview" value="0" class="form-control form-control-sm" readonly>
                        <input type="hidden" name="initial_stock" id="initialStockValue" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Expiration Date</label>
                        <input type="date" name="expiration_date" min="{{ now()->toDateString() }}" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="form-text">Initial Stock is calculated from Package Quantity × Usage per Purchase Unit.</div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-success">Add Item</button>
            </div>
        </form>
    </div>
</div>
