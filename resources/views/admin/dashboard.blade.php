<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-wide flex flex-col gap-8">
            <nav class="wy-topbar flex-wrap">
                <div>
                    <p class="text-sm font-semibold text-black">Wallet Yanga Admin</p>
                    <p class="text-sm text-zinc-500">{{ auth()->user()->name }} · {{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.customers.index') }}" class="wy-button wy-button-secondary">Customers</a>
                    <a href="{{ route('admin.kyc.index') }}" class="wy-button wy-button-secondary">KYC queue</a>
                    @if (auth()->user()->role === 'super_admin')
                        <a href="{{ route('admin.staff.index') }}" class="wy-button wy-button-secondary">Staff</a>
                    @endif
                    <a href="{{ route('wallet.dashboard') }}" class="wy-button wy-button-secondary">User Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="wy-button wy-button-secondary">Logout</button>
                    </form>
                </div>
            </nav>

            <section>
                <p class="wy-kicker">Operations cockpit</p>
                <h1 class="wy-hero-title mt-3">Admin Dashboard</h1>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <a href="{{ route('admin.customers.index') }}" class="wy-card hover:bg-zinc-50">
                    <p class="text-sm text-zinc-500">Users</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $usersCount }}</p>
                    <p class="mt-2 text-xs text-zinc-500">Open customers →</p>
                </a>
                <div class="wy-card">
                    <p class="text-sm text-zinc-500">Staff</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $staffCount }}</p>
                </div>
                <a href="{{ route('admin.kyc.index') }}" class="wy-card bg-amber-50 hover:bg-amber-100/70">
                    <p class="text-sm text-amber-700">KYC pending</p>
                    <p class="mt-2 text-3xl font-semibold text-amber-900">{{ $pendingKycCount }}</p>
                    <p class="mt-2 text-xs text-amber-700">Open queue →</p>
                </a>
                <div class="wy-card">
                    <p class="text-sm text-zinc-500">Wallets</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $walletsCount }}</p>
                </div>
                <div class="wy-card">
                    <p class="text-sm text-zinc-500">Deposits</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $depositsCount }}</p>
                </div>
                <div class="wy-card">
                    <p class="text-sm text-zinc-500">Cards</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $cardsCount }}</p>
                </div>
            </section>

            <section class="wy-table-card">
                <div class="border-b border-zinc-200 px-4 py-3">
                    <h2 class="font-semibold">Recent Ledger Transactions</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Posted</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            @forelse ($ledgerTransactions as $transaction)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $transaction->reference }}</td>
                                    <td class="px-4 py-3">{{ $transaction->type }}</td>
                                    <td class="px-4 py-3">{{ $transaction->status }}</td>
                                    <td class="px-4 py-3 text-zinc-500">{{ optional($transaction->posted_at)->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-zinc-500">No ledger transactions posted yet.</td>
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
