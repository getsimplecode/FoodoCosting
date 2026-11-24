<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryMovement extends Model
{
    protected $fillable = [
        'user_id',
        'inventory_item_id',
        'type',
        'quantity',
        'previous_stock',
        'resulting_stock',
        'reason',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'previous_stock' => 'decimal:3',
            'resulting_stock' => 'decimal:3',
            'occurred_at' => 'datetime',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(InventoryBatch::class, 'inventory_movement_batches')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
