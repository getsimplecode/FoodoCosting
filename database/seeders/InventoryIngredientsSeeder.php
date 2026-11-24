<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryIngredientsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();

        if (!$user) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $ingredients = [
            ['Chicken Breast', 'case', 'kg', 1500, 14],
            ['Rice', 'sack', 'kg', 1000, 365],
            ['Cooking Oil', 'case', 'L', 1400, 365],
            ['Spring Onion', 'sack', 'kg', 400, 30],
            ['Garlic', 'sack', 'kg', 500, 60],
            ['Ginger', 'sack', 'kg', 300, 30],
            ['Tomato', 'crate', 'kg', 500, 14],
            ['Potato', 'sack', 'kg', 600, 60],
            ['Carrot', 'sack', 'kg', 450, 30],
            ['Cabbage', 'crate', 'pcs', 700, 14],
            ['Bell Pepper', 'crate', 'pcs', 800, 14],
            ['Soy Sauce', 'case', 'L', 350, 365],
            ['Vinegar', 'case', 'L', 250, 365],
            ['Salt', 'bag', 'kg', 100, 730],
            ['Sugar', 'bag', 'kg', 220, 365],
            ['Black Pepper', 'bag', 'kg', 600, 365],
            ['Flour', 'sack', 'kg', 260, 365],
            ['Cornstarch', 'bag', 'kg', 300, 365],
            ['Eggs', 'tray', 'pcs', 400, 30],
            ['Butter', 'case', 'kg', 900, 90],
        ];

        foreach ($ingredients as [$name, $purchaseUnit, $usageUnit, $purchasePrice, $expirationDays]) {
            $item = $user->inventoryItems()->firstOrCreate(
                ['name' => $name],
                [
                    'category' => 'Ingredients',
                    'purchase_quantity' => 1,
                    'conversion_quantity' => 20,
                    'purchase_unit' => $purchaseUnit,
                    'usage_unit' => $usageUnit,
                    'purchase_price' => $purchasePrice,
                    'stock_quantity' => 20,
                    'reorder_level' => 5,
                ],
            );

            if (!$item->wasRecentlyCreated) {
                continue;
            }

            $now = now();
            $movement = $item->movements()->create([
                'user_id' => $user->id,
                'type' => 'stock_in',
                'quantity' => 20,
                'previous_stock' => 0,
                'resulting_stock' => 20,
                'reason' => 'Sample inventory seed',
                'occurred_at' => $now,
            ]);

            $batch = $item->batches()->create([
                'user_id' => $user->id,
                'batch_number' => 'SEED-ING-' . strtoupper(str_replace(' ', '-', $name)),
                'quantity' => 20,
                'initial_quantity' => 20,
                'unit_cost' => round($purchasePrice / 20, 2),
                'expiration_date' => $now->copy()->addDays($expirationDays)->toDateString(),
                'received_at' => $now,
            ]);

            $movement->batches()->attach($batch->id, ['quantity' => 20]);
        }
    }
}
