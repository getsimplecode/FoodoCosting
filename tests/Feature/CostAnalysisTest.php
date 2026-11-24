<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_added_items_are_saved_and_restored_with_a_grand_total(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->followingRedirects()->post(route('cost-analysis.items.store'), [
            'item_name' => 'Cooking Oil',
            'quantity' => '2.500',
            'unit_price' => '10.00',
            'line_total' => '0.01',
        ])->assertOk()
            ->assertSee('Item added to your cost analysis.');

        $this->assertDatabaseHas('cost_analysis_items', [
            'user_id' => $user->id,
            'item_name' => 'Cooking Oil',
            'quantity' => '2.500',
            'unit_price' => '10.00',
            'line_total' => '25.00',
        ]);

        $this->get(route('cost-analysis.index'))
            ->assertOk()
            ->assertSee('Cooking Oil')
            ->assertSee('25.00');
    }

    public function test_add_item_modal_offers_inventory_browsing_and_custom_item_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cost-analysis.index'))
            ->assertOk()
            ->assertSee('cost-item-modal-dialog', false)
            ->assertSee('Use an inventory ingredient')
            ->assertSee(route('cost-analysis.ingredients'))
            ->assertSee('name="item_name"', false)
            ->assertSee('name="quantity"', false)
            ->assertSee('name="unit_price"', false);
    }

    public function test_invalid_add_item_submission_reopens_the_modal_with_validation_feedback(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('cost-analysis.index'))
            ->post(route('cost-analysis.items.store'), [
                'item_name' => '',
                'quantity' => '',
                'unit_price' => '',
            ])
            ->assertSessionHasErrors(['item_name', 'quantity', 'unit_price']);

        $this->get(route('cost-analysis.index'))
            ->assertOk()
            ->assertSee('data-auto-show="true"', false)
            ->assertSee('invalid-feedback', false);
    }

    public function test_users_only_see_and_remove_their_own_items(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = $owner->costAnalysisItems()->create([
            'item_name' => 'Flour',
            'quantity' => '1.000',
            'unit_price' => '5.00',
            'line_total' => '5.00',
        ]);

        $this->actingAs($otherUser)
            ->get(route('cost-analysis.index'))
            ->assertOk()
            ->assertDontSee('Flour');

        $this->delete(route('cost-analysis.items.destroy', $item))
            ->assertNotFound();

        $this->put(route('cost-analysis.items.update', $item), [
            'item_name' => 'Changed flour',
            'quantity' => '2.000',
            'unit_price' => '10.00',
        ])->assertNotFound();

        $this->assertDatabaseHas('cost_analysis_items', ['id' => $item->id]);
    }

    public function test_item_updates_recalculate_its_line_total(): void
    {
        $user = User::factory()->create();
        $item = $user->costAnalysisItems()->create([
            'item_name' => 'Rice',
            'quantity' => '1.000',
            'unit_price' => '5.00',
            'line_total' => '5.00',
        ]);

        $this->actingAs($user)
            ->put(route('cost-analysis.items.update', $item), [
                'item_name' => 'Jasmine rice',
                'quantity' => '2.500',
                'unit_price' => '12.00',
            ])
            ->assertRedirect(route('cost-analysis.index'));

        $this->assertDatabaseHas('cost_analysis_items', [
            'id' => $item->id,
            'item_name' => 'Jasmine rice',
            'quantity' => '2.500',
            'unit_price' => '12.00',
            'line_total' => '30.00',
        ]);
    }

    public function test_calculation_inputs_are_validated_and_applied_after_redirect(): void
    {
        $user = User::factory()->create();
        $user->costAnalysisItems()->create([
            'item_name' => 'Chicken',
            'quantity' => '2.000',
            'unit_price' => '10.00',
            'line_total' => '20.00',
        ]);

        $this->actingAs($user)
            ->post(route('cost-analysis.calculate'), [
                'created_packs' => '4',
                'target_cost_percent' => '40.00',
                'other_expenses' => '2.00',
            ])
            ->assertRedirect(route('cost-analysis.index'));

        $this->get(route('cost-analysis.index'))
            ->assertOk()
            ->assertSee('₱12.50')
            ->assertSee('₱28.00')
            ->assertSee('56.00%');

        $this->from(route('cost-analysis.index'))
            ->post(route('cost-analysis.calculate'), [
                'created_packs' => '0',
                'target_cost_percent' => '0',
                'other_expenses' => '-1',
            ])
            ->assertSessionHasErrors(['created_packs', 'target_cost_percent', 'other_expenses']);
    }

    public function test_comparison_chart_keeps_a_net_loss_as_a_negative_value(): void
    {
        $user = User::factory()->create();
        $user->costAnalysisItems()->create([
            'item_name' => 'Ingredients',
            'quantity' => '1.000',
            'unit_price' => '100.00',
            'line_total' => '100.00',
        ]);

        $this->actingAs($user)
            ->get(route('cost-analysis.index'))
            ->assertOk()
            ->assertSee('Net loss')
            ->assertSee('₱-350.00');
    }

    public function test_saved_reports_keep_a_snapshot_after_the_current_table_is_reset(): void
    {
        $user = User::factory()->create();
        $item = $user->costAnalysisItems()->create([
            'item_name' => 'Archived Chicken',
            'quantity' => '2.000',
            'unit_price' => '10.00',
            'line_total' => '20.00',
        ]);

        $this->actingAs($user)
            ->post(route('cost-analysis.calculate'), [
                'created_packs' => '4',
                'target_cost_percent' => '40.00',
                'other_expenses' => '2.00',
            ])
            ->assertRedirect(route('cost-analysis.index'));

        $this->post(route('cost-analysis.reports.store'), ['title' => 'Chicken Batch'])
            ->assertRedirect(route('cost-analysis.reports.index'));

        $report = $user->costAnalysisReports()->firstOrFail();
        $this->assertSame('Archived Chicken', $report->snapshot['items'][0]['item_name']);
        $this->assertSame('4', $report->snapshot['created_packs']);

        $this->delete(route('cost-analysis.items.reset'))
            ->assertRedirect(route('cost-analysis.index'));

        $this->assertDatabaseMissing('cost_analysis_items', ['id' => $item->id]);
        $this->assertDatabaseHas('cost_analysis_reports', ['id' => $report->id]);

        $this->get(route('cost-analysis.index'))
            ->assertOk()
            ->assertDontSee('Archived Chicken');

        $this->get(route('cost-analysis.reports.index'))
            ->assertOk()
            ->assertSee('Chicken Batch')
            ->assertSee(route('cost-analysis.reports.show', $report))
            ->assertSee('Print')
            ->assertSee('aria-label="View report: Chicken Batch"', false)
            ->assertSee('aria-label="Print report: Chicken Batch"', false)
            ->assertSee('print=1');

        $this->get(route('cost-analysis.reports.show', $report))
            ->assertOk()
            ->assertSee('Viewing saved report: <strong>Chicken Batch</strong>', false)
            ->assertSee('Archived Chicken')
            ->assertSee('₱20.00')
            ->assertSee('aria-label="Print report"', false)
            ->assertDontSee('Update Item');

        $this->get(route('cost-analysis.reports.show', ['report' => $report, 'print' => 1]))
            ->assertOk()
            ->assertSee('saved-report-print-content', false)
            ->assertSee('Saved Cost Analysis')
            ->assertSee('Total Direct Cost')
            ->assertSee('window.print()', false);
    }

    public function test_users_cannot_view_another_users_saved_report(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $report = $owner->costAnalysisReports()->create([
            'title' => 'Private report',
            'snapshot' => [
                'items' => [[
                    'id' => 1,
                    'item_name' => 'Private item',
                    'quantity' => '1.000',
                    'unit_price' => '1.00',
                    'line_total' => '1.00',
                ]],
                'created_packs' => 1,
                'target_cost_percent' => 40,
                'other_expenses' => 0,
            ],
        ]);

        $this->actingAs($otherUser)
            ->get(route('cost-analysis.reports.index'))
            ->assertOk()
            ->assertDontSee('Private report');

        $this->actingAs($otherUser)
            ->get(route('cost-analysis.reports.show', $report))
            ->assertNotFound();
    }

    public function test_empty_cost_analysis_cannot_be_saved_as_a_report(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('cost-analysis.index'))
            ->post(route('cost-analysis.reports.store'), ['title' => 'Empty report'])
            ->assertRedirect(route('cost-analysis.index'))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('cost_analysis_reports', 0);
    }

    public function test_saving_a_report_deducts_matching_inventory_using_fefo(): void
    {
        $user = User::factory()->create();
        $item = $user->inventoryItems()->create([
            'name' => 'Chicken',
            'category' => 'Ingredients',
            'purchase_quantity' => '1.000',
            'conversion_quantity' => '1.000',
            'purchase_unit' => 'kg',
            'usage_unit' => 'kg',
            'purchase_price' => '250.00',
            'stock_quantity' => '15.000',
            'reorder_level' => '2.000',
        ]);

        $older = $item->batches()->create([
            'user_id' => $user->id,
            'batch_number' => 'LOT-OLD',
            'quantity' => '5.000',
            'initial_quantity' => '5.000',
            'unit_cost' => '250.00',
            'expiration_date' => now()->addDays(3)->toDateString(),
            'received_at' => now()->subDays(2),
        ]);
        $newer = $item->batches()->create([
            'user_id' => $user->id,
            'batch_number' => 'LOT-NEW',
            'quantity' => '10.000',
            'initial_quantity' => '10.000',
            'unit_cost' => '250.00',
            'expiration_date' => now()->addDays(10)->toDateString(),
            'received_at' => now(),
        ]);

        $user->costAnalysisItems()->create([
            'item_name' => '  chicken ',
            'quantity' => '7.000',
            'unit_price' => '250.00',
            'line_total' => '1750.00',
        ]);

        $this->actingAs($user)
            ->post(route('cost-analysis.reports.store'), ['title' => 'Chicken Sales'])
            ->assertRedirect(route('cost-analysis.reports.index'));

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'stock_quantity' => '8.000',
        ]);
        $this->assertSame('0.000', $older->fresh()->quantity);
        $this->assertSame('8.000', $newer->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'type' => 'stock_out',
            'quantity' => '7.000',
            'reason' => 'Cost analysis report: Chicken Sales',
        ]);
    }

    public function test_saving_a_report_fails_without_enough_matching_inventory(): void
    {
        $user = User::factory()->create();
        $item = $user->inventoryItems()->create([
            'name' => 'Rice',
            'category' => 'Ingredients',
            'purchase_quantity' => '25.000',
            'conversion_quantity' => '1.000',
            'purchase_unit' => 'kg',
            'usage_unit' => 'kg',
            'purchase_price' => '2500.00',
            'stock_quantity' => '5.000',
            'reorder_level' => '2.000',
        ]);

        $item->batches()->create([
            'user_id' => $user->id,
            'batch_number' => 'LOT-RICE',
            'quantity' => '5.000',
            'initial_quantity' => '5.000',
            'unit_cost' => '100.00',
            'expiration_date' => now()->addMonth()->toDateString(),
            'received_at' => now(),
        ]);

        $user->costAnalysisItems()->create([
            'item_name' => 'Rice',
            'quantity' => '6.000',
            'unit_price' => '100.00',
            'line_total' => '600.00',
        ]);

        $this->actingAs($user)
            ->from(route('cost-analysis.index'))
            ->post(route('cost-analysis.reports.store'), ['title' => 'Too much rice'])
            ->assertRedirect(route('cost-analysis.index'))
            ->assertSessionHasErrors(['items']);

        $this->assertDatabaseCount('cost_analysis_reports', 0);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'stock_quantity' => '5.000',
        ]);
    }

    public function test_add_item_modal_links_to_a_separate_ingredient_page(): void
    {
        $user = User::factory()->create();
        $user->inventoryItems()->create([
            'name' => 'Chicken',
            'category' => 'Ingredients',
            'purchase_quantity' => '1.000',
            'conversion_quantity' => '1.000',
            'purchase_unit' => 'kg',
            'usage_unit' => 'kg',
            'purchase_price' => '250.00',
            'stock_quantity' => '5.000',
            'reorder_level' => '0.000',
        ]);
        $user->inventoryItems()->create([
            'name' => 'Lunch Box',
            'category' => 'Packaging',
            'purchase_quantity' => '100.000',
            'conversion_quantity' => '1.000',
            'purchase_unit' => 'pack',
            'usage_unit' => 'pcs',
            'purchase_price' => '850.00',
            'stock_quantity' => '100.000',
            'reorder_level' => '0.000',
        ]);

        $this->actingAs($user)
            ->get(route('cost-analysis.index'))
            ->assertOk()
            ->assertSee('aria-label="Browse inventory ingredients"', false)
            ->assertSee('cost-analysis/ingredients')
            ->assertDontSee('Chicken — 5')
            ->assertDontSee('Lunch Box — 100 pcs available')
            ->assertSee('aria-label="Save cost item"', false);
    }

    public function test_calculator_actions_use_accessible_icon_buttons(): void
    {
        $user = User::factory()->create();
        $user->costAnalysisItems()->create([
            'item_name' => 'Cooking Oil',
            'quantity' => '1.000',
            'unit_price' => '10.00',
            'line_total' => '10.00',
        ]);

        $this->actingAs($user)
            ->get(route('cost-analysis.index'))
            ->assertOk()
            ->assertSee('aria-label="Save report"', false)
            ->assertSee('aria-label="Reset current table"', false)
            ->assertSee('aria-label="Add cost item"', false)
            ->assertSee('aria-label="Recalculate cost analysis"', false)
            ->assertSee('aria-label="Edit Cooking Oil"', false)
            ->assertSee('aria-label="Delete Cooking Oil"', false);
    }

    public function test_ingredient_page_shows_all_matching_items_in_a_scrollable_list(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        for ($index = 1; $index <= 21; $index++) {
            $user->inventoryItems()->create([
                'name' => sprintf('Ingredient %02d', $index),
                'category' => 'Ingredients',
                'purchase_quantity' => '1.000',
                'conversion_quantity' => '1.000',
                'purchase_unit' => 'kg',
                'usage_unit' => 'kg',
                'purchase_price' => '100.00',
                'stock_quantity' => '5.000',
                'reorder_level' => '0.000',
            ]);
        }

        $user->inventoryItems()->create([
            'name' => 'Packaging item',
            'category' => 'Packaging',
            'purchase_quantity' => '1.000',
            'conversion_quantity' => '1.000',
            'purchase_unit' => 'pack',
            'usage_unit' => 'pcs',
            'purchase_price' => '100.00',
            'stock_quantity' => '5.000',
            'reorder_level' => '0.000',
        ]);
        $otherUser->inventoryItems()->create([
            'name' => 'Secret Ingredient',
            'category' => 'Ingredients',
            'purchase_quantity' => '1.000',
            'conversion_quantity' => '1.000',
            'purchase_unit' => 'kg',
            'usage_unit' => 'kg',
            'purchase_price' => '100.00',
            'stock_quantity' => '5.000',
            'reorder_level' => '0.000',
        ]);

        $this->actingAs($user)
            ->get(route('cost-analysis.ingredients', ['search' => 'Ingredient']))
            ->assertOk()
            ->assertSee('21 ingredients')
            ->assertSee('Ingredient 01')
            ->assertSee('Ingredient 20')
            ->assertSee('Ingredient 21')
            ->assertDontSee('Packaging item')
            ->assertDontSee('Secret Ingredient')
            ->assertSee('Scrollable ingredient list')
            ->assertDontSee('pagination');
    }
}
