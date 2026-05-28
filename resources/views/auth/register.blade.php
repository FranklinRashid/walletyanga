<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Create Account · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-compact grid items-center gap-10 lg:grid-cols-[0.9fr_1.1fr]">
            <section>
                <p class="wy-kicker">Wallet Yanga</p>
                <h1 class="wy-hero-title mt-3">Create your Malawi wallet account</h1>
                <p class="wy-subtitle max-w-xl text-base">
                    Sign up to start onboarding for MWK deposits, USD conversion, and virtual card access.
                </p>
            </section>

            <section class="wy-panel">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Register</h2>
                        <p class="mt-1 text-sm text-zinc-500">Your account starts with basic KYC status.</p>
                    </div>
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-[#4cd14f]">Login</a>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="wy-label">Full name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="wy-field text-sm">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="wy-label">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="wy-field text-sm">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="phone" class="wy-label">Malawi phone number</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="+265..." class="wy-field text-sm">
                        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="wy-label">Password</label>
                            <input id="password" type="password" name="password" required autocomplete="new-password" class="wy-field text-sm">
                            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="wy-label">Confirm password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="wy-field text-sm">
                        </div>
                    </div>

                    <button type="submit" class="wy-button wy-button-primary w-full">
                        Create account
                    </button>
                </form>
            </section>
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
