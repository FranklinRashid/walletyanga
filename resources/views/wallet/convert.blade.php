<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Convert MWK to USD · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 text-zinc-100 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-4xl flex-col gap-8 px-6 py-8">
            <nav class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-800 pb-5">
                <div>
                    <p class="text-sm font-semibold text-white">Convert Money</p>
                    <p class="text-sm text-zinc-400">MWK wallet value to internal USD balance</p>
                </div>
                <a href="{{ route('wallet.dashboard') }}" class="rounded-md border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-200 hover:border-zinc-500">Back</a>
            </nav>

            @if (session('status'))
                <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section>
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-300">Wallet balances</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-white">Convert MWK to USD</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-300">
                    Review your available wallet balances before creating an FX quote.
                </p>
            </section>

            <section class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-5">
                    <p class="text-sm text-zinc-400">MWK Balance</p>
                    <p class="mt-3 text-4xl font-semibold text-white">
                        {{ number_format($mwkWallet->cached_balance_minor / 100, 2) }}
                    </p>
                    <p class="mt-2 text-sm text-zinc-500">Malawian Kwacha · {{ $mwkWallet->status }}</p>
                </div>

                <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-5">
                    <p class="text-sm text-zinc-400">USD Balance</p>
                    <p class="mt-3 text-4xl font-semibold text-white">
                        {{ number_format($usdWallet->cached_balance_minor / 100, 2) }}
                    </p>
                    <p class="mt-2 text-sm text-zinc-500">Internal USD value · {{ $usdWallet->status }}</p>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-800 bg-zinc-900 p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Reference rate</p>
                        <p class="mt-2 text-lg font-semibold text-white">1 USD ≈ {{ number_format($spot['rate'], 2) }} MWK</p>
                        <p class="mt-1 text-xs text-zinc-500">
                            @if ($spot['as_of'])
                                As of {{ $spot['as_of'] }} ·
                            @endif
                            Source: {{ $spot['source'] === 'currency-api' ? 'live market data' : 'configured fallback' }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
                <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-5">
                    <h2 class="font-semibold text-white">Create Quote</h2>
                    <form method="POST" action="{{ route('wallet.convert.quote') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="mwk_amount" class="block text-sm font-medium text-zinc-300">MWK amount</label>
                            <input id="mwk_amount" name="mwk_amount" type="number" inputmode="decimal" step="0.01" min="{{ number_format($minMwkMinor / 100, 2, '.', '') }}" required
                                value="{{ old('mwk_amount') }}"
                                class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400"
                                placeholder="0.00">
                        </div>
                        <p class="text-xs text-zinc-500">Minimum conversion: {{ number_format($minMwkMinor / 100, 2) }} MWK.</p>
                        <button type="submit" class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                            Generate USD quote
                        </button>
                    </form>
                </div>

                <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-5">
                    <h2 class="font-semibold text-white">Current Quote</h2>
                    @if ($activeQuote)
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-zinc-400">You convert</span>
                                <span class="font-semibold text-white">{{ number_format($activeQuote->from_amount_minor / 100, 2) }} MWK</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-zinc-400">You receive</span>
                                <span class="font-semibold text-white">{{ number_format($activeQuote->to_amount_minor / 100, 2) }} USD</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-zinc-400">Effective rate</span>
                                <span class="font-mono text-white">{{ number_format((float) $activeQuote->effective_rate, 4) }} MWK/USD</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-zinc-400">Spread</span>
                                <span class="text-white">{{ $activeQuote->spread_bps }} bps</span>
                            </div>
                            <p class="rounded-md border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-100">
                                Expires {{ $activeQuote->expires_at->diffForHumans() }}.
                            </p>
                            <form method="POST" action="{{ route('wallet.convert.accept', $activeQuote) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-md bg-emerald-400 px-4 py-2.5 text-sm font-semibold text-zinc-950 hover:bg-emerald-300">
                                    Confirm conversion
                                </button>
                            </form>
                        </div>
                    @else
                        <p class="mt-4 text-sm leading-6 text-zinc-400">No active quote. Enter a MWK amount to lock a rate for five minutes.</p>
                    @endif
                </div>
            </section>

            <section class="rounded-lg border border-zinc-800 bg-zinc-900">
                <div class="border-b border-zinc-800 px-4 py-3">
                    <h2 class="font-semibold">Recent Conversions</h2>
                </div>
                <div class="divide-y divide-zinc-800">
                    @forelse ($recentConversions as $conversion)
                        <div class="px-4 py-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span>{{ number_format($conversion->quote->from_amount_minor / 100, 2) }} MWK → {{ number_format($conversion->quote->to_amount_minor / 100, 2) }} USD</span>
                                <span class="text-zinc-400">{{ $conversion->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">Rate {{ number_format((float) $conversion->quote->effective_rate, 4) }} · {{ $conversion->status }}</p>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-sm text-zinc-500">No conversions yet.</p>
                    @endforelse
                </div>
            </section>
        </main>
    </body>
</html>
