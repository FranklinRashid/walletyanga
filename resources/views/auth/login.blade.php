<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="mx-auto grid min-h-screen w-full max-w-5xl items-center gap-10 px-6 pb-10 pt-16 lg:grid-cols-2">
            <section>
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-300">Wallet Yanga</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-black sm:text-5xl">Welcome back</h1>
                <p class="mt-4 max-w-xl text-base leading-7 text-zinc-600">
                    Access your ledger-backed wallet dashboard.
                </p>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-6">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Login</h2>
                        <p class="mt-1 text-sm text-zinc-500">Use your registered email address.</p>
                    </div>
                    <a href="{{ route('register') }}" class="text-sm font-medium text-emerald-300 hover:text-emerald-200">Register</a>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="text-sm font-medium text-zinc-700">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-black outline-none focus:border-emerald-400">
                        @error('email')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="text-sm font-medium text-zinc-700">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-black outline-none focus:border-emerald-400">
                        @error('password')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-zinc-600">
                        <input type="checkbox" name="remember" class="rounded border-zinc-300 bg-zinc-50 text-emerald-400">
                        Remember me
                    </label>

                    <button type="submit" class="w-full rounded-md bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 hover:bg-emerald-300">
                        Login
                    </button>
                </form>
            </section>
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
