<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $customer->name }} · Customers · Admin · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-wide flex flex-col gap-8">
            <nav class="wy-topbar flex-wrap">
                <div>
                    <p class="text-sm font-semibold text-black">Customer profile</p>
                    <p class="text-sm text-zinc-500">{{ $customer->email }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.customers.index') }}" class="wy-button wy-button-secondary">Customers</a>
                    <a href="{{ route('admin.dashboard') }}" class="wy-button wy-button-secondary">Dashboard</a>
                </div>
            </nav>

            @if (session('status'))
                <p class="wy-alert wy-alert-success">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <p class="wy-alert wy-alert-danger">{{ $errors->first() }}</p>
            @endif

            <section class="grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
                <div class="space-y-4">
                    <section class="wy-card">
                        <p class="wy-kicker">Customer</p>
                        <h1 class="mt-2 text-3xl font-semibold text-black">{{ $customer->name }}</h1>
                        <p class="mt-2 text-sm text-zinc-500">{{ $customer->email }}</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase text-zinc-500">Status</p>
                                <p class="mt-1 font-semibold text-black">{{ $customer->status }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-zinc-500">KYC</p>
                                <p class="mt-1 font-semibold text-black">{{ $customer->kycProfile?->status ?? 'not_started' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-zinc-500">Phone</p>
                                <p class="mt-1 font-semibold text-black">{{ $customer->profile?->phone ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-zinc-500">Country</p>
                                <p class="mt-1 font-semibold text-black">{{ $customer->profile?->country ?? '—' }}</p>
                            </div>
                        </div>
                        @if ($canManageCustomers)
                            <div class="mt-5 border-t border-zinc-100 pt-4">
                                @if ($customer->status === 'active')
                                    <form method="POST" action="{{ route('admin.customers.suspend', $customer) }}" class="space-y-3">
                                        @csrf
                                        <label for="customer_suspend_reason" class="wy-label">Suspension reason</label>
                                        <textarea id="customer_suspend_reason" name="reason" rows="2" required class="wy-field text-sm" placeholder="Why should this customer account be suspended?">{{ old('reason') }}</textarea>
                                        <button type="submit" class="wy-button border border-red-200 bg-red-50 text-red-700 hover:border-red-300">
                                            Suspend account
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.customers.activate', $customer) }}">
                                        @csrf
                                        <button type="submit" class="wy-button wy-button-primary">
                                            Activate account
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                        @if ($customer->kycProfile)
                            <div class="mt-5 border-t border-zinc-100 pt-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs uppercase text-zinc-500">KYC details</p>
                                        <p class="mt-1 text-sm text-zinc-600">
                                            Tier {{ $customer->kycProfile->tier ?? '—' }} · Risk {{ $customer->kycProfile->risk_rating ?? '—' }}
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.kyc.show', $customer->kycProfile) }}" class="wy-button wy-button-secondary">Review KYC</a>
                                </div>

                                @if ($canRevokeKyc && $customer->kycProfile->status === 'approved')
                                    <form method="POST" action="{{ route('admin.customers.kyc.revoke', $customer) }}" class="mt-4 space-y-3">
                                        @csrf
                                        <label for="kyc_revoke_reason" class="wy-label">Revocation reason</label>
                                        <textarea id="kyc_revoke_reason" name="reason" rows="2" required class="wy-field text-sm" placeholder="Why should this customer lose approved KYC access?">{{ old('reason') }}</textarea>
                                        <button type="submit" class="wy-button border border-red-200 bg-red-50 text-red-700 hover:border-red-300">
                                            Revoke KYC
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </section>

                    <section class="wy-card">
                        <h2 class="font-semibold text-black">Wallets</h2>
                        <div class="mt-3 divide-y divide-zinc-100">
                            @forelse ($customer->wallets as $wallet)
                                <div class="flex items-center justify-between gap-3 py-3">
                                    <div>
                                        <p class="font-semibold text-black">{{ $wallet->currency }} · {{ str_replace('_', ' ', $wallet->type) }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $wallet->status }}</p>
                                    </div>
                                    <p class="font-mono text-sm font-semibold text-black">{{ number_format($wallet->cached_balance_minor / 100, 2) }}</p>
                                </div>
                            @empty
                                <p class="py-4 text-sm text-zinc-500">No wallets yet.</p>
                            @endforelse
                        </div>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="wy-table-card">
                        <div class="border-b border-zinc-200 px-4 py-3">
                            <h2 class="font-semibold text-black">Virtual cards</h2>
                        </div>
                        <div class="divide-y divide-zinc-200">
                            @forelse ($customer->virtualCards as $card)
                                <article class="grid gap-3 px-4 py-4 sm:grid-cols-[1fr_auto] sm:items-center">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate font-semibold text-black">{{ $card->nickname ?: ($card->brand ?? 'Virtual card') }}</p>
                                            <span class="wy-chip">{{ $card->status }}</span>
                                        </div>
                                        <p class="mt-2 font-mono text-sm text-zinc-600">{{ $card->masked_pan ?? $card->provider_card_id }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $card->provider }} · {{ $card->currency }} · Balance {{ number_format(($cardBalances[$card->id] ?? 0) / 100, 2) }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 sm:justify-end">
                                        @if ($canManageCards && $card->status === 'active')
                                            <form method="POST" action="{{ route('admin.customers.cards.freeze', [$customer, $card]) }}">
                                                @csrf
                                                <button type="submit" class="wy-button wy-button-secondary">Freeze</button>
                                            </form>
                                        @elseif ($canManageCards && $card->status === 'inactive')
                                            <form method="POST" action="{{ route('admin.customers.cards.unfreeze', [$customer, $card]) }}">
                                                @csrf
                                                <button type="submit" class="wy-button wy-button-primary">Unfreeze</button>
                                            </form>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-zinc-500">No virtual cards yet.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="wy-table-card">
                        <div class="border-b border-zinc-200 px-4 py-3">
                            <h2 class="font-semibold text-black">Recent activity</h2>
                        </div>
                        <div class="divide-y divide-zinc-100">
                            @forelse ($timelineItems as $item)
                                <article class="grid gap-2 px-4 py-3 sm:grid-cols-[1fr_auto] sm:items-center">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-black">{{ $item['title'] }}</p>
                                        <p class="mt-1 truncate text-sm text-zinc-500">{{ $item['subtitle'] }}</p>
                                    </div>
                                    <div class="sm:text-right">
                                        <p class="font-mono text-sm font-semibold text-black">{{ $item['amount_label'] }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $item['occurred_at']->format('d M Y H:i') }}</p>
                                    </div>
                                </article>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-zinc-500">No activity yet.</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            </section>
        </main>
        @include("partials.mobile-nav")
    </body>
</html>
