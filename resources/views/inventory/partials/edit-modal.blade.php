<div class="modal fade" id="editInventoryModal{{ $item->id }}" tabindex="-1" aria-labelledby="editInventoryModalLabel{{ $item->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('inventory.update', $item) }}" class="modal-content border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6" id="editInventoryModalLabel{{ $item->id }}">Edit Inventory Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                @include('inventory.partials.form-fields', ['item' => $item, 'isCreate' => false])
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Update Item</button>
            </div>
        </form>
    </div>
</div>
