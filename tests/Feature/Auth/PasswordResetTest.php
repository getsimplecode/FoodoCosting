<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_response_does_not_disclose_unknown_email_addresses(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $knownResponse = $this->post('/forgot-password', ['email' => $user->email]);
        $unknownResponse = $this->post('/forgot-password', ['email' => 'unknown@example.com']);

        $knownResponse->assertSessionHas('status', __(Password::RESET_LINK_SENT));
        $unknownResponse->assertSessionHas('status', __(Password::RESET_LINK_SENT));
    }

    public function test_reset_link_url_ignores_a_forwarded_host_header(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->withHeaders(['X-Forwarded-Host' => 'attacker.example'])
            ->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $this->assertSame('localhost', parse_url($notification->url, PHP_URL_HOST));

            return true;
        });
    }

    public function test_password_reset_requests_are_rate_limited_per_email_and_ip(): void
    {
        $email = 'unknown@example.com';

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post('/forgot-password', ['email' => $email])
                ->assertSessionHas('status', __(Password::RESET_LINK_SENT));
        }

        $this->post('/forgot-password', ['email' => $email])
            ->assertTooManyRequests();
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}
