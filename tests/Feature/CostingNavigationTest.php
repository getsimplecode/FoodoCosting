<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_costing_sections_can_be_opened_from_the_sidebar(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            'inventory.index' => 'inventory.index',
            'cost-analysis.index' => 'cost-analysis.index',
            'cost-analysis.reports.index' => 'reports.index',
        ] as $route => $view) {
            $this->get(route($route))
                ->assertOk()
                ->assertViewIs($view)
                ->assertSee(route('cost-analysis.reports.index'))
                ->assertSee('Saved Reports');
        }
    }

    public function test_costing_sections_require_authentication(): void
    {
        $this->get(route('inventory.index'))
            ->assertRedirect(route('login'));
    }
}
