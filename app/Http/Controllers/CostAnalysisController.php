<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCostAnalysisItemRequest;
use App\Http\Requests\UpdateCostAnalysisItemRequest;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CostAnalysisController extends Controller
{
    public function index(Request $request): View
    {
        $items = $request->user()->costAnalysisItems()->latest()->get();
        $grandTotal = $request->user()->costAnalysisItems()->sum('line_total');
        $calculation = $this->calculationInputs($request);
        $createdPacks = $calculation['created_packs'];
        $targetCostPercent = $calculation['target_cost_percent'];
        $otherExpenses = $calculation['other_expenses'];
        $isSavedReport = false;

        return view('cost-analysis.index', compact(
            'items',
            'grandTotal',
            'createdPacks',
            'targetCostPercent',
            'otherExpenses',
            'isSavedReport',
        ));
    }

    public function showReport(Request $request, int $report): View
    {
        $savedReport = $request->user()->costAnalysisReports()->findOrFail($report);
        $snapshot = $savedReport->snapshot;
        $items = $snapshot['items'];
        $createdPacks = $snapshot['created_packs'];
        $targetCostPercent = $snapshot['target_cost_percent'];
        $otherExpenses = $snapshot['other_expenses'];
        $isSavedReport = true;
        $autoPrint = $request->boolean('print');

        return view('cost-analysis.index', compact(
            'items',
            'createdPacks',
            'targetCostPercent',
            'otherExpenses',
            'isSavedReport',
            'savedReport',
            'autoPrint',
        ));
    }

    public function ingredients(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim((string) ($validated['search'] ?? ''));

        $ingredients = $request->user()->inventoryItems()
            ->where('category', 'Ingredients')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->with(['batches' => fn ($query) => $query
                ->where('quantity', '>', 0)
                ->orderByRaw('CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('expiration_date')
                ->orderBy('received_at')])
            ->orderBy('name')
            ->get();

        return view('cost-analysis.ingredients', compact('ingredients', 'search'));
    }

    public function reportsIndex(Request $request): View
    {
        $reports = $request->user()->costAnalysisReports()->latest()->get();

        return view('reports.index', compact('reports'));
    }

    public function calculate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'created_packs' => ['required', 'integer', 'min:1', 'max:1000000'],
            'target_cost_percent' => ['required', 'numeric', 'gt:0', 'lte:100', 'decimal:0,2'],
            'other_expenses' => ['required', 'numeric', 'gte:0', 'decimal:0,2'],
        ]);

        $request->session()->put('cost_analysis_calculation', $validated);

        return to_route('cost-analysis.index')->with('success', 'Cost analysis recalculated.');
    }

    public function saveReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);
        $items = $request->user()->costAnalysisItems()->oldest()->get()->map(fn ($item) => [
            'id' => $item->id,
            'item_name' => $item->item_name,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'line_total' => $item->line_total,
        ])->all();

        if ($items === []) {
            return back()->withErrors(['items' => 'Add at least one bought item before saving a report.']);
        }

        $calculation = $this->calculationInputs($request);
        DB::transaction(function () use ($request, $validated, $items, $calculation): void {
            app(InventoryStockService::class)->consume(
                $request->user(),
                $items,
                'Cost analysis report: ' . $validated['title'],
            );

            $request->user()->costAnalysisReports()->create([
                'title' => $validated['title'],
                'snapshot' => [
                    'items' => $items,
                    ...$calculation,
                ],
            ]);
        });

        return to_route('cost-analysis.reports.index')->with('success', 'Analysis report saved.');
    }

    public function resetItems(Request $request): RedirectResponse
    {
        $request->user()->costAnalysisItems()->delete();

        return to_route('cost-analysis.index')->with('success', 'Current analysis table cleared. Saved reports are unchanged.');
    }

    public function store(StoreCostAnalysisItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['line_total'] = round((float) $validated['quantity'] * (float) $validated['unit_price'], 2);

        $request->user()->costAnalysisItems()->create($validated);

        return to_route('cost-analysis.index')->with('success', 'Item added to your cost analysis.');
    }

    public function update(UpdateCostAnalysisItemRequest $request, int $item): RedirectResponse
    {
        $item = $request->user()->costAnalysisItems()->findOrFail($item);
        $validated = $request->validated();
        $validated['line_total'] = round((float) $validated['quantity'] * (float) $validated['unit_price'], 2);
        $item->update($validated);

        return to_route('cost-analysis.index')->with('success', 'Item updated in your cost analysis.');
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $request->user()->costAnalysisItems()->findOrFail($item)->delete();

        return to_route('cost-analysis.index')->with('success', 'Item removed from your cost analysis.');
    }

    private function calculationInputs(Request $request): array
    {
        $calculation = $request->session()->get('cost_analysis_calculation', []);

        return [
            'created_packs' => $calculation['created_packs'] ?? 24,
            'target_cost_percent' => $calculation['target_cost_percent'] ?? 40,
            'other_expenses' => $calculation['other_expenses'] ?? 500,
        ];
    }
}
