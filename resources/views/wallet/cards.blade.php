<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Virtual Cards · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        @php
            $primaryCard = $revealedCard ?? $cards->first();
        @endphp

        <main class="wy-shell wy-shell-wide">
            <nav class="wy-topbar">
                <div class="min-w-0">
                    <a href="{{ route('wallet.dashboard') }}" class="wy-kicker">Wallet Yanga</a>
                    <h1 class="wy-page-title truncate">Virtual cards</h1>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('wallet.convert') }}" class="wy-button wy-button-secondary">
                        Convert
                    </a>
                    <a href="{{ route('wallet.add-money') }}" class="wy-button wy-button-primary">
                        Add money
                    </a>
                </div>
            </nav>

            <div class="mt-5 space-y-3">
                @if (session('status'))
                    <div class="wy-alert wy-alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="wy-alert wy-alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>

            <section class="mt-5 grid gap-4 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="wy-card">
                            <p class="text-xs font-medium uppercase text-zinc-500">USD Balance</p>
                            <p class="mt-2 font-mono text-2xl font-semibold text-black">{{ number_format($usdWallet->cached_balance_minor / 100, 2) }}</p>
                        </div>
                        <div class="wy-card">
                            <p class="text-xs font-medium uppercase text-zinc-500">MWK Balance</p>
                            <p class="mt-2 font-mono text-2xl font-semibold text-black">{{ number_format($mwkWallet->cached_balance_minor / 100, 2) }}</p>
                        </div>
                    </div>

                    <section class="wy-card overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-zinc-500">Active card</p>
                                    <p class="mt-1 text-lg font-semibold text-black">{{ $primaryCard?->nickname ?: 'Wallet Yanga Card' }}</p>
                                </div>
                                <span class="wy-chip">{{ $primaryCard?->status ?? 'ready' }}</span>
                            </div>

                            <div class="mt-4 aspect-[1.586/1] w-full overflow-hidden rounded-[16px] bg-[radial-gradient(circle_at_20%_0%,rgba(255,255,255,0.18),transparent_28%),linear-gradient(135deg,#202020_0%,#0d0d0d_55%,#252525_100%)] p-5 shadow-[0_16px_30px_rgba(0,0,0,0.18)]">
                                <div class="flex h-full flex-col justify-between">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-semibold uppercase text-white/60">Wallet Yanga</p>
                                            <p class="mt-1 text-sm font-medium text-white/90">{{ str_replace('_', ' ', $issuer) }}</p>
                                        </div>
                                        <div class="h-8 w-11 rounded-md border border-amber-200/50 bg-amber-200/80"></div>
                                    </div>

                                    <div>
                                        <p class="font-mono text-xl font-semibold tracking-normal text-white sm:text-2xl">
                                            {{ $primaryCard?->masked_pan ?? '**** **** **** ****' }}
                                        </p>
                                        <div class="mt-4 flex items-end justify-between gap-4">
                                            <div class="min-w-0">
                                                <p class="text-xs uppercase text-white/50">Card holder</p>
                                                <p class="truncate text-sm font-semibold text-white">{{ $revealedDetails['cardholder_name'] ?? $user->name }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs uppercase text-white/50">Balance</p>
                                                <p class="font-mono text-sm font-semibold text-white">
                                                    {{ $primaryCard ? number_format(($cardBalances[$primaryCard->id] ?? 0) / 100, 2) : '0.00' }} {{ $primaryCard?->currency ?? 'USD' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </section>

                    <section class="wy-card">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-black">Create card</h2>
                                <p class="mt-1 text-sm text-zinc-500">Spends from your USD wallet.</p>
                            </div>
                            <span class="wy-chip">USD</span>
                        </div>

                        <form method="POST" action="{{ route('wallet.cards.store') }}" class="mt-4 space-y-3">
                            @csrf

                            <div>
                                <label for="nickname" class="wy-label">Card nickname</label>
                                <input id="nickname" name="nickname" value="{{ old('nickname') }}" placeholder="Netflix, ads, hosting" class="wy-field text-base sm:text-sm">
                                @error('nickname')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <button type="submit" @disabled($usdWallet->cached_balance_minor <= 0) class="wy-button wy-button-primary w-full disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-500">
                                Create virtual card
                            </button>
                        </form>
                    </section>
                </div>

                <section class="wy-table-card">
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3">
                        <div>
                            <h2 class="font-semibold text-black">My cards</h2>
                            <p class="mt-1 text-xs text-zinc-500">{{ $cards->count() }} issued</p>
                        </div>
                        <span class="wy-chip">Online payments</span>
                    </div>

                    <div class="divide-y divide-zinc-200">
                        @forelse ($cards as $card)
                            <article class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate font-semibold text-black">{{ $card->nickname ?: ($card->brand ?? 'Virtual card') }}</p>
                                            <span class="wy-chip py-0.5">{{ $card->status }}</span>
                                        </div>
                                        <p class="mt-2 font-mono text-sm text-zinc-600">{{ $card->masked_pan ?? 'tokenized' }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $card->provider }} · {{ $card->currency }} · {{ $card->created_at->format('d M Y H:i') }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-xs uppercase text-zinc-500">Card balance</p>
                                        <p class="mt-1 font-mono text-sm text-zinc-700">
                                            {{ number_format(($cardBalances[$card->id] ?? 0) / 100, 2) }}
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('wallet.cards.top-up', $card) }}" class="mt-4 grid gap-2 sm:grid-cols-[1fr_auto]">
                                    @csrf
                                    <input type="number" name="amount" min="0.01" step="0.01" placeholder="USD amount" @disabled($card->status !== 'active') class="wy-field min-w-0 text-base disabled:cursor-not-allowed disabled:bg-zinc-100 sm:text-sm">
                                    <button type="submit" @disabled($card->status !== 'active') class="wy-button wy-button-primary disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-zinc-500">
                                        Top up
                                    </button>
                                </form>

                                <div class="mt-2 grid gap-2 sm:grid-cols-[1fr_auto]">
                                    <form method="POST" action="{{ route('wallet.cards.reveal', $card) }}" class="grid gap-2 sm:grid-cols-[1fr_auto]">
                                        @csrf
                                        <input type="password" name="password" autocomplete="current-password" placeholder="Password" @disabled($card->status !== 'active') class="wy-field min-w-0 text-base disabled:cursor-not-allowed disabled:bg-zinc-100 sm:text-sm">
                                        <button type="submit" @disabled($card->status !== 'active') class="wy-button wy-button-secondary disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-zinc-500">
                                            View details
                                        </button>
                                    </form>

                                    @if ($card->status === 'active')
                                        <form method="POST" action="{{ route('wallet.cards.freeze', $card) }}">
                                            @csrf
                                            <button type="submit" class="wy-button wy-button-secondary w-full">
                                                Freeze
                                            </button>
                                        </form>
                                    @elseif ($card->status === 'inactive')
                                        <form method="POST" action="{{ route('wallet.cards.unfreeze', $card) }}">
                                            @csrf
                                            <button type="submit" class="wy-button wy-button-primary w-full">
                                                Unfreeze
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="px-4 py-10 text-center">
                                <p class="font-semibold text-black">No cards yet</p>
                                <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-zinc-500">Create your first virtual card after converting MWK into USD.</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($recentCardTransactions->isNotEmpty())
                        <div class="border-t border-zinc-200 px-4 py-3">
                            <h2 class="font-semibold text-black">Card activity</h2>
                            <div class="mt-3 divide-y divide-zinc-100">
                                @foreach ($recentCardTransactions as $transaction)
                                    <div class="flex items-center justify-between gap-3 py-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-black">{{ str_replace('_', ' ', $transaction->type) }}</p>
                                            <p class="mt-0.5 text-xs text-zinc-500">{{ $transaction->created_at->format('d M Y H:i') }}</p>
                                        </div>
                                        <p class="font-mono text-sm font-semibold text-zinc-800">
                                            {{ $transaction->type === 'top_up' ? '+' : '-' }}{{ number_format($transaction->amount_minor / 100, 2) }} {{ $transaction->currency }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            </section>

        </main>
        @if ($revealedCard && $revealedDetails)
            <div class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"></div>
            <section class="fixed inset-x-0 bottom-0 z-50 rounded-t-[30px] bg-zinc-50 p-4 shadow-2xl shadow-black/50 lg:hidden">
                <div class="mx-auto h-1.5 w-12 rounded-full bg-zinc-700"></div>
                <div class="mt-4 flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-amber-700">Card details revealed</p>
                        <p class="mt-1 text-sm text-zinc-500">Shown once. Wallet Yanga does not save full card data.</p>
                    </div>
                    <a href="{{ route('wallet.cards') }}" class="wy-button wy-button-secondary">Done</a>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="wy-card col-span-2 p-3">
                        <p class="text-xs uppercase text-zinc-500">Card number</p>
                        <p class="mt-1 break-all font-mono text-lg font-semibold text-black">{{ $revealedDetails['number'] ?: 'Unavailable' }}</p>
                    </div>
                    <div class="wy-card p-3">
                        <p class="text-xs uppercase text-zinc-500">Expiry</p>
                        <p class="mt-1 font-mono text-lg font-semibold text-black">{{ $revealedDetails['expiry_month'] ?: '--' }}/{{ $revealedDetails['expiry_year'] ?: '--' }}</p>
                    </div>
                    <div class="wy-card p-3">
                        <p class="text-xs uppercase text-zinc-500">CVV</p>
                        <p class="mt-1 font-mono text-lg font-semibold text-black">{{ $revealedDetails['cvv'] ?: '---' }}</p>
                    </div>
                    <div class="wy-card col-span-2 p-3">
                        <p class="text-xs uppercase text-zinc-500">Name</p>
                        <p class="mt-1 truncate text-lg font-semibold text-black">{{ $revealedDetails['cardholder_name'] ?: $user->name }}</p>
                    </div>
                </div>
            </section>

            <section class="mx-auto hidden w-full max-w-7xl px-4 pb-8 sm:px-6 lg:block lg:px-8">
                <div class="wy-alert wy-alert-warning p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-amber-700">Card details revealed</p>
                            <p class="mt-1 text-sm text-amber-700/75">Shown once. Wallet Yanga does not save full card data.</p>
                        </div>
                        <a href="{{ route('wallet.cards') }}" class="wy-button wy-button-secondary">Done</a>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-4">
                        <div>
                            <p class="text-xs uppercase text-amber-700/70">Number</p>
                            <p class="mt-1 break-all font-mono text-sm font-semibold text-black">{{ $revealedDetails['number'] ?: 'Unavailable' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-amber-700/70">Expiry</p>
                            <p class="mt-1 font-mono text-sm font-semibold text-black">{{ $revealedDetails['expiry_month'] ?: '--' }}/{{ $revealedDetails['expiry_year'] ?: '--' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-amber-700/70">CVV</p>
                            <p class="mt-1 font-mono text-sm font-semibold text-black">{{ $revealedDetails['cvv'] ?: '---' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-amber-700/70">Name</p>
                            <p class="mt-1 truncate text-sm font-semibold text-black">{{ $revealedDetails['cardholder_name'] ?: $user->name }}</p>
                        </div>
                    </div>
                </div>
            </section>
        @endif
        @include('partials.mobile-nav')
    </body>
</html>
