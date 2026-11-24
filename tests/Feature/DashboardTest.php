<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_user_scoped_inventory_alerts_activity_and_reports(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $item = $user->inventoryItems()->create([
            'name' => 'Chicken Breast',
            'category' => 'Ingredients',
            'purchase_quantity' => '1.000',
            'conversion_quantity' => '1.000',
            'purchase_unit' => 'kg',
            'usage_unit' => 'kg',
            'purchase_price' => '250.00',
            'stock_quantity' => '2.000',
            'reorder_level' => '5.000',
        ]);
        $item->batches()->create([
            'user_id' => $user->id,
            'batch_number' => 'LOT-EXPIRING',
            'quantity' => '2.000',
            'initial_quantity' => '2.000',
            'unit_cost' => '250.00',
            'expiration_date' => today()->addDays(7)->toDateString(),
            'received_at' => now(),
        ]);
        $item->movements()->create([
            'user_id' => $user->id,
            'type' => 'stock_out',
            'quantity' => '1.000',
            'previous_stock' => '3.000',
            'resulting_stock' => '2.000',
            'reason' => 'Kitchen prep',
            'occurred_at' => now(),
        ]);
        $user->costAnalysisReports()->create([
            'title' => 'Chicken Lunch',
            'snapshot' => [
                'items' => [[
                    'item_name' => 'Chicken Breast',
                    'quantity' => '1.000',
                    'unit_price' => '250.00',
                    'line_total' => '250.00',
                ]],
                'created_packs' => 5,
                'target_cost_percent' => 40,
                'other_expenses' => 50,
            ],
        ]);

        $otherUser->inventoryItems()->create([
            'name' => 'Private Ingredient',
            'category' => 'Ingredients',
            'purchase_quantity' => '1.000',
            'conversion_quantity' => '1.000',
            'purchase_unit' => 'kg',
            'usage_unit' => 'kg',
            'purchase_price' => '100.00',
            'stock_quantity' => '0.000',
            'reorder_level' => '5.000',
        ]);
        $otherUser->costAnalysisReports()->create([
            'title' => 'Private Report',
            'snapshot' => [
                'items' => [],
                'created_packs' => 1,
                'target_cost_percent' => 40,
                'other_expenses' => 0,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Chicken Breast')
            ->assertSee('Expiring soon')
            ->assertSee('Stock out')
            ->assertSee('Chicken Lunch')
            ->assertSee('data-dashboard-chart="line"', false)
            ->assertSee('data-dashboard-chart="doughnut"', false)
            ->assertDontSee('Private Ingredient')
            ->assertDontSee('Private Report');
    }
}
