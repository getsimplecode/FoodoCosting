<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InventoryIngredientsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_item_can_be_added_and_its_unit_cost_is_calculated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('inventory.store'), [
                'name' => 'Rice',
                'category' => 'Ingredients',
                'purchase_quantity' => '1.000',
                'conversion_quantity' => '25.000',
                'purchase_unit' => 'sack',
                'usage_unit' => 'kg',
                'purchase_price' => '2500.00',
                'reorder_level' => '5.000',
                'initial_stock' => '25.000',
                'expiration_date' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect(route('inventory.index'));

        $item = $user->inventoryItems()->firstOrFail();

        $this->assertSame('Rice', $item->name);
        $this->assertSame(100.0, $item->unitCost());
        $this->assertSame('25.000', $item->stock_quantity);
        $batch = $item->batches()->firstOrFail();
        $this->assertMatchesRegularExpression('/^LOT-\d{8}-[A-Z0-9]{6}$/', $batch->batch_number);
        $this->assertSame('25.000', $batch->quantity);
    }

    public function test_duplicate_name_adds_a_new_purchase_batch_instead_of_a_new_item(): void
    {
        $user = User::factory()->create();
        $existing = $user->inventoryItems()->create([
            'name' => 'Bombay',
            'category' => 'Ingredients',
            'purchase_quantity' => '1.000',
            'conversion_quantity' => '25.000',
            'purchase_unit' => 'sack',
            'usage_unit' => 'kg',
            'purchase_price' => '2500.00',
            'stock_quantity' => '10.000',
            'reorder_level' => '5.000',
        ]);
        $existing->batches()->create([
            'user_id' => $user->id,
            'batch_number' => 'LOT-OLD',
            'quantity' => '10.000',
            'initial_quantity' => '10.000',
            'unit_cost' => '100.00',
            'expiration_date' => now()->addMonth()->toDateString(),
            'received_at' => now()->subWeek(),
        ]);

        $this->actingAs($user)
            ->post(route('inventory.store'), [
                'name' => '  bombay ',
                'category' => 'Ingredients',
                'purchase_quantity' => '1.000',
                'conversion_quantity' => '25.000',
                'purchase_unit' => 'sack',
                'usage_unit' => 'kg',
                'purchase_price' => '3000.00',
                'reorder_level' => '5.000',
                'initial_stock' => '25.000',
                'expiration_date' => now()->addMonths(2)->toDateString(),
            ])
            ->assertRedirect(route('inventory.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, $user->inventoryItems()->count());
        $this->assertDatabaseHas('inventory_items', [
            'id' => $existing->id,
            'stock_quantity' => '35.000',
            'purchase_price' => '3000.00',
        ]);
        $this->assertDatabaseHas('inventory_batches', [
            'inventory_item_id' => $existing->id,
            'quantity' => '25.000',
            'unit_cost' => '120.00',
        ]);
    }

    public function test_unit_cost_uses_unrecorded_old_stock_before_new_fefo_batch(): void
    {
        $user = User::factory()->create();
        $item = $user->inventoryItems()->create([
            'name' => 'Bombay',
            'category' => 'Ingredients',
            'purchase_quantity' => '1.000',
            'conversion_quantity' => '25.000',
            'purchase_unit' => 'sack',
            'usage_unit' => 'kg',
            'purchase_price' => '900.00',
            'stock_quantity' => '2.000',
            'reorder_level' => '0.500',
        ]);
        $item->batches()->create([
            'user_id' => $user->id,
            'batch_number' => 'LOT-NEW',
            'quantity' => '1.000',
            'initial_quantity' => '1.000',
            'unit_cost' => '1200.00',
            'expiration_date' => now()->addMonth()->toDateString(),
            'received_at' => now(),
        ]);

        $this->assertSame(36.0, $item->unitCost());
    }

    public function test_users_only_update_and_delete_their_own_inventory_items(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = $owner->inventoryItems()->create([
            'name' => 'Chicken',
            'category' => 'Ingredients',
            'purchase_quantity' => '1.000',
            'conversion_quantity' => '1.000',
            'purchase_unit' => 'kg',
            'usage_unit' => 'g',
            'purchase_price' => '250.00',
            'stock_quantity' => '10.000',
            'reorder_level' => '2.000',
        ]);

        $this->actingAs($otherUser)
            ->put(route('inventory.update', $item), [
                'name' => 'Changed',
                'category' => 'Ingredients',
                'purchase_quantity' => '1.000',
                'conversion_quantity' => '1.000',
                'purchase_unit' => 'kg',
                'usage_unit' => 'g',
                'purchase_price' => '300.00',
                'stock_quantity' => '10.000',
                'reorder_level' => '2.000',
            ])
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->delete(route('inventory.destroy', $item))
            ->assertNotFound();

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'name' => 'Chicken']);
    }

    public function test_stock_movements_update_stock_and_keep_a_history(): void
    {
        $user = User::factory()->create();
        $item = $user->inventoryItems()->create([
            'name' => 'Lunch Box',
            'category' => 'Packaging',
            'purchase_quantity' => '100.000',
            'purchase_unit' => 'pack',
            'usage_unit' => 'pcs',
            'purchase_price' => '850.00',
            'stock_quantity' => '100.000',
            'reorder_level' => '10.000',
        ]);

        $this->actingAs($user)
            ->post(route('inventory.movements.store', $item), [
                'type' => 'damaged',
                'quantity' => '3.000',
                'reason' => 'Dented boxes',
                'occurred_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'stock_quantity' => '97.000',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'user_id' => $user->id,
            'type' => 'damaged',
            'quantity' => '3.000',
            'previous_stock' => '100.000',
            'resulting_stock' => '97.000',
        ]);
    }

    public function test_stock_out_uses_the_earliest_expiration_batch_first(): void
    {
        $user = User::factory()->create();
        $item = $user->inventoryItems()->create([
            'name' => 'Chicken',
            'category' => 'Ingredients',
            'purchase_quantity' => '1.000',
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
            'expiration_date' => now()->addDays(3)->toDateString(),
            'received_at' => now()->subDays(2),
        ]);
        $newer = $item->batches()->create([
            'user_id' => $user->id,
            'batch_number' => 'LOT-NEW',
            'quantity' => '10.000',
            'initial_quantity' => '10.000',
            'expiration_date' => now()->addDays(10)->toDateString(),
            'received_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('inventory.movements.store', $item), [
                'type' => 'stock_out',
                'quantity' => '7.000',
                'reason' => 'Kitchen use',
                'occurred_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertSame('0.000', $older->fresh()->quantity);
        $this->assertSame('8.000', $newer->fresh()->quantity);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'stock_quantity' => '8.000',
        ]);
    }

    public function test_stock_out_cannot_exceed_available_stock(): void
    {
        $user = User::factory()->create();
        $item = $user->inventoryItems()->create([
            'name' => 'Rice',
            'category' => 'Ingredients',
            'purchase_quantity' => '25.000',
            'purchase_unit' => 'sack',
            'usage_unit' => 'kg',
            'purchase_price' => '2500.00',
            'stock_quantity' => '5.000',
            'reorder_level' => '2.000',
        ]);

        $this->actingAs($user)
            ->from(route('inventory.index'))
            ->post(route('inventory.movements.store', $item), [
                'type' => 'stock_out',
                'quantity' => '6.000',
                'reason' => 'Kitchen use',
                'occurred_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors(['quantity']);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'stock_quantity' => '5.000',
        ]);
    }

    public function test_inventory_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('inventory.store'), [
                'name' => '',
                'purchase_quantity' => '0',
                'conversion_quantity' => '0',
                'purchase_unit' => '',
                'usage_unit' => '',
                'purchase_price' => '-1',
                'reorder_level' => '-1',
            ])
            ->assertSessionHasErrors([
                'name',
                'purchase_quantity',
                'conversion_quantity',
                'purchase_unit',
                'usage_unit',
                'purchase_price',
                'reorder_level',
            ]);
    }

    public function test_inventory_table_has_a_viewport_limited_scroll_area(): void
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
            'reorder_level' => '1.000',
        ]);

        $this->actingAs($user)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Scrollable inventory table')
            ->assertSee('max-height:calc(100vh - 330px)', false)
            ->assertSee('position:sticky;top:0;z-index:2', false)
            ->assertSee('aria-label="View batches for Chicken"', false)
            ->assertSee('aria-label="Manage stock for Chicken"', false)
            ->assertSee('aria-label="Edit Chicken"', false)
            ->assertSee('aria-label="Delete Chicken"', false)
            ->assertSee('Chicken');
    }

    public function test_sample_ingredient_seeder_creates_twenty_stocked_items_idempotently(): void
    {
        $user = User::factory()->create();

        $this->seed(InventoryIngredientsSeeder::class);
        $this->seed(InventoryIngredientsSeeder::class);

        $items = $user->inventoryItems()->get();

        $this->assertCount(20, $items);
        $this->assertSame(400.0, (float) $items->sum('stock_quantity'));
        $this->assertSame(20, $user->inventoryMovements()->count());
        $this->assertSame(20, $items->sum(fn ($item) => $item->batches()->count()));
        $this->assertSame(20, $items->filter(
            fn ($item) => $item->category === 'Ingredients' && (float) $item->stock_quantity === 20.0
        )->count());
        $this->assertSame(1, User::count());
    }
}
