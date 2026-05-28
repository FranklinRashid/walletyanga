<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>KYC queue · Admin · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-wide flex flex-col gap-8">
            <nav class="wy-topbar flex-wrap">
                <div>
                    <p class="text-sm font-semibold text-black">Wallet Yanga Admin</p>
                    <p class="text-sm text-zinc-500">KYC queue · {{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="wy-button wy-button-secondary">Dashboard</a>
                    @if (auth()->user()->role === 'super_admin')
                        <a href="{{ route('admin.staff.index') }}" class="wy-button wy-button-secondary">Staff</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="wy-button wy-button-secondary">Logout</button>
                    </form>
                </div>
            </nav>

            @if (session('status'))
                <p class="wy-alert wy-alert-success">{{ session('status') }}</p>
            @endif

            <section>
                <h1 class="wy-page-title">Pending KYC reviews</h1>
                <p class="mt-2 text-sm text-zinc-500">Customers who submitted details and ID documents for compliance.</p>
            </section>

            <section class="wy-table-card">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Submitted</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            @forelse ($pendingProfiles as $profile)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-black">{{ $profile->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-600">{{ $profile->user?->email ?? '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-500">{{ optional($profile->submitted_at)->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.kyc.show', $profile) }}" class="wy-button wy-button-primary px-3 py-1.5 text-xs">Review</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-zinc-500">No submissions awaiting review.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
