<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(['Ingredients', 'Packaging', 'Supplies'])],
            'purchase_quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'conversion_quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'purchase_unit' => ['required', Rule::in(['kg', 'g', 'liter', 'ml', 'sack', 'pack', 'bottle', 'box', 'can', 'pcs'])],
            'usage_unit' => ['required', Rule::in(['kg', 'g', 'liter', 'ml', 'pcs'])],
            'purchase_price' => ['required', 'numeric', 'gte:0', 'decimal:0,2'],
            'reorder_level' => ['required', 'numeric', 'gte:0', 'decimal:0,3'],
            'initial_stock' => ['nullable', 'numeric', 'gte:0', 'decimal:0,3'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
