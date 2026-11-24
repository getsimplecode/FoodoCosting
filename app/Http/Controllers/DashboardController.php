<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $items = $user->inventoryItems()->get();
        $lowStockItems = $items
            ->filter(fn ($item) => (float) $item->stock_quantity <= (float) $item->reorder_level)
            ->sortBy(fn ($item) => (float) $item->stock_quantity)
            ->take(5)
            ->values();

        $expiringBatchQuery = InventoryBatch::query()
            ->where('user_id', $user->id)
            ->where('quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', today()->addDays(30));
        $expiringBatchCount = (clone $expiringBatchQuery)->count();
        $expiringBatches = $expiringBatchQuery
            ->with('inventoryItem:id,name,usage_unit')
            ->orderBy('expiration_date')
            ->limit(5)
            ->get();

        $recentMovements = $user->inventoryMovements()
            ->with('inventoryItem')
            ->latest('occurred_at')
            ->limit(6)
            ->get();
        $recentReports = $user->costAnalysisReports()
            ->latest()
            ->limit(5)
            ->get();

        $categoryCounts = $items
            ->groupBy(fn ($item) => filled($item->category) ? $item->category : 'Uncategorized')
            ->map(fn ($categoryItems) => $categoryItems->count());
        $chartPalette = ['#0b7250', '#3e75c3', '#e3b323', '#e58a3a', '#8a6fc1', '#91a59a'];
        $categoryChart = [
            'labels' => $categoryCounts->keys()->values()->all(),
            'values' => $categoryCounts->values()->all(),
            'colors' => $categoryCounts->keys()->values()->map(
                fn ($category, $index) => $chartPalette[$index % count($chartPalette)]
            )->all(),
            'empty' => $categoryCounts->isEmpty(),
        ];
        if ($categoryChart['empty']) {
            $categoryChart['labels'] = ['No inventory yet'];
            $categoryChart['values'] = [1];
            $categoryChart['colors'] = ['#e8eee9'];
        }

        $chartDates = collect(range(6, 0))->map(fn ($daysAgo) => today()->subDays($daysAgo));
        $dailyMovementCounts = [];
        foreach ($chartDates as $date) {
            $dailyMovementCounts[$date->toDateString()] = [
                'stock_in' => 0,
                'stock_out' => 0,
                'damaged' => 0,
            ];
        }

        $movementRecords = $user->inventoryMovements()
            ->whereBetween('occurred_at', [$chartDates->first(), now()])
            ->get(['type', 'occurred_at']);
        foreach ($movementRecords as $movement) {
            $date = $movement->occurred_at->toDateString();
            if (isset($dailyMovementCounts[$date][$movement->type])) {
                $dailyMovementCounts[$date][$movement->type]++;
            }
        }

        $movementChart = [
            'labels' => $chartDates->map(fn ($date) => $date->format('D'))->all(),
            'datasets' => [
                [
                    'label' => 'Stock in',
                    'data' => array_column($dailyMovementCounts, 'stock_in'),
                    'borderColor' => '#0b7250',
                    'backgroundColor' => 'rgba(11, 114, 80, 0.12)',
                ],
                [
                    'label' => 'Stock out',
                    'data' => array_column($dailyMovementCounts, 'stock_out'),
                    'borderColor' => '#3e75c3',
                    'backgroundColor' => 'rgba(62, 117, 195, 0.12)',
                ],
                [
                    'label' => 'Damaged',
                    'data' => array_column($dailyMovementCounts, 'damaged'),
                    'borderColor' => '#d95d59',
                    'backgroundColor' => 'rgba(217, 93, 89, 0.12)',
                ],
            ],
        ];

        return view('dashboard', [
            'totalItems' => $items->count(),
            'ingredientCount' => $items->where('category', 'Ingredients')->count(),
            'lowStockCount' => $items->filter(
                fn ($item) => (float) $item->stock_quantity <= (float) $item->reorder_level
            )->count(),
            'reportCount' => $user->costAnalysisReports()->count(),
            'lowStockItems' => $lowStockItems,
            'expiringBatches' => $expiringBatches,
            'expiringBatchCount' => $expiringBatchCount,
            'recentMovements' => $recentMovements,
            'recentReports' => $recentReports,
            'categoryChart' => $categoryChart,
            'movementChart' => $movementChart,
        ]);
    }
}
