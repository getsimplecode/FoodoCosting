@extends('layouts.login')

@section('title', 'Sign in | ' . config('app.name', 'FoodoCosting'))

@section('content')
    <div class="auth-heading">
        <span>ACCOUNT ACCESS</span>
        <h2>Welcome back</h2>
        <p>Sign in to continue to your kitchen workspace.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success small py-2" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label auth-label">Email address</label>
            <input id="email" class="form-control auth-input @error('email') is-invalid @enderror"
                   type="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" placeholder="you@example.com">
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label auth-label">Password</label>
            <input id="password" class="form-control auth-input @error('password') is-invalid @enderror"
                   type="password" name="password" required autocomplete="current-password"
                   placeholder="Enter your password">
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <label for="remember_me" class="form-check d-flex align-items-center gap-2 mb-0">
                <input id="remember_me" type="checkbox" class="form-check-input auth-check mt-0"
                       name="remember" @checked(old('remember'))>
                <span class="small text-secondary">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="auth-forgot" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn auth-submit w-100">
            Sign in
            <svg aria-hidden="true" focusable="false" class="ms-1" width="17" height="17" viewBox="0 0 24 24" fill="none" style="vertical-align:-4px">
                <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>
@endsection
