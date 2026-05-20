<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Add money · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="mx-auto min-h-screen w-full max-w-5xl px-4 pb-20 pt-16 sm:px-6 lg:px-8">
            <nav class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('wallet.dashboard') }}" class="text-sm font-semibold text-emerald-300 hover:text-emerald-200">Wallet Yanga</a>
                    <h1 class="mt-1 text-2xl font-semibold text-black">Add money</h1>
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

            <section class="mt-5 grid gap-4 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                <div class="space-y-4">
                    <section class="rounded-lg border border-zinc-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase text-zinc-500">Payment method</p>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-400 text-lg font-semibold text-zinc-950">P</div>
                            <div>
                                <p class="font-semibold text-black">Secure checkout</p>
                                <p class="mt-1 text-sm text-zinc-500">Settles into your MWK wallet</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-zinc-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase text-zinc-500">Reference rate</p>
                        <p class="mt-2 text-2xl font-semibold text-black">
                            1 USD ≈ <span id="rate-display">{{ number_format($spot['rate'], 2) }}</span> MWK
                        </p>
                        <p class="mt-2 text-xs leading-5 text-zinc-500" id="rate-meta-text">
                            @if ($spot['as_of'])
                                As of {{ $spot['as_of'] }} ·
                            @endif
                            Source: {{ $spot['source'] === 'currency-api' ? 'live market data' : 'configured fallback' }}
                        </p>
                        <button type="button" id="refresh-rate" class="mt-4 rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-emerald-300 hover:border-emerald-400/50 disabled:opacity-50">
                            Refresh rate
                        </button>
                    </section>
                </div>

                <section class="rounded-lg border border-zinc-200 bg-white p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-black">Top up wallet</p>
                            <p class="mt-1 text-sm text-zinc-500">Choose a currency, enter amount, then continue to checkout.</p>
                        </div>
                        <span class="rounded-full border border-zinc-300 px-2 py-1 text-xs text-zinc-600">Step 1 of 2</span>
                    </div>

                    <form method="POST" action="{{ route('wallet.add-money.store') }}" class="mt-5 space-y-5" id="add-money-form">
                        @csrf
                        <div>
                            <label for="input_currency" class="block text-sm font-medium text-zinc-600">Amount currency</label>
                            <select id="input_currency" name="input_currency" class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-3 text-base text-black outline-none focus:border-emerald-400 sm:py-2 sm:text-sm">
                                <option value="MWK" @selected(old('input_currency', 'MWK') === 'MWK')>Malawian Kwacha (MWK)</option>
                                <option value="USD" @selected(old('input_currency') === 'USD')>US Dollar (USD)</option>
                            </select>
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-zinc-600">Amount</label>
                            <input id="amount" name="amount" type="number" inputmode="decimal" step="0.01" min="0.01" required
                                value="{{ old('amount') }}"
                                class="mt-2 w-full rounded-md border border-zinc-300 bg-zinc-50 px-3 py-4 text-2xl font-semibold text-black outline-none focus:border-emerald-400"
                                placeholder="0.00">
                        </div>

                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                            <p class="text-sm text-zinc-500">Approximate equivalent</p>
                            <p id="equivalent-line" class="mt-1 text-lg font-semibold text-black">Enter an amount to see the conversion.</p>
                            <p class="mt-3 text-xs leading-5 text-zinc-500">
                                Checkout amount: <span class="text-zinc-600" id="pay-mwk-line">—</span> MWK
                            </p>
                        </div>

                        <p class="text-xs leading-5 text-zinc-500">Minimum charge: {{ number_format($minMwkMinor / 100, 2) }} MWK · Maximum: {{ number_format($maxMwkMinor / 100, 2) }} MWK.</p>

                        <button type="submit" class="w-full rounded-md bg-emerald-400 px-4 py-3 text-sm font-semibold text-zinc-950 hover:bg-emerald-300">
                            Continue to payment
                        </button>
                    </form>
                </section>
            </section>
        </main>
        <script>
            (function () {
                const rateUrl = @json(route('api.fx.usd-mwk'));
                let mwkPerUsd = {{ json_encode((float) $spot['rate']) }};

                const inputCurrency = document.getElementById('input_currency');
                const amountEl = document.getElementById('amount');
                const equivalentLine = document.getElementById('equivalent-line');
                const payMwkLine = document.getElementById('pay-mwk-line');
                const rateDisplay = document.getElementById('rate-display');
                const rateMetaText = document.getElementById('rate-meta-text');
                const refreshBtn = document.getElementById('refresh-rate');

                function fmt(n, d) {
                    return Number(n).toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d });
                }

                function recalc() {
                    const raw = parseFloat(amountEl.value);
                    if (!Number.isFinite(raw) || raw <= 0) {
                        equivalentLine.textContent = 'Enter an amount to see the conversion.';
                        payMwkLine.textContent = '—';
                        return;
                    }
                    const cur = inputCurrency.value;
                    let mwkTotal;
                    if (cur === 'MWK') {
                        mwkTotal = raw;
                        equivalentLine.textContent = '≈ ' + fmt(raw / mwkPerUsd, 2) + ' USD';
                    } else {
                        mwkTotal = raw * mwkPerUsd;
                        equivalentLine.textContent = '≈ ' + fmt(mwkTotal, 2) + ' MWK';
                    }
                    payMwkLine.textContent = fmt(mwkTotal, 2);
                }

                async function refreshRate() {
                    refreshBtn.disabled = true;
                    try {
                        const res = await fetch(rateUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!res.ok) throw new Error('bad status');
                        const data = await res.json();
                        if (typeof data.mwk_per_usd === 'number' && data.mwk_per_usd > 0) {
                            mwkPerUsd = data.mwk_per_usd;
                            rateDisplay.textContent = fmt(mwkPerUsd, 2);
                            const asOf = data.as_of ? 'As of ' + data.as_of + ' · ' : '';
                            const src = data.source === 'currency-api' ? 'live market data' : 'configured fallback';
                            rateMetaText.textContent = asOf + 'Source: ' + src;
                            recalc();
                        }
                    } catch (e) {
                        rateMetaText.textContent = 'Could not refresh rate.';
                    }
                    refreshBtn.disabled = false;
                }

                inputCurrency.addEventListener('change', recalc);
                amountEl.addEventListener('input', recalc);
                refreshBtn.addEventListener('click', refreshRate);
                recalc();
            })();
        </script>
        @include('partials.mobile-nav')
    </body>
</html>
