<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        @php
            $mwkWallet = $wallets->firstWhere('currency', 'MWK');
            $usdWallet = $wallets->firstWhere('currency', 'USD');
            $primaryCard = $cards->first();
            $kycStatus = $user->kycProfile?->status ?? 'not_started';
            $firstName = str($user->name)->before(' ')->toString() ?: $user->name;
            $usdBalance = ($usdWallet?->cached_balance_minor ?? 0) / 100;
            $mwkBalance = ($mwkWallet?->cached_balance_minor ?? 0) / 100;
            $cardLastFour = $primaryCard?->masked_pan ? substr(preg_replace('/\D/', '', $primaryCard->masked_pan), -4) : '1234';
            $activityItems = $timelineItems->take(4);
            $hour = now()->hour;
            $timeGreeting = match (true) {
                $hour < 12 => 'M’mawa wabwino',
                $hour < 17 => 'Masana abwino',
                default => 'Madzulo abwino',
            };
        @endphp

        <main class="mx-auto min-h-screen w-full max-w-[430px] bg-white px-6 pb-24 pt-16 shadow-2xl shadow-zinc-300/60 sm:px-7 lg:my-8 lg:max-w-6xl lg:rounded-[32px] lg:px-10 lg:pb-12">
            <section class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-semibold leading-tight tracking-normal text-black sm:text-[32px]">{{ $timeGreeting }}, {{ $firstName }}</h1>
                    <p class="mt-1 text-[17px] font-normal text-zinc-500 sm:text-[19px]">Welcome to Wallet Yanga</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                    @csrf
                    <button type="submit" class="rounded-full border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-600 hover:border-zinc-400">
                        Logout
                    </button>
                </form>
            </section>

            @if (session('status'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (! in_array($kycStatus, ['approved', 'pending'], true))
                <section class="mt-5 rounded-3xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-800">KYC required</p>
                    <p class="mt-1 text-sm leading-6 text-amber-700">Complete identity checks to unlock deposits, conversion, and virtual cards.</p>
                    <a href="{{ route('kyc.edit') }}" class="mt-3 inline-flex rounded-full bg-amber-300 px-4 py-2 text-sm font-semibold text-zinc-950">
                        Start KYC
                    </a>
                </section>
            @elseif ($kycStatus === 'pending')
                <section class="mt-5 rounded-3xl border border-sky-200 bg-sky-50 p-4">
                    <p class="text-sm font-semibold text-sky-800">KYC under review</p>
                    <p class="mt-1 text-sm leading-6 text-sky-700">Compliance has your submission. Wallet services unlock after approval.</p>
                </section>
            @endif

            <section class="mt-6 lg:grid lg:grid-cols-[0.85fr_1.15fr] lg:gap-10">
                <div>
                    <section>
                        <p class="text-[18px] font-medium text-zinc-500 sm:text-[21px]">Dollar Balance</p>
                        <p class="mt-3 max-w-full overflow-hidden text-[54px] font-normal leading-none tracking-[-0.02em] text-black sm:text-[76px] lg:text-[86px]">
                            ${{ number_format($usdBalance, 1) }}
                        </p>
                        <div class="mt-5">
                            <p class="text-[16px] font-normal text-zinc-500 sm:text-[18px]">Kwacha Balance</p>
                            <p class="mt-1 text-[18px] font-semibold text-zinc-500 sm:text-[20px]">MWK {{ number_format($mwkBalance, 2) }}</p>
                        </div>
                    </section>

                    @if ($kycStatus === 'approved')
                        <section class="mt-6 grid grid-cols-3 items-start gap-3 text-center sm:gap-5">
                            <a href="{{ route('wallet.add-money') }}" class="group flex min-w-0 flex-col items-center">
                                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#4cd14f] text-black shadow-[0_10px_22px_rgba(76,209,79,0.26)] transition group-hover:scale-105 sm:h-24 sm:w-24">
                                    <svg viewBox="0 0 24 24" class="h-8 w-8 sm:h-12 sm:w-12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                        <path d="M12 5v14M5 12h14" />
                                    </svg>
                                </span>
                                <span class="mt-3 block text-[17px] font-medium text-black sm:mt-4 sm:text-[20px]">Deposit</span>
                            </a>
                            <a href="{{ route('wallet.convert') }}" class="group flex min-w-0 flex-col items-center">
                                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#4cd14f] text-black shadow-[0_10px_22px_rgba(76,209,79,0.26)] transition group-hover:scale-105 sm:h-24 sm:w-24">
                                    <svg viewBox="0 0 24 24" class="h-8 w-8 sm:h-12 sm:w-12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 3l4 4-4 4" />
                                        <path d="M3 7h18" />
                                        <path d="M7 21l-4-4 4-4" />
                                        <path d="M21 17H3" />
                                    </svg>
                                </span>
                                <span class="mt-3 block text-[17px] font-medium text-black sm:mt-4 sm:text-[20px]">Convert</span>
                                <span class="mx-auto mt-5 block h-1.5 w-12 rounded-full bg-[#4cd14f] sm:mt-9 sm:w-16"></span>
                            </a>
                            <button type="button" disabled class="flex min-w-0 cursor-not-allowed flex-col items-center opacity-95">
                                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#4cd14f] text-black shadow-[0_10px_22px_rgba(76,209,79,0.26)] sm:h-24 sm:w-24">
                                    <svg viewBox="0 0 24 24" class="h-8 w-8 sm:h-12 sm:w-12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 4v16" />
                                        <path d="M5 13l7 7 7-7" />
                                    </svg>
                                </span>
                                <span class="mt-3 block text-[17px] font-medium text-black sm:mt-4 sm:text-[20px]">Withdraw</span>
                            </button>
                        </section>
                    @endif

                    <section class="mt-5">
                        <div class="flex items-center justify-between">
                            <h2 class="text-[27px] font-semibold tracking-normal text-black sm:text-[30px]">My cards</h2>
                            <a href="{{ route('wallet.cards') }}" class="text-[18px] font-semibold text-[#4cd14f] sm:text-[20px]">Add cards</a>
                        </div>

                        <a href="{{ route('wallet.cards') }}" class="mt-5 block h-[92px] overflow-hidden rounded-[16px] bg-[radial-gradient(circle_at_20%_0%,rgba(255,255,255,0.18),transparent_28%),linear-gradient(135deg,#202020_0%,#0d0d0d_55%,#252525_100%)] px-5 py-4 shadow-[0_16px_30px_rgba(0,0,0,0.18)] sm:h-[122px] sm:p-5">
                            <div class="flex items-start justify-between">
                                <p class="text-[24px] font-black italic leading-none text-white sm:text-[32px]">VISA</p>
                                <p class="select-none text-[36px] font-black italic leading-none text-white/10 sm:text-[56px]">VISA</p>
                            </div>
                            <p class="mt-3 font-mono text-[13px] font-semibold tracking-[0.18em] text-white sm:mt-6 sm:text-[19px] sm:tracking-[0.28em]">
                                •••• •••• •••• {{ $cardLastFour ?: '1234' }}
                            </p>
                            <p class="mt-2 text-[15px] font-normal text-white sm:mt-5 sm:text-[19px]">Wallet Yanga</p>
                        </a>
                    </section>
                </div>

                <section class="mt-6 rounded-t-[30px] bg-[#f5f6f8] px-0 pb-4 pt-5 lg:mt-0 lg:rounded-[34px] lg:p-7">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-[27px] font-semibold tracking-normal text-black sm:text-[30px]">Recent Activity</h2>
                        <a href="{{ route('wallet.activity') }}" class="rounded-full border border-zinc-400/70 bg-white px-4 py-2 text-[15px] font-medium text-black shadow-sm sm:px-5 sm:text-[17px]">
                            View all
                        </a>
                    </div>

                    <div class="mt-5 space-y-3 sm:mt-6 sm:space-y-4">
                        @forelse ($activityItems as $item)
                            @php
                                $iconBg = match ($item['tone']) {
                                    'emerald' => 'bg-emerald-100',
                                    'sky' => 'bg-sky-100',
                                    'violet' => 'bg-violet-100',
                                    'amber' => 'bg-amber-100',
                                    default => 'bg-zinc-100',
                                };
                            @endphp
                            <article class="flex items-center justify-between gap-3 rounded-[20px] bg-white p-3 shadow-[0_14px_32px_rgba(15,23,42,0.06)] sm:gap-4 sm:p-4">
                                <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full {{ $iconBg }} text-black sm:h-16 sm:w-16">
                                        @if ($item['direction'] === 'exchange')
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 sm:h-8 sm:w-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 3l4 4-4 4" />
                                                <path d="M3 7h18" />
                                                <path d="M7 21l-4-4 4-4" />
                                                <path d="M21 17H3" />
                                            </svg>
                                        @elseif ($item['direction'] === 'out')
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 sm:h-8 sm:w-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 20V6" />
                                                <path d="M6 12l6-6 6 6" />
                                                <path d="M5 21h14" />
                                            </svg>
                                        @else
                                            <svg viewBox="0 0 24 24" class="h-7 w-7 sm:h-8 sm:w-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 4v14" />
                                                <path d="M6 12l6 6 6-6" />
                                                <path d="M5 21h14" />
                                            </svg>
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-[19px] font-semibold text-black sm:text-[22px]">{{ $item['title'] }}</p>
                                        <p class="mt-1 truncate text-[15px] text-zinc-500 sm:text-[17px]">{{ $item['subtitle'] }}</p>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-[18px] font-semibold text-black sm:text-[21px]">{{ $item['amount_label'] }}</p>
                                    <p class="mt-1 text-[14px] text-zinc-500 sm:text-[16px]">{{ $item['occurred_at']->format('M j, g:iA') }}</p>
                                </div>
                            </article>
                        @empty
                            <article class="rounded-[22px] bg-white p-6 text-center shadow-[0_14px_32px_rgba(15,23,42,0.06)]">
                                <p class="text-lg font-semibold text-black">No activity yet</p>
                                <p class="mt-2 text-sm leading-6 text-zinc-500">Your deposits, conversions, and card payments will appear here.</p>
                            </article>
                        @endforelse
                    </div>
                </section>
            </section>
        </main>
        @include('partials.mobile-nav')
    </body>
</html>
