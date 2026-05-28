<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Customers · Admin · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-wide flex flex-col gap-8">
            <nav class="wy-topbar flex-wrap">
                <div>
                    <p class="text-sm font-semibold text-black">Wallet Yanga Admin</p>
                    <p class="text-sm text-zinc-500">Customers · {{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="wy-button wy-button-secondary">Dashboard</a>
                    <a href="{{ route('admin.kyc.index') }}" class="wy-button wy-button-secondary">KYC queue</a>
                    @if (auth()->user()->role === 'super_admin')
                        <a href="{{ route('admin.staff.index') }}" class="wy-button wy-button-secondary">Staff</a>
                    @endif
                </div>
            </nav>

            <section class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="wy-page-title">Customers</h1>
                    <p class="mt-2 text-sm text-zinc-500">Wallet, card, KYC, and support visibility.</p>
                </div>
                <form method="GET" action="{{ route('admin.customers.index') }}" class="grid w-full gap-2 sm:w-auto sm:grid-cols-[260px_auto]">
                    <input name="q" value="{{ $search }}" placeholder="Search name or email" class="wy-field text-sm">
                    <button type="submit" class="wy-button wy-button-primary">Search</button>
                </form>
            </section>

            <section class="wy-table-card">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">KYC</th>
                                <th class="px-4 py-3">Wallets</th>
                                <th class="px-4 py-3">Cards</th>
                                <th class="px-4 py-3">Joined</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            @forelse ($customers as $customer)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-black">{{ $customer->name }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $customer->email }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="wy-chip">{{ $customer->kycProfile?->status ?? 'not_started' }}</span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-zinc-700">{{ $customer->wallets_count }}</td>
                                    <td class="px-4 py-3 font-mono text-zinc-700">{{ $customer->virtual_cards_count }}</td>
                                    <td class="px-4 py-3 text-zinc-500">{{ $customer->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="wy-button wy-button-primary px-3 py-1.5 text-xs">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No customers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($customers->hasPages())
                    <div class="border-t border-zinc-200 px-4 py-3">
                        {{ $customers->links() }}
                    </div>
                @endif
            </section>
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
