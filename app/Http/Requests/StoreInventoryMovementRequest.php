<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['stock_in', 'stock_out', 'damaged'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'reason' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['required', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:today'],
            'purchase_price' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2'],
        ];
    }
}
