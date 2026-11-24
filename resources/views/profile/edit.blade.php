@extends('layouts.mainlayout')

@section('title', 'My Profile | ' . config('app.name', 'FoodoCosting'))
@section('page-title', 'My Profile')

@section('content')
    <section class="section profile-page">
        <div class="profile-hero mb-3">
            <span class="profile-hero-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            <div class="profile-hero-copy">
                <span class="profile-eyebrow">ACCOUNT SETTINGS</span>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
            </div>
            <span class="profile-account-badge">FoodoCosting account</span>
        </div>

        @if (session('status') === 'profile-updated')
            <div class="alert alert-success py-2 small" role="status">Profile information saved.</div>
        @elseif (session('status') === 'password-updated')
            <div class="alert alert-success py-2 small" role="status">Password updated successfully.</div>
        @elseif (session('status') === 'verification-link-sent')
            <div class="alert alert-success py-2 small" role="status">A new verification link has been sent.</div>
        @endif

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="profile-settings-card h-100">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
            <div class="col-xl-6">
                <div class="profile-settings-card h-100">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
            <div class="col-12">
                <div class="profile-danger-card">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .profile-page { --profile-green:#0b5d3b; --profile-gold:#f2b705; }
        .profile-hero { display:flex; align-items:center; gap:15px; padding:22px 24px; color:#fff; background:linear-gradient(115deg,#073d29,#0b5d3b 58%,#248653); border-radius:17px; box-shadow:0 12px 26px rgba(11,93,59,.12); }
        .profile-hero-avatar { display:grid; width:54px; height:54px; flex:0 0 54px; place-items:center; color:#123226; background:#a7edca; border:3px solid rgba(255,255,255,.25); border-radius:50%; font-size:1.35rem; font-weight:750; }
        .profile-hero-copy { min-width:0; flex:1; }
        .profile-eyebrow { color:#bce9cf; font-size:.65rem; font-weight:750; letter-spacing:.13em; }
        .profile-hero h2 { overflow:hidden; margin:4px 0 2px; color:#fff; font-size:1.3rem; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .profile-hero p { overflow:hidden; margin:0; color:rgba(255,255,255,.75); font-size:.78rem; text-overflow:ellipsis; white-space:nowrap; }
        .profile-account-badge { flex:0 0 auto; padding:7px 10px; color:#fff; background:rgba(255,255,255,.11); border:1px solid rgba(255,255,255,.17); border-radius:999px; font-size:.68rem; }
        .profile-settings-card,.profile-danger-card { padding:20px; background:#fff; border:1px solid #e8eee9; border-radius:15px; box-shadow:0 4px 16px rgba(19,45,31,.035); }
        .profile-section-heading { margin-bottom:18px; }
        .profile-section-heading h3 { margin:0; color:#183528; font-size:1rem; font-weight:700; }
        .profile-section-heading p { margin:5px 0 0; color:#87938d; font-size:.77rem; }
        .profile-page .form-label { margin-bottom:6px; color:#38483f; font-size:.78rem; font-weight:650; }
        .profile-page .form-control { min-height:41px; border-color:#dce5df; border-radius:8px; font-size:.86rem; box-shadow:none; }
        .profile-page .form-control:focus { border-color:#4ca77a; box-shadow:0 0 0 .2rem rgba(76,167,122,.12); }
        .profile-save-button { min-width:115px; color:#fff; background:#0b5d3b; border-color:#0b5d3b; }
        .profile-save-button:hover { color:#fff; background:#08482f; border-color:#08482f; }
        .profile-danger-card { border-color:#f0d9d7; }
        .profile-danger-card h3 { margin:0; color:#923b35; font-size:.96rem; font-weight:700; }
        .profile-danger-card p { margin:5px 0 0; color:#87938d; font-size:.77rem; }
        @media (max-width:575.98px) {
            .profile-hero { flex-wrap:wrap; padding:18px; }
            .profile-account-badge { margin-left:69px; }
            .profile-settings-card,.profile-danger-card { padding:16px; }
        }
    </style>
@endpush
