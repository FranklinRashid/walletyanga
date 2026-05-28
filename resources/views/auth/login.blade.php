<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-compact grid items-center gap-10 lg:grid-cols-2">
            <section>
                <p class="wy-kicker">Wallet Yanga</p>
                <h1 class="wy-hero-title mt-3">Welcome back</h1>
                <p class="wy-subtitle max-w-xl text-base">
                    Access your ledger-backed wallet dashboard.
                </p>
            </section>

            <section class="wy-panel">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Login</h2>
                        <p class="mt-1 text-sm text-zinc-500">Use your registered email address.</p>
                    </div>
                    <a href="{{ route('register') }}" class="text-sm font-semibold text-[#4cd14f]">Register</a>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="wy-label">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="wy-field text-sm">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="wy-label">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="wy-field text-sm">
                        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-zinc-600">
                        <input type="checkbox" name="remember" class="rounded border-zinc-300 bg-zinc-50 text-emerald-400">
                        Remember me
                    </label>

                    <button type="submit" class="wy-button wy-button-primary w-full">
                        Login
                    </button>
                </form>
            </section>
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
