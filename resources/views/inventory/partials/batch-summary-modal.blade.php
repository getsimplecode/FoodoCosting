@php
    $batches = $item->batches()
        ->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END')
        ->orderBy('expiration_date')
        ->orderBy('received_at')
        ->get();
    $formatBatchQuantity = fn (float $quantity): string =>
        rtrim(rtrim(number_format($quantity, 3, '.', ','), '0'), '.');
@endphp

<div class="modal fade" id="batchViewModal{{ $item->id }}" tabindex="-1" aria-labelledby="batchViewModalLabel{{ $item->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title fs-6" id="batchViewModalLabel{{ $item->id }}">{{ $item->name }} — Stock Summary</h5>
                    <div class="small text-muted">
                        Current Stock: {{ $formatBatchQuantity((float) $item->stock_quantity) }} {{ $item->usage_unit }}
                        · Unit Cost: ₱{{ number_format($item->unitCost(), 2) }}/{{ $item->usage_unit }}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-2">Batch</th>
                                <th>Received</th>
                                <th>Expiration</th>
                                <th class="text-end">Initial</th>
                                <th class="text-end">Remaining</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end pe-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($batches as $batch)
                                @php
                                    $remaining = (float) $batch->quantity;
                                    $isExhausted = $remaining <= 0;
                                    $isExpired = $batch->expiration_date && $batch->expiration_date->isPast();
                                @endphp
                                <tr class="{{ $isExhausted ? 'text-muted' : ($isExpired ? 'table-danger' : '') }}">
                                    <td class="ps-2 fw-semibold">{{ $batch->batch_number ?: 'No batch code' }}</td>
                                    <td>{{ $batch->received_at->format('M d, Y') }}</td>
                                    <td>{{ $batch->expiration_date?->format('M d, Y') ?: 'No expiry' }}</td>
                                    <td class="text-end">{{ $formatBatchQuantity((float) $batch->initial_quantity) }} {{ $item->usage_unit }}</td>
                                    <td class="text-end">{{ $formatBatchQuantity($remaining) }} {{ $item->usage_unit }}</td>
                                    <td class="text-end">₱{{ number_format((float) $batch->unit_cost, 2) }}</td>
                                    <td class="text-end pe-2">
                                        @if ($isExhausted)
                                            <span class="badge text-bg-secondary">Exhausted</span>
                                        @elseif ($isExpired)
                                            <span class="badge text-bg-danger">Expired</span>
                                        @else
                                            <span class="badge text-bg-success">Active</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No purchase batches recorded.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
