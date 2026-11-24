<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Http\Requests\StoreInventoryMovementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $category = trim((string) $request->query('category'));
        $report = $request->query('view') === 'report';
        $reportCategory = $report
            ? (string) $request->query('report_category', 'all')
            : 'all';

        $query = $request->user()->inventoryItems()->latest();

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        $items = $query->get();
        if ($report) {
            $items = match ($reportCategory) {
                'ingredients' => $items->where('category', 'Ingredients')->values(),
                'packaging' => $items->where('category', 'Packaging')->values(),
                'supplies' => $items->where('category', 'Supplies')->values(),
                'both' => $items->whereIn('category', ['Ingredients', 'Packaging'])->values(),
                default => $items,
            };
        }
        $categories = $request->user()->inventoryItems()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $categoryCounts = collect([
            'Ingredients',
            'Packaging',
            'Supplies',
            'Uncategorized',
        ])->mapWithKeys(fn (string $section) => [
            $section => $request->user()->inventoryItems()
                ->when(
                    $section === 'Uncategorized',
                    fn ($query) => $query->where(fn ($nested) => $nested
                        ->whereNull('category')
                        ->orWhere('category', '')),
                    fn ($query) => $query->where('category', $section),
                )
                ->count(),
        ]);

        $movements = $request->user()->inventoryMovements()
            ->with('inventoryItem')
            ->latest('occurred_at')
            ->limit(20)
            ->get();

        $reportMovements = $request->user()->inventoryMovements()
            ->with('inventoryItem')
            ->when($reportCategory === 'ingredients', fn ($query) => $query->whereHas(
                'inventoryItem',
                fn ($itemQuery) => $itemQuery->where('category', 'Ingredients'),
            ))
            ->when($reportCategory === 'packaging', fn ($query) => $query->whereHas(
                'inventoryItem',
                fn ($itemQuery) => $itemQuery->where('category', 'Packaging'),
            ))
            ->when($reportCategory === 'supplies', fn ($query) => $query->whereHas(
                'inventoryItem',
                fn ($itemQuery) => $itemQuery->where('category', 'Supplies'),
            ))
            ->when($reportCategory === 'both', fn ($query) => $query->whereHas(
                'inventoryItem',
                fn ($itemQuery) => $itemQuery->whereIn('category', ['Ingredients', 'Packaging']),
            ))
            ->latest('occurred_at')
            ->limit(500)
            ->get();

        return view('inventory.index', compact(
            'items',
            'categories',
            'search',
            'category',
            'categoryCounts',
            'movements',
            'reportMovements',
            'report',
            'reportCategory',
        ));
    }

    public function store(StoreInventoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $packageCount = (float) $validated['purchase_quantity'];
        $usagePerPackage = (float) $validated['conversion_quantity'];
        $initialStock = $packageCount * $usagePerPackage;
        $expirationDate = $validated['expiration_date'] ?? null;
        unset($validated['initial_stock'], $validated['expiration_date']);
        $validated['stock_quantity'] = $initialStock;

        $normalizedName = $this->normalizeName($validated['name']);
        $existingItem = $request->user()->inventoryItems()
            ->get()
            ->first(fn ($item) => $this->normalizeName($item->name) === $normalizedName);

        if ($existingItem) {
            DB::transaction(function () use ($request, $validated, $initialStock, $expirationDate, $existingItem): void {
                $purchaseQuantity = (float) $validated['purchase_quantity'];
                $conversionQuantity = (float) $validated['conversion_quantity'];
                $purchasePrice = (float) $validated['purchase_price'];
                $purchaseUsageQuantity = $purchaseQuantity * $conversionQuantity;
                $batchUnitCost = $purchaseUsageQuantity > 0 ? $purchasePrice / $purchaseUsageQuantity : 0;
                $previousStock = (float) $existingItem->stock_quantity;
                $resultingStock = $previousStock + $initialStock;

                $existingItem->update([
                    'category' => $validated['category'] ?? $existingItem->category,
                    'purchase_quantity' => $validated['purchase_quantity'],
                    'conversion_quantity' => $validated['conversion_quantity'],
                    'purchase_unit' => $validated['purchase_unit'],
                    'usage_unit' => $validated['usage_unit'],
                    'purchase_price' => $validated['purchase_price'],
                    'reorder_level' => $validated['reorder_level'],
                    'stock_quantity' => $resultingStock,
                ]);

                if ($initialStock > 0) {
                    $now = now();
                    $movement = $existingItem->movements()->create([
                        'user_id' => $request->user()->id,
                        'type' => 'stock_in',
                        'quantity' => $initialStock,
                        'previous_stock' => $previousStock,
                        'resulting_stock' => $resultingStock,
                        'reason' => 'Additional stock purchase',
                        'occurred_at' => $now,
                    ]);

                    $batch = $existingItem->batches()->create([
                        'user_id' => $request->user()->id,
                        'batch_number' => $this->generateBatchNumber($request),
                        'quantity' => $initialStock,
                        'initial_quantity' => $initialStock,
                        'unit_cost' => round($batchUnitCost, 2),
                        'expiration_date' => $expirationDate,
                        'received_at' => $now,
                    ]);

                    $movement->batches()->attach($batch->id, [
                        'quantity' => $initialStock,
                    ]);
                }
            });

            return to_route('inventory.index')->with('success', 'Existing item found. Stock and purchase details updated.');
        }

        DB::transaction(function () use ($request, $validated, $initialStock, $expirationDate): void {
            $item = $request->user()->inventoryItems()->create($validated);

            if ($initialStock > 0) {
                $now = now();
                $purchaseQuantity = (float) $validated['purchase_quantity'];
                $conversionQuantity = (float) $validated['conversion_quantity'];
                $purchasePrice = (float) $validated['purchase_price'];
                $purchaseUsageQuantity = $purchaseQuantity * $conversionQuantity;
                $unitCost = $purchaseUsageQuantity > 0 ? $purchasePrice / $purchaseUsageQuantity : 0;
                $movement = $item->movements()->create([
                    'user_id' => $request->user()->id,
                    'type' => 'stock_in',
                    'quantity' => $initialStock,
                    'previous_stock' => 0,
                    'resulting_stock' => $initialStock,
                    'reason' => 'Initial stock',
                    'occurred_at' => $now,
                ]);

                $batch = $item->batches()->create([
                    'user_id' => $request->user()->id,
                    'batch_number' => $this->generateBatchNumber($request),
                    'quantity' => $initialStock,
                    'initial_quantity' => $initialStock,
                    'unit_cost' => round($unitCost, 2),
                    'expiration_date' => $expirationDate,
                    'received_at' => $now,
                ]);

                $movement->batches()->attach($batch->id, [
                    'quantity' => $initialStock,
                ]);
            }
        });

        return to_route('inventory.index')->with('success', 'Inventory item added.');
    }

    public function update(UpdateInventoryRequest $request, int $item): RedirectResponse
    {
        $inventoryItem = $request->user()->inventoryItems()->findOrFail($item);
        $inventoryItem->update($request->validated());

        return to_route('inventory.index')->with('success', 'Inventory item updated.');
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $request->user()->inventoryItems()->findOrFail($item)->delete();

        return to_route('inventory.index')->with('success', 'Inventory item removed.');
    }

    public function storeMovement(StoreInventoryMovementRequest $request, int $item): RedirectResponse
    {
        $inventoryItem = $request->user()->inventoryItems()->findOrFail($item);
        $validated = $request->validated();

        DB::transaction(function () use ($inventoryItem, $validated, $request): void {
            $inventoryItem = $inventoryItem->newQuery()->lockForUpdate()->findOrFail($inventoryItem->id);
            $quantity = (float) $validated['quantity'];
            $previousStock = (float) $inventoryItem->stock_quantity;

            if (in_array($validated['type'], ['stock_out', 'damaged'], true) && $quantity > $previousStock) {
                throw ValidationException::withMessages([
                    'quantity' => 'The quantity cannot exceed the available stock.',
                ]);
            }

            $resultingStock = $validated['type'] === 'stock_in'
                ? $previousStock + $quantity
                : $previousStock - $quantity;

            $inventoryItem->update([
                'stock_quantity' => round($resultingStock, 3),
            ]);

            $movement = $inventoryItem->movements()->create([
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'resulting_stock' => $resultingStock,
                'reason' => $validated['reason'] ?? null,
                'occurred_at' => $validated['occurred_at'],
            ]);

            if ($validated['type'] === 'stock_in') {
                $unitCost = isset($validated['purchase_price'])
                    ? (float) $validated['purchase_price'] / $quantity
                    : (float) $inventoryItem->unitCost();
                $inventoryItem->batches()->create([
                    'user_id' => $request->user()->id,
                    'batch_number' => $this->generateBatchNumber($request),
                    'quantity' => $quantity,
                    'initial_quantity' => $quantity,
                    'unit_cost' => round($unitCost, 2),
                    'expiration_date' => $validated['expiration_date'] ?? null,
                    'received_at' => $validated['occurred_at'],
                ]);
            } else {
                $remaining = $quantity;
                $batches = $inventoryItem->batches()
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

                    $movement->getAttribute('id');
                    $movement->batches()->attach($batch->id, [
                        'quantity' => round($taken, 3),
                    ]);

                    $remaining -= $taken;
                }
            }
        });

        $message = match ($validated['type']) {
            'stock_in' => 'Stock added successfully.',
            'stock_out' => 'Stock released successfully.',
            'damaged' => 'Damaged stock recorded successfully.',
        };

        return to_route('inventory.index')->with('success', $message);
    }

    private function generateBatchNumber(Request $request): string
    {
        do {
            $batchNumber = 'LOT-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while ($request->user()->inventoryItems()
            ->whereHas('batches', fn ($query) => $query->where('batch_number', $batchNumber))
            ->exists());

        return $batchNumber;
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''), 'UTF-8');
    }
}
