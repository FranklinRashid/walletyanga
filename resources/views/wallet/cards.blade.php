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

        <main class="mx-auto min-h-screen w-full max-w-7xl px-4 pb-24 pt-16 sm:px-6 lg:px-8 lg:pb-10">
            <nav class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('wallet.dashboard') }}" class="text-sm font-semibold text-emerald-300 hover:text-emerald-200">Wallet Yanga</a>
                    <h1 class="mt-1 truncate text-2xl font-semibold text-black">Virtual cards</h1>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('wallet.convert') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400">
                        Convert
                    </a>
                    <a href="{{ route('wallet.add-money') }}" class="rounded-md bg-emerald-400 px-3 py-2 text-sm font-semibold text-zinc-950 hover:bg-emerald-300">
                        Add money
                    </a>
                </div>
            </nav>

            <div class="mt-5 space-y-3">
                @if (session('status'))
                    <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>

            <section class="mt-5 grid gap-4 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-zinc-200 bg-white p-4">
                            <p class="text-xs font-medium uppercase text-zinc-500">USD Balance</p>
                            <p class="mt-2 font-mono text-2xl font-semibold text-black">{{ number_format($usdWallet->cached_balance_minor / 100, 2) }}</p>
                        </div>
                        <div class="rounded-lg border border-zinc-200 bg-white p-4">
                            <p class="text-xs font-medium uppercase text-zinc-500">MWK Balance</p>
                            <p class="mt-2 font-mono text-2xl font-semibold text-black">{{ number_format($mwkWallet->cached_balance_minor / 100, 2) }}</p>
                        </div>
                    </div>

                    <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                        <div class="p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-zinc-500">Active card</p>
                                    <p class="mt-1 text-lg font-semibold text-black">{{ $primaryCard?->nickname ?: 'Wallet Yanga Card' }}</p>
                                </div>
                                <span class="rounded-full border border-emerald-400/30 px-2 py-1 text-xs font-medium text-emerald-200">{{ $primaryCard?->status ?? 'ready' }}</span>
                            </div>

                            <div class="mt-4 aspect-[1.586/1] w-full overflow-hidden rounded-lg border border-white/10 bg-[linear-gradient(135deg,#0c0f0f_0%,#14524a_48%,#b46a22_100%)] p-5 shadow-2xl shadow-black/30">
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
                                                <p class="text-xs uppercase text-white/50">Currency</p>
                                                <p class="font-mono text-sm font-semibold text-white">{{ $primaryCard?->currency ?? 'USD' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-black">Create card</h2>
                                <p class="mt-1 text-sm text-zinc-500">Spends from your USD wallet.</p>
                            </div>
                            <span class="rounded-full border border-zinc-300 px-2 py-1 text-xs text-zinc-600">USD</span>
                        </div>

                        <form method="POST" action="{{ route('wallet.cards.store') }}" class="mt-4 space-y-3">
                            @csrf

                            <div>
                                <label for="nickname" class="text-sm font-medium text-zinc-700">Card nickname</label>
                                <input id="nickname" name="nickname" value="{{ old('nickname') }}" placeholder="Netflix, ads, hosting" class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-3 text-base text-black outline-none focus:border-emerald-400 sm:py-2 sm:text-sm">
                                @error('nickname')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                            </div>

                            <button type="submit" @disabled($usdWallet->cached_balance_minor <= 0) class="w-full rounded-md bg-emerald-400 px-4 py-3 text-sm font-semibold text-zinc-950 hover:bg-emerald-300 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-500 sm:py-2.5">
                                Create virtual card
                            </button>
                        </form>
                    </section>
                </div>

                <section class="rounded-lg border border-zinc-200 bg-white">
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3">
                        <div>
                            <h2 class="font-semibold text-black">My cards</h2>
                            <p class="mt-1 text-xs text-zinc-500">{{ $cards->count() }} issued</p>
                        </div>
                        <span class="rounded-full border border-zinc-300 px-2 py-1 text-xs text-zinc-600">Online payments</span>
                    </div>

                    <div class="divide-y divide-zinc-200">
                        @forelse ($cards as $card)
                            <article class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate font-semibold text-black">{{ $card->nickname ?: ($card->brand ?? 'Virtual card') }}</p>
                                            <span class="rounded-full border border-zinc-300 px-2 py-0.5 text-xs text-zinc-600">{{ $card->status }}</span>
                                        </div>
                                        <p class="mt-2 font-mono text-sm text-zinc-600">{{ $card->masked_pan ?? 'tokenized' }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $card->provider }} · {{ $card->currency }} · {{ $card->created_at->format('d M Y H:i') }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-xs uppercase text-zinc-500">Limit</p>
                                        <p class="mt-1 font-mono text-sm text-zinc-700">
                                            {{ $card->daily_limit_minor ? number_format($card->daily_limit_minor / 100, 2) : 'Default' }}
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('wallet.cards.reveal', $card) }}" class="mt-4 grid gap-2 sm:grid-cols-[1fr_auto]">
                                    @csrf
                                    <input type="password" name="password" autocomplete="current-password" placeholder="Password" class="min-w-0 rounded-md border border-zinc-300 bg-zinc-50 px-3 py-3 text-base text-black outline-none focus:border-amber-300 sm:py-2 sm:text-sm">
                                    <button type="submit" class="rounded-md border border-amber-300/60 px-3 py-3 text-sm font-semibold text-amber-700 hover:border-amber-400 sm:py-2">
                                        View details
                                    </button>
                                </form>
                            </article>
                        @empty
                            <div class="px-4 py-10 text-center">
                                <p class="font-semibold text-black">No cards yet</p>
                                <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-zinc-500">Create your first virtual card after converting MWK into USD.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </section>

        </main>
        @if ($revealedCard && $revealedDetails)
            <div class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"></div>
            <section class="fixed inset-x-0 bottom-0 z-50 rounded-t-lg border border-amber-300/20 bg-zinc-50 p-4 shadow-2xl shadow-black/50 lg:hidden">
                <div class="mx-auto h-1.5 w-12 rounded-full bg-zinc-700"></div>
                <div class="mt-4 flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-amber-700">Card details revealed</p>
                        <p class="mt-1 text-sm text-zinc-500">Shown once. Wallet Yanga does not save full card data.</p>
                    </div>
                    <a href="{{ route('wallet.cards') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm text-zinc-700">Done</a>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="col-span-2 rounded-lg border border-zinc-200 bg-white p-3">
                        <p class="text-xs uppercase text-zinc-500">Card number</p>
                        <p class="mt-1 break-all font-mono text-lg font-semibold text-black">{{ $revealedDetails['number'] ?: 'Unavailable' }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-200 bg-white p-3">
                        <p class="text-xs uppercase text-zinc-500">Expiry</p>
                        <p class="mt-1 font-mono text-lg font-semibold text-black">{{ $revealedDetails['expiry_month'] ?: '--' }}/{{ $revealedDetails['expiry_year'] ?: '--' }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-200 bg-white p-3">
                        <p class="text-xs uppercase text-zinc-500">CVV</p>
                        <p class="mt-1 font-mono text-lg font-semibold text-black">{{ $revealedDetails['cvv'] ?: '---' }}</p>
                    </div>
                    <div class="col-span-2 rounded-lg border border-zinc-200 bg-white p-3">
                        <p class="text-xs uppercase text-zinc-500">Name</p>
                        <p class="mt-1 truncate text-lg font-semibold text-black">{{ $revealedDetails['cardholder_name'] ?: $user->name }}</p>
                    </div>
                </div>
            </section>

            <section class="mx-auto hidden w-full max-w-7xl px-4 pb-8 sm:px-6 lg:block lg:px-8">
                <div class="rounded-lg border border-amber-300/20 bg-amber-400/10 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-amber-700">Card details revealed</p>
                            <p class="mt-1 text-sm text-amber-700/75">Shown once. Wallet Yanga does not save full card data.</p>
                        </div>
                        <a href="{{ route('wallet.cards') }}" class="rounded-md border border-amber-300/60 px-3 py-2 text-sm text-amber-700">Done</a>
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
