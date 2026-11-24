<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in | ' . config('app.name', 'FoodoCosting'))</title>
    @vite('resources/css/mainlayout.css')
    <style>
        .auth-shell {
            display:grid;
            min-width:320px;
            min-height:100vh;
            margin:0;
            padding:24px;
            place-items:center;
            color:#1d2b37;
            background:
                radial-gradient(circle at 12% 10%, rgba(80,190,130,.08), transparent 28rem),
                #f5f7f5;
        }
        .auth-card {
            width:min(100%, 420px);
            padding:34px;
            background:#fff;
            border:1px solid #e8eee9;
            border-radius:18px;
            box-shadow:0 18px 48px rgba(20,55,36,.09);
        }
        .auth-brand {
            display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            margin-bottom:30px;
            color:#1d2b37;
            text-decoration:none;
        }
        .auth-brand-mark {
            display:grid;
            width:42px;
            height:42px;
            place-items:center;
            color:#123226;
            background:linear-gradient(145deg,#abf2d0,#52d6a0);
            border-radius:13px;
        }
        .auth-brand-mark svg { width:24px; height:24px; }
        .auth-brand-copy strong,.auth-brand-copy small { display:block; }
        .auth-brand-copy strong { font-size:16px; font-weight:750; letter-spacing:-.35px; }
        .auth-brand-copy strong span { color:#278b63; }
        .auth-brand-copy small { margin-top:1px; color:#87938d; font-size:10px; }
        .auth-heading { margin-bottom:25px; text-align:center; }
        .auth-heading span { color:#278b63; font-size:.66rem; font-weight:750; letter-spacing:.14em; }
        .auth-heading h2 { margin:8px 0 6px; color:#183528; font-size:1.75rem; font-weight:750; letter-spacing:-.045em; }
        .auth-heading p { margin:0; color:#78857d; font-size:.86rem; }
        .auth-label { margin-bottom:7px; color:#38483f; font-size:.78rem; font-weight:650; }
        .auth-input { min-height:45px; border-color:#dce5df; border-radius:9px; font-size:.88rem; box-shadow:none !important; }
        .auth-input:focus { border-color:#4ca77a; }
        .auth-submit { min-height:45px; border:0; border-radius:9px; color:#fff; background:linear-gradient(100deg,#0b5d3b,#228451); font-size:.88rem; font-weight:700; box-shadow:0 7px 16px rgba(11,93,59,.15); }
        .auth-submit:hover { color:#fff; background:linear-gradient(100deg,#08482f,#176e42); }
        .auth-check { border-color:#b9c9bf; }
        .auth-check:checked { background-color:#0b7250; border-color:#0b7250; }
        .auth-forgot { color:#0b7250; font-size:.76rem; font-weight:600; text-decoration:none; }
        .auth-forgot:hover { color:#073d29; text-decoration:underline; }
        .auth-footer { margin:18px 0 0; color:#97a29a; font-size:.7rem; text-align:center; }
        @media (max-width:480px) {
            .auth-shell { padding:16px; }
            .auth-card { padding:27px 22px; border-radius:15px; }
        }
    </style>
</head>
<body class="auth-shell">
    <main class="auth-card">
        <a href="{{ route('login') }}" class="auth-brand" aria-label="FoodoCosting login">
            <span class="auth-brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M5 8.5h14l-1.2 8.1a2 2 0 0 1-2 1.7H8.2a2 2 0 0 1-2-1.7L5 8.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="M8 8.5 10 5m6 3.5L14 5M8.5 12h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="auth-brand-copy">
                <strong>Foodo<span>Costing</span></strong>
                <small>Kitchen management</small>
            </span>
        </a>

        @yield('content')
    </main>
    <p class="auth-footer">FoodoCosting <span aria-hidden="true">·</span> Kitchen management</p>
</body>
</html>
