<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-6 py-8">
            <nav class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200 pb-5">
                <div>
                    <p class="text-sm font-semibold text-black">Wallet Yanga Admin</p>
                    <p class="text-sm text-zinc-500">{{ auth()->user()->name }} · {{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.kyc.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400">KYC queue</a>
                    @if (auth()->user()->role === 'super_admin')
                        <a href="{{ route('admin.staff.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400">Staff</a>
                    @endif
                    <a href="{{ route('wallet.dashboard') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400">User Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400">Logout</button>
                    </form>
                </div>
            </nav>

            <section>
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-300">Operations cockpit</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-black">Admin Dashboard</h1>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <p class="text-sm text-zinc-500">Users</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $usersCount }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <p class="text-sm text-zinc-500">Staff</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $staffCount }}</p>
                </div>
                <a href="{{ route('admin.kyc.index') }}" class="rounded-lg border border-amber-900/60 bg-amber-950/30 p-4 hover:border-amber-700/80">
                    <p class="text-sm text-amber-200/90">KYC pending</p>
                    <p class="mt-2 text-3xl font-semibold text-amber-100">{{ $pendingKycCount }}</p>
                    <p class="mt-2 text-xs text-amber-200/70">Open queue →</p>
                </a>
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <p class="text-sm text-zinc-500">Wallets</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $walletsCount }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <p class="text-sm text-zinc-500">Deposits</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $depositsCount }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-4">
                    <p class="text-sm text-zinc-500">Cards</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $cardsCount }}</p>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white">
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
                        <tbody class="divide-y divide-zinc-800">
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
