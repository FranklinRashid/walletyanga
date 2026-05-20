<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Convert MWK to USD · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="mx-auto min-h-screen w-full max-w-5xl px-4 pb-20 pt-16 sm:px-6 lg:px-8">
            <nav class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('wallet.dashboard') }}" class="text-sm font-semibold text-emerald-300 hover:text-emerald-200">Wallet Yanga</a>
                    <h1 class="mt-1 text-2xl font-semibold text-black">Convert</h1>
                </div>
                <a href="{{ route('wallet.dashboard') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400">Back</a>
            </nav>

            <div class="mt-5 space-y-3">
                @if (session('status'))
                    <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>

            <section class="mt-5 grid gap-4 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
                <div class="space-y-4">
                    <section class="rounded-lg border border-zinc-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase text-zinc-500">Available balances</p>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4">
                                <p class="text-xs uppercase text-zinc-500">MWK Balance</p>
                                <p class="mt-2 font-mono text-2xl font-semibold text-black">{{ number_format($mwkWallet->cached_balance_minor / 100, 2) }}</p>
                            </div>
                            <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4">
                                <p class="text-xs uppercase text-zinc-500">USD Balance</p>
                                <p class="mt-2 font-mono text-2xl font-semibold text-black">{{ number_format($usdWallet->cached_balance_minor / 100, 2) }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase text-zinc-500">Reference rate</p>
                        <p class="mt-2 text-2xl font-semibold text-black">1 USD ≈ {{ number_format($spot['rate'], 2) }} MWK</p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500">
                            @if ($spot['as_of'])
                                As of {{ $spot['as_of'] }} ·
                            @endif
                            Source: {{ $spot['source'] === 'currency-api' ? 'live market data' : 'configured fallback' }}
                        </p>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="rounded-lg border border-zinc-200 bg-white p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-black">Create FX quote</p>
                                <p class="mt-1 text-sm text-zinc-500">Lock a MWK to USD rate for five minutes.</p>
                            </div>
                            <span class="rounded-full border border-zinc-300 px-2 py-1 text-xs text-zinc-600">MWK → USD</span>
                        </div>

                        <form method="POST" action="{{ route('wallet.convert.quote') }}" class="mt-5 space-y-4">
                            @csrf
                            <div>
                                <label for="mwk_amount" class="block text-sm font-medium text-zinc-600">MWK amount</label>
                                <input id="mwk_amount" name="mwk_amount" type="number" inputmode="decimal" step="0.01" min="{{ number_format($minMwkMinor / 100, 2, '.', '') }}" required
                                    value="{{ old('mwk_amount') }}"
                                    class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-4 text-2xl font-semibold text-black outline-none focus:border-emerald-400"
                                    placeholder="0.00">
                            </div>
                            <p class="text-xs text-zinc-500">Minimum conversion: {{ number_format($minMwkMinor / 100, 2) }} MWK.</p>
                            <button type="submit" class="w-full rounded-md bg-emerald-400 px-4 py-3 text-sm font-semibold text-zinc-950 hover:bg-emerald-300">
                                Generate quote
                            </button>
                        </form>
                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-4 sm:p-5">
                        <h2 class="font-semibold text-black">Current quote</h2>
                        @if ($activeQuote)
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-zinc-500">You convert</span>
                                        <span class="font-semibold text-black">{{ number_format($activeQuote->from_amount_minor / 100, 2) }} MWK</span>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-4">
                                        <span class="text-zinc-500">You receive</span>
                                        <span class="font-semibold text-emerald-300">{{ number_format($activeQuote->to_amount_minor / 100, 2) }} USD</span>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-4">
                                        <span class="text-zinc-500">Effective rate</span>
                                        <span class="font-mono text-black">{{ number_format((float) $activeQuote->effective_rate, 4) }}</span>
                                    </div>
                                </div>
                                <p class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-100">
                                    Expires {{ $activeQuote->expires_at->diffForHumans() }} · Spread {{ $activeQuote->spread_bps }} bps
                                </p>
                                <form method="POST" action="{{ route('wallet.convert.accept', $activeQuote) }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-md bg-emerald-400 px-4 py-3 text-sm font-semibold text-zinc-950 hover:bg-emerald-300">
                                        Confirm conversion
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="mt-4 text-sm leading-6 text-zinc-500">No active quote. Enter a MWK amount to see the exact USD amount before confirming.</p>
                        @endif
                    </section>
                </div>
            </section>

            <section class="mt-4 rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-4 py-3">
                    <h2 class="font-semibold text-black">Recent conversions</h2>
                </div>
                <div class="divide-y divide-zinc-800">
                    @forelse ($recentConversions as $conversion)
                        <div class="px-4 py-4 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="font-semibold text-black">{{ number_format($conversion->quote->from_amount_minor / 100, 2) }} MWK → {{ number_format($conversion->quote->to_amount_minor / 100, 2) }} USD</span>
                                <span class="text-zinc-500">{{ $conversion->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">Rate {{ number_format((float) $conversion->quote->effective_rate, 4) }} · {{ $conversion->status }}</p>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-sm text-zinc-500">No conversions yet.</p>
                    @endforelse
                </div>
            </section>
        </main>
        @include('partials.mobile-nav')
    </body>
</html>
