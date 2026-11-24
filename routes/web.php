<?php

use App\Http\Controllers\CostAnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::post('/inventory/{item}/movements', [InventoryController::class, 'storeMovement'])->name('inventory.movements.store');
    Route::get('/cost-analysis', [CostAnalysisController::class, 'index'])->name('cost-analysis.index');
    Route::get('/cost-analysis/ingredients', [CostAnalysisController::class, 'ingredients'])->name('cost-analysis.ingredients');
    Route::post('/cost-analysis/calculate', [CostAnalysisController::class, 'calculate'])->name('cost-analysis.calculate');
    Route::post('/cost-analysis/reports', [CostAnalysisController::class, 'saveReport'])->name('cost-analysis.reports.store');
    Route::get('/cost-analysis/reports', [CostAnalysisController::class, 'reportsIndex'])->name('cost-analysis.reports.index');
    Route::get('/cost-analysis/reports/{report}', [CostAnalysisController::class, 'showReport'])->name('cost-analysis.reports.show');
    Route::delete('/cost-analysis/items', [CostAnalysisController::class, 'resetItems'])->name('cost-analysis.items.reset');
    Route::post('/cost-analysis/items', [CostAnalysisController::class, 'store'])->name('cost-analysis.items.store');
    Route::put('/cost-analysis/items/{item}', [CostAnalysisController::class, 'update'])->name('cost-analysis.items.update');
    Route::delete('/cost-analysis/items/{item}', [CostAnalysisController::class, 'destroy'])->name('cost-analysis.items.destroy');
});

require __DIR__.'/auth.php';
