<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryBatch extends Model
{
    protected $fillable = [
        'user_id',
        'inventory_item_id',
        'batch_number',
        'quantity',
        'initial_quantity',
        'unit_cost',
        'expiration_date',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'initial_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'expiration_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function movements(): BelongsToMany
    {
        return $this->belongsToMany(InventoryMovement::class, 'inventory_movement_batches')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
