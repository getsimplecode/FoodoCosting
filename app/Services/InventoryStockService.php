<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStockService
{
    public function consume(User $user, array $consumptions, string $reason): void
    {
        DB::transaction(function () use ($user, $consumptions, $reason): void {
            $items = $user->inventoryItems()->get();
            $aggregated = [];

            foreach ($consumptions as $consumption) {
                $name = $this->normalizeName((string) ($consumption['item_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $aggregated[$name] = ($aggregated[$name] ?? 0) + (float) ($consumption['quantity'] ?? 0);
            }

            foreach ($aggregated as $normalizedName => $quantity) {
                $quantity = round((float) $quantity, 3);
                if ($quantity <= 0) {
                    continue;
                }

                $item = $items->first(
                    fn ($inventoryItem) => $this->normalizeName($inventoryItem->name) === $normalizedName
                );

                if (!$item) {
                    continue;
                }

                $item = $item->newQuery()->lockForUpdate()->findOrFail($item->id);
                $previousStock = (float) $item->stock_quantity;

                if ($quantity > $previousStock) {
                    throw ValidationException::withMessages([
                        'items' => "Cannot save report. {$item->name} needs {$this->formatQuantity($quantity)}, but only {$this->formatQuantity($previousStock)} is available.",
                    ]);
                }

                $remaining = $quantity;
                $movement = $item->movements()->create([
                    'user_id' => $user->id,
                    'type' => 'stock_out',
                    'quantity' => $quantity,
                    'previous_stock' => $previousStock,
                    'resulting_stock' => round($previousStock - $quantity, 3),
                    'reason' => $reason,
                    'occurred_at' => now(),
                ]);

                $batches = $item->batches()
                    ->where('quantity', '>', 0)
                    ->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('expiration_date')
                    ->orderBy('received_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $available = (float) $batch->quantity;
                    $taken = min($remaining, $available);

                    $batch->update([
                        'quantity' => round($available - $taken, 3),
                    ]);

                    $movement->batches()->attach($batch->id, [
                        'quantity' => round($taken, 3),
                    ]);

                    $remaining -= $taken;
                }

                $item->update([
                    'stock_quantity' => round($previousStock - $quantity, 3),
                ]);
            }
        });
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''), 'UTF-8');
    }

    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }
}
