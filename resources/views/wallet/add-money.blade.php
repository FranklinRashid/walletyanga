<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Add money · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="wy-add-money-page bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-compact wy-add-money-shell">
            <nav class="wy-topbar wy-add-money-topbar">
                <a href="{{ route('wallet.dashboard') }}" class="wy-add-money-back" aria-label="Back to dashboard">←</a>
                <h1 class="wy-page-title">Add money</h1>
                <span class="wy-add-money-nav-spacer" aria-hidden="true"></span>
            </nav>

            @if (session('status') || $errors->any())
                <div class="wy-add-money-alerts mt-5 space-y-3">
                    @if (session('status'))
                        <div class="wy-alert wy-alert-warning">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="wy-alert wy-alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif
                </div>
            @endif

            <section class="wy-add-money-layout mt-5">
                <section class="wy-add-money-panel">
                    <div class="wy-wallet-card">
                        <span class="wy-wallet-orb wy-wallet-orb-malawi" aria-hidden="true"></span>
                        <div>
                            <p class="wy-wallet-name">MWK wallet</p>
                            <p class="wy-wallet-subtitle">Settles in Malawian Kwacha</p>
                        </div>
                        <span class="wy-wallet-code">MWK</span>
                    </div>

                    <form method="POST" action="{{ route('wallet.add-money.store') }}" class="wy-add-money-form mt-5 space-y-5" id="add-money-form">
                        @csrf
                        <input type="hidden" id="input_currency" name="input_currency" value="{{ old('input_currency', 'USD') }}">

                        <div class="wy-amount-stage">
                            <label for="amount" class="wy-amount-label">You're adding</label>
                            <div class="wy-amount-input-row">
                                <span class="wy-currency-symbol" id="currency-symbol">$</span>
                                <input id="amount" name="amount" type="number" inputmode="decimal" step="0.01" min="0.01" required
                                    value="{{ old('amount') }}"
                                    class="wy-amount-input"
                                    placeholder="0">
                            </div>
                            <p id="equivalent-line" class="wy-mwk-preview">≈ 0.00 MWK</p>
                            <button type="button" class="wy-currency-switch" id="currency-switch" aria-label="Switch input currency">
                                <span id="currency-switch-current">USD</span>
                                <span aria-hidden="true">⇄</span>
                                <span id="currency-switch-next">MWK</span>
                            </button>
                        </div>

                        <div class="wy-money-detail">
                            <span class="wy-rate-label">
                                Rate
                                <span class="wy-rate-info" tabindex="0" aria-describedby="rate-meta-text">i</span>
                            </span>
                            <span class="wy-money-detail-fill" aria-hidden="true"></span>
                            <span>1 USD ≈ <span id="rate-display">{{ number_format($spot['rate'], 2) }}</span> MWK</span>
                        </div>

                        <p class="wy-rate-meta" id="rate-meta-text">
                            @if ($spot['as_of'])
                                As of {{ $spot['as_of'] }} ·
                            @endif
                            Wallet Yanga does not set this rate. It is sourced from {{ $spot['source'] === 'currency-api' ? 'Currency API' : 'the configured fallback rate' }}.
                        </p>

                        <div class="wy-money-detail wy-money-detail-muted">
                            <span>Limit</span>
                            <span class="wy-money-detail-fill" aria-hidden="true"></span>
                            <span id="limit-display"></span>
                        </div>

                        <button type="button" id="refresh-rate" class="wy-rate-refresh disabled:opacity-50">
                            Refresh rate
                        </button>

                        <button type="submit" class="wy-button wy-button-primary wy-add-money-submit">
                            Add money
                        </button>
                    </form>
                </section>
            </section>
        </main>
        <script>
            (function () {
                const rateUrl = @json(route('api.fx.usd-mwk'));
                let mwkPerUsd = {{ json_encode((float) $spot['rate']) }};
                const limits = {
                    minMwk: {{ json_encode($minMwkMinor / 100) }},
                    maxMwk: {{ json_encode($maxMwkMinor / 100) }},
                };

                const inputCurrency = document.getElementById('input_currency');
                const amountEl = document.getElementById('amount');
                const currencySymbol = document.getElementById('currency-symbol');
                const currencySwitch = document.getElementById('currency-switch');
                const currencySwitchCurrent = document.getElementById('currency-switch-current');
                const currencySwitchNext = document.getElementById('currency-switch-next');
                const equivalentLine = document.getElementById('equivalent-line');
                const limitDisplay = document.getElementById('limit-display');
                const rateDisplay = document.getElementById('rate-display');
                const rateMetaText = document.getElementById('rate-meta-text');
                const refreshBtn = document.getElementById('refresh-rate');
                const submitBtn = document.querySelector('.wy-add-money-submit');

                function fmt(n, d) {
                    return Number(n).toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d });
                }

                function formatAmount(currency, value) {
                    return currency === 'USD' ? '$' + fmt(value, 2) : fmt(value, 2) + ' MWK';
                }

                function syncAmountSize() {
                    const digitCount = amountEl.value.replace(/[^0-9]/g, '').length;
                    let size = '3rem';

                    if (digitCount > 10) {
                        size = '1.85rem';
                    } else if (digitCount > 8) {
                        size = '2.1rem';
                    } else if (digitCount > 6) {
                        size = '2.45rem';
                    } else if (digitCount > 4) {
                        size = '2.75rem';
                    }

                    amountEl.parentElement.style.setProperty('--wy-amount-font-size', size);
                }

                function syncCurrencyUi() {
                    const currency = inputCurrency.value === 'MWK' ? 'MWK' : 'USD';
                    inputCurrency.value = currency;
                    currencySymbol.textContent = currency === 'USD' ? '$' : 'MWK';
                    currencySymbol.classList.toggle('wy-currency-symbol-long', currency === 'MWK');
                    currencySwitchCurrent.textContent = currency;
                    currencySwitchNext.textContent = currency === 'USD' ? 'MWK' : 'USD';
                    amountEl.setAttribute('aria-label', currency + ' amount');
                    limitDisplay.textContent = currency === 'USD'
                        ? formatAmount('USD', limits.minMwk / mwkPerUsd) + '-' + formatAmount('USD', limits.maxMwk / mwkPerUsd)
                        : formatAmount('MWK', limits.minMwk) + '-' + formatAmount('MWK', limits.maxMwk);
                }

                function recalc() {
                    syncCurrencyUi();
                    syncAmountSize();
                    const raw = parseFloat(amountEl.value);
                    if (!Number.isFinite(raw) || raw <= 0) {
                        equivalentLine.textContent = inputCurrency.value === 'USD' ? '≈ 0.00 MWK' : '≈ $0.00';
                        submitBtn.textContent = 'Add money';
                        return;
                    }
                    if (inputCurrency.value === 'USD') {
                        equivalentLine.textContent = '≈ ' + fmt(raw * mwkPerUsd, 2) + ' MWK';
                    } else {
                        equivalentLine.textContent = '≈ $' + fmt(raw / mwkPerUsd, 2);
                    }
                    submitBtn.textContent = 'Add ' + formatAmount(inputCurrency.value, raw);
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
                            const src = data.source === 'currency-api' ? 'Currency API' : 'the configured fallback rate';
                            rateMetaText.textContent = asOf + 'Wallet Yanga does not set this rate. It is sourced from ' + src + '.';
                            recalc();
                        }
                    } catch (e) {
                        rateMetaText.textContent = 'Could not refresh rate.';
                    }
                    refreshBtn.disabled = false;
                }

                currencySwitch.addEventListener('click', function () {
                    inputCurrency.value = inputCurrency.value === 'USD' ? 'MWK' : 'USD';
                    recalc();
                    amountEl.focus();
                });
                amountEl.addEventListener('input', recalc);
                refreshBtn.addEventListener('click', refreshRate);
                recalc();
            })();
        </script>
        @include('partials.mobile-nav')
    </body>
</html>
