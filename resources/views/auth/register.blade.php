<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Create Account · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="mx-auto grid min-h-screen w-full max-w-6xl items-center gap-10 px-6 pb-10 pt-16 lg:grid-cols-[0.9fr_1.1fr]">
            <section>
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-300">Wallet Yanga</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-black sm:text-5xl">Create your Malawi wallet account</h1>
                <p class="mt-4 max-w-xl text-base leading-7 text-zinc-600">
                    Sign up to start onboarding for MWK deposits, USD conversion, and virtual card access.
                </p>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-6">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Register</h2>
                        <p class="mt-1 text-sm text-zinc-500">Your account starts with basic KYC status.</p>
                    </div>
                    <a href="{{ route('login') }}" class="text-sm font-medium text-emerald-300 hover:text-emerald-200">Login</a>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="text-sm font-medium text-zinc-700">Full name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-black outline-none focus:border-emerald-400">
                        @error('name')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="text-sm font-medium text-zinc-700">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-black outline-none focus:border-emerald-400">
                        @error('email')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="phone" class="text-sm font-medium text-zinc-700">Malawi phone number</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="+265..." class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-black outline-none focus:border-emerald-400">
                        @error('phone')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="text-sm font-medium text-zinc-700">Password</label>
                            <input id="password" type="password" name="password" required autocomplete="new-password" class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-black outline-none focus:border-emerald-400">
                            @error('password')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="text-sm font-medium text-zinc-700">Confirm password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-black outline-none focus:border-emerald-400">
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-md bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 hover:bg-emerald-300">
                        Create account
                    </button>
                </form>
            </section>
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
