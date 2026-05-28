<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Create Staff · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-compact grid items-center gap-10 lg:grid-cols-[0.85fr_1.15fr]">
            <section>
                <p class="wy-kicker">Internal access</p>
                <h1 class="wy-hero-title mt-3">Create staff account</h1>
                <p class="wy-subtitle max-w-xl text-base">
                    Staff accounts are created by a Super Admin and audited for compliance.
                </p>
            </section>

            <section class="wy-panel">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Staff Details</h2>
                        <p class="mt-1 text-sm text-zinc-500">Choose the least-privilege role for this user.</p>
                    </div>
                    <a href="{{ route('admin.staff.index') }}" class="text-sm font-semibold text-[#4cd14f]">Back</a>
                </div>

                <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="wy-label">Full name</label>
                        <input id="name" name="name" value="{{ old('name') }}" required autofocus class="wy-field text-sm">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="wy-label">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required class="wy-field text-sm">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="role" class="wy-label">Role</label>
                        <select id="role" name="role" required class="wy-field text-sm">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="wy-label">Password</label>
                            <input id="password" type="password" name="password" required class="wy-field text-sm">
                            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="wy-label">Confirm password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required class="wy-field text-sm">
                        </div>
                    </div>

                    <button type="submit" class="wy-button wy-button-primary w-full">
                        Create staff user
                    </button>
                </form>
            </section>
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
