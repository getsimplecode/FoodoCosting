<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->accountThrottleKey());
            RateLimiter::hit($this->ipThrottleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->accountThrottleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $ipKey = $this->throttleKey();
        $accountKey = $this->accountThrottleKey();
        $globalIpKey = $this->ipThrottleKey();

        if (! RateLimiter::tooManyAttempts($ipKey, 5)
            && ! RateLimiter::tooManyAttempts($accountKey, 10)
            && ! RateLimiter::tooManyAttempts($globalIpKey, 60)) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            RateLimiter::tooManyAttempts($ipKey, 5) ? RateLimiter::availableIn($ipKey) : 0,
            RateLimiter::tooManyAttempts($accountKey, 10) ? RateLimiter::availableIn($accountKey) : 0,
            RateLimiter::tooManyAttempts($globalIpKey, 60) ? RateLimiter::availableIn($globalIpKey) : 0
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    private function accountThrottleKey(): string
    {
        return 'login:account:'.hash('sha256', Str::lower((string) $this->input('email')));
    }

    private function ipThrottleKey(): string
    {
        return 'login:ip:'.$this->ip();
    }
}
