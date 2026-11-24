<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_visiting_the_homepage_is_redirected_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_authenticated_user_visiting_the_homepage_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard', absolute: false));
    }
}
