<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Add money · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 text-zinc-100 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-lg flex-col gap-8 px-6 py-8">
            <nav class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-800 pb-5">
                <div>
                    <p class="text-sm font-semibold text-white">Add money</p>
                    <p class="text-sm text-zinc-400">Pay with Paychangu · settled in MWK</p>
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

            <section class="rounded-lg border border-zinc-800 bg-zinc-900 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Reference rate</p>
                <p class="mt-2 text-lg font-semibold text-white">
                    1 USD ≈ <span id="rate-display">{{ number_format($spot['rate'], 2) }}</span> MWK
                </p>
                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500">
                    <span id="rate-meta-text">
                        @if ($spot['as_of'])
                            As of {{ $spot['as_of'] }} ·
                        @endif
                        Source: {{ $spot['source'] === 'currency-api' ? 'live market data' : 'configured fallback' }}
                    </span>
                    <button type="button" id="refresh-rate" class="text-emerald-400 hover:text-emerald-300 disabled:opacity-50">Refresh</button>
                </p>
                <p class="mt-3 text-xs leading-relaxed text-zinc-500">
                    Rates are indicative for planning. Your bank or Paychangu may apply a slightly different settlement amount.
                </p>
            </section>

            <section class="rounded-lg border border-zinc-800 bg-zinc-900 p-5">
                <form method="POST" action="{{ route('wallet.add-money.store') }}" class="space-y-5" id="add-money-form">
                    @csrf
                    <div>
                        <label for="input_currency" class="block text-sm font-medium text-zinc-300">I want to enter the amount in</label>
                        <select id="input_currency" name="input_currency" class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400">
                            <option value="MWK" @selected(old('input_currency', 'MWK') === 'MWK')>Malawian Kwacha (MWK)</option>
                            <option value="USD" @selected(old('input_currency') === 'USD')>US Dollar (USD)</option>
                        </select>
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-zinc-300">Amount</label>
                        <input id="amount" name="amount" type="number" inputmode="decimal" step="0.01" min="0.01" required
                            value="{{ old('amount') }}"
                            class="mt-2 w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white outline-none focus:border-emerald-400"
                            placeholder="0.00">
                    </div>
                    <p class="text-xs text-zinc-500">Minimum charge: {{ number_format($minMwkMinor / 100, 2) }} MWK · Maximum: {{ number_format($maxMwkMinor / 100, 2) }} MWK.</p>
                    <div class="rounded-md border border-zinc-800 bg-zinc-950/80 px-4 py-3 text-sm">
                        <p class="text-zinc-500">Approximate equivalent</p>
                        <p id="equivalent-line" class="mt-1 font-medium text-white">Enter an amount to see the conversion.</p>
                        <p class="mt-2 text-xs text-zinc-500">
                            You will pay <span class="text-zinc-300" id="pay-mwk-line">—</span> MWK via Paychangu (charge currency).
                        </p>
                    </div>
                    <button type="submit" class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">
                        Continue to payment
                    </button>
                </form>
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
                        const usdEq = raw / mwkPerUsd;
                        equivalentLine.textContent = '≈ ' + fmt(usdEq, 2) + ' USD';
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
    </body>
</html>
