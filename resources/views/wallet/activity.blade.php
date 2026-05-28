<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Activity · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-wide">
            <nav class="wy-topbar">
                <div class="min-w-0">
                    <a href="{{ route('wallet.dashboard') }}" class="wy-kicker">Wallet Yanga</a>
                    <h1 class="wy-page-title truncate">Activity</h1>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('wallet.convert') }}" class="wy-button wy-button-secondary">Convert</a>
                    <a href="{{ route('wallet.add-money') }}" class="wy-button wy-button-primary">Add money</a>
                </div>
            </nav>

            <section class="mt-5 overflow-hidden rounded-[24px] border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-4 py-3">
                    <h2 class="font-semibold text-black">Money timeline</h2>
                    <p class="mt-1 text-sm text-zinc-500">Deposits, conversions, card funding, and card spend in one place.</p>
                </div>

                <div class="divide-y divide-zinc-100">
                    @forelse ($timelineItems as $item)
                        @php
                            $iconBg = match ($item['tone']) {
                                'emerald' => 'bg-emerald-100',
                                'sky' => 'bg-sky-100',
                                'violet' => 'bg-violet-100',
                                'amber' => 'bg-amber-100',
                                default => 'bg-zinc-100',
                            };
                        @endphp
                        <article class="grid gap-3 px-4 py-4 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $iconBg }} text-black">
                                    @if ($item['direction'] === 'exchange')
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 3l4 4-4 4" />
                                            <path d="M3 7h18" />
                                            <path d="M7 21l-4-4 4-4" />
                                            <path d="M21 17H3" />
                                        </svg>
                                    @elseif ($item['direction'] === 'out')
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 20V6" />
                                            <path d="M6 12l6-6 6 6" />
                                            <path d="M5 21h14" />
                                        </svg>
                                    @else
                                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 4v14" />
                                            <path d="M6 12l6 6 6-6" />
                                            <path d="M5 21h14" />
                                        </svg>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-black">{{ $item['title'] }}</p>
                                    <p class="mt-1 truncate text-sm text-zinc-500">{{ $item['subtitle'] }}</p>
                                </div>
                            </div>
                            <div class="pl-14 sm:pl-0 sm:text-right">
                                <p class="font-mono text-sm font-semibold text-black">{{ $item['amount_label'] }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $item['occurred_at']->format('d M Y H:i') }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="px-4 py-10 text-center">
                            <p class="font-semibold text-black">No activity yet</p>
                            <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-zinc-500">Your wallet and card movements will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>
        @include('partials.mobile-nav')
    </body>
</html>
