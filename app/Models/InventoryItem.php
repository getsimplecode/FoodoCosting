<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'name',
        'category',
        'purchase_quantity',
        'conversion_quantity',
        'purchase_unit',
        'usage_unit',
        'purchase_price',
        'stock_quantity',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'purchase_quantity' => 'decimal:3',
            'conversion_quantity' => 'decimal:3',
            'purchase_price' => 'decimal:2',
            'stock_quantity' => 'decimal:3',
            'reorder_level' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unitCost(): float
    {
        $batches = $this->relationLoaded('batches')
            ? $this->batches
            : $this->batches()
                ->where('quantity', '>', 0)
                ->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expiration_date')
                ->orderBy('received_at')
                ->get();

        $purchaseQuantity = (float) $this->purchase_quantity;
        $conversionQuantity = (float) $this->conversion_quantity;
        if ($conversionQuantity <= 0) {
            $conversionQuantity = 1;
        }
        $fallbackUnitCost = $purchaseQuantity > 0
            ? (float) $this->purchase_price / ($purchaseQuantity * $conversionQuantity)
            : 0;

        $stock = (float) $this->stock_quantity;
        if ($stock <= 0) {
            return $fallbackUnitCost;
        }

        $coveredQuantity = (float) $batches->sum(fn ($batch) => (float) $batch->quantity);
        $uncoveredQuantity = max(0, $stock - $coveredQuantity);

        if ($uncoveredQuantity > 0) {
            return $fallbackUnitCost;
        }

        $nextBatch = $batches->first();

        return $nextBatch && (float) $nextBatch->unit_cost > 0
            ? (float) $nextBatch->unit_cost
            : $fallbackUnitCost;
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }
}
