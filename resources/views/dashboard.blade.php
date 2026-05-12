<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 text-zinc-100 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-6 py-8">
            <nav class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-800 pb-5">
                <div>
                    <p class="text-sm font-semibold text-white">Wallet Yanga</p>
                    <p class="text-sm text-zinc-400">{{ $user->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-200 hover:border-zinc-500">
                        Logout
                    </button>
                </form>
            </nav>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-300">Malawi fintech core</p>
                    <h1 class="mt-3 text-4xl font-semibold tracking-tight text-white sm:text-5xl">Hello, {{ $user->name }}</h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-300">
                        Your account is active with KYC status: <span class="font-semibold text-white">{{ str_replace('_', ' ', $user->kycProfile?->status ?? 'not started') }}</span>.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                        <p class="text-sm text-zinc-400">Wallets</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $wallets->count() }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                        <p class="text-sm text-zinc-400">Deposits</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $deposits->count() }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                        <p class="text-sm text-zinc-400">Cards</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $cards->count() }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                        <p class="text-sm text-zinc-400">Ledger Txns</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $ledgerTransactions->count() }}</p>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-lg border border-zinc-800 bg-zinc-900">
                    <div class="border-b border-zinc-800 px-4 py-3">
                        <h2 class="font-semibold">Wallet Ledger</h2>
                    </div>
                    <div class="divide-y divide-zinc-800">
                        @forelse ($wallets as $wallet)
                            <div class="px-4 py-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span>{{ $wallet->currency }} {{ $wallet->type }}</span>
                                    <span class="font-mono">{{ number_format($wallet->cached_balance_minor / 100, 2) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">{{ $wallet->status }}</p>
                            </div>
                        @empty
                            <p class="px-4 py-6 text-sm text-zinc-500">No wallets have been created yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-800 bg-zinc-900">
                    <div class="border-b border-zinc-800 px-4 py-3">
                        <h2 class="font-semibold">Paychangu Deposits</h2>
                    </div>
                    <div class="divide-y divide-zinc-800">
                        @forelse ($deposits as $deposit)
                            <div class="px-4 py-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="truncate font-mono text-xs">{{ $deposit->tx_ref }}</span>
                                    <span>{{ number_format($deposit->amount_minor / 100, 2) }} {{ $deposit->currency }}</span>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">{{ $deposit->status }}</p>
                            </div>
                        @empty
                            <p class="px-4 py-6 text-sm text-zinc-500">No Paychangu deposit intents yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-800 bg-zinc-900">
                    <div class="border-b border-zinc-800 px-4 py-3">
                        <h2 class="font-semibold">Virtual Cards</h2>
                    </div>
                    <div class="divide-y divide-zinc-800">
                        @forelse ($cards as $card)
                            <div class="px-4 py-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span>{{ $card->brand ?? 'Card' }}</span>
                                    <span class="font-mono">{{ $card->masked_pan ?? 'tokenized' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">{{ $card->status }} · {{ $card->currency }}</p>
                            </div>
                        @empty
                            <p class="px-4 py-6 text-sm text-zinc-500">No virtual cards have been issued yet.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-800 bg-zinc-900">
                <div class="border-b border-zinc-800 px-4 py-3">
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
                                    <td class="px-4 py-3 text-zinc-400">{{ optional($transaction->posted_at)->diffForHumans() }}</td>
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
    </body>
</html>
