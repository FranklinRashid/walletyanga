<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Convert MWK to USD · Wallet Yanga</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="wy-convert-page bg-[#f4f5f7] text-zinc-950 antialiased">
        <main class="wy-shell wy-shell-compact wy-convert-shell">
            <nav class="wy-topbar wy-convert-topbar">
                <a href="{{ route('wallet.dashboard') }}" class="wy-add-money-back" aria-label="Back to dashboard">←</a>
                <h1 class="wy-page-title">Convert</h1>
                <span class="wy-add-money-nav-spacer" aria-hidden="true"></span>
            </nav>

            @if (session('status') || $errors->any())
                <div class="wy-convert-alerts mt-5 space-y-3">
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

            <section class="wy-convert-layout mt-5">
                <section class="wy-add-money-panel wy-convert-panel">
                    <div class="wy-convert-wallets">
                        <div class="wy-wallet-card wy-convert-wallet">
                            <span class="wy-wallet-orb" aria-hidden="true"></span>
                            <div>
                                <p class="wy-wallet-name">MWK wallet</p>
                                <span class="sr-only">MWK Balance</span>
                                <p class="wy-wallet-subtitle">{{ number_format($mwkWallet->cached_balance_minor / 100, 2) }} available</p>
                            </div>
                            <span class="wy-wallet-code">From</span>
                        </div>
                        <div class="wy-wallet-card wy-convert-wallet">
                            <span class="wy-wallet-orb wy-wallet-orb-usd" aria-hidden="true"></span>
                            <div>
                                <p class="wy-wallet-name">USD wallet</p>
                                <span class="sr-only">USD Balance</span>
                                <p class="wy-wallet-subtitle">{{ number_format($usdWallet->cached_balance_minor / 100, 2) }} available</p>
                            </div>
                            <span class="wy-wallet-code">To</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('wallet.convert.quote') }}" class="wy-convert-form mt-5 space-y-5">
                        @csrf

                        <div class="wy-amount-stage">
                            <label for="convert_amount" class="wy-amount-label" id="convert-amount-label">You're converting</label>
                            <div class="wy-amount-input-row">
                                <span class="wy-currency-symbol wy-currency-code wy-currency-symbol-long" id="convert-currency-code">MWK</span>
                                <input id="convert_amount" name="mwk_amount" type="number" inputmode="decimal" step="0.01" min="{{ number_format($minMwkMinor / 100, 2, '.', '') }}" required
                                    value="{{ old('mwk_amount') }}"
                                    class="wy-amount-input wy-convert-amount-input"
                                    placeholder="0">
                            </div>
                            <p id="convert-preview" class="wy-mwk-preview">≈ 0.00 USD</p>
                            <button type="button" class="wy-currency-switch" id="convert-switch" aria-label="Switch amount entry mode">
                                <span id="convert-switch-current">MWK</span>
                                <span aria-hidden="true">⇄</span>
                                <span id="convert-switch-next">USD</span>
                            </button>
                        </div>

                        <div class="wy-money-detail">
                            <span>Rate</span>
                            <span class="wy-money-detail-fill" aria-hidden="true"></span>
                            <span>1 USD ≈ <span id="convert-rate">{{ number_format($spot['rate'], 2) }}</span> MWK</span>
                        </div>

                        <div class="wy-money-detail wy-money-detail-muted">
                            <span>Minimum</span>
                            <span class="wy-money-detail-fill" aria-hidden="true"></span>
                            <span>{{ number_format($minMwkMinor / 100, 2) }} MWK</span>
                        </div>

                        <button type="submit" class="wy-button wy-button-primary wy-convert-submit w-full">
                            Convert MWK to dollars
                        </button>
                    </form>

                    <p class="wy-convert-empty" id="convert-empty-copy">Enter Kwacha to convert MWK to USD.</p>
                </section>
            </section>

            <section class="wy-convert-history mt-4">
                <h2 class="wy-convert-section-title">Recent conversions</h2>
                <div class="mt-3 divide-y divide-zinc-200">
                    @forelse ($recentConversions as $conversion)
                        <div class="py-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="font-semibold text-black">{{ number_format($conversion->quote->from_amount_minor / 100, 2) }} MWK → {{ number_format($conversion->quote->to_amount_minor / 100, 2) }} USD</span>
                                <span class="text-zinc-500">{{ $conversion->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">Rate {{ number_format((float) $conversion->quote->effective_rate, 4) }} · {{ $conversion->status }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-zinc-500">No conversions yet.</p>
                    @endforelse
                </div>
            </section>
        </main>
        <script>
            (function () {
                const rate = {{ json_encode((float) $spot['rate']) }};
                const minMwkAmount = {{ json_encode(number_format($minMwkMinor / 100, 2, '.', '')) }};
                let inputMode = 'MWK';

                const formEl = document.querySelector('.wy-convert-form');
                const amountEl = document.getElementById('convert_amount');
                const previewEl = document.getElementById('convert-preview');
                const submitBtn = formEl.querySelector('button[type="submit"]');
                const switchBtn = document.getElementById('convert-switch');
                const switchCurrent = document.getElementById('convert-switch-current');
                const switchNext = document.getElementById('convert-switch-next');
                const labelEl = document.getElementById('convert-amount-label');
                const currencyCodeEl = document.getElementById('convert-currency-code');
                const emptyCopyEl = document.getElementById('convert-empty-copy');

                function fmt(n, d) {
                    return Number(n).toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d });
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

                function syncModeUi() {
                    labelEl.textContent = inputMode === 'MWK' ? "You're converting" : "You want dollars";
                    currencyCodeEl.textContent = inputMode;
                    currencyCodeEl.classList.toggle('wy-currency-symbol-long', inputMode === 'MWK');
                    switchCurrent.textContent = inputMode;
                    switchNext.textContent = inputMode === 'MWK' ? 'USD' : 'MWK';
                    amountEl.min = inputMode === 'MWK' ? minMwkAmount : '0.01';
                    switchBtn.setAttribute('aria-label', inputMode === 'MWK' ? 'Enter USD instead' : 'Enter MWK instead');
                    if (emptyCopyEl) {
                        emptyCopyEl.textContent = inputMode === 'MWK'
                            ? 'Enter Kwacha to convert MWK to USD.'
                            : 'Enter Dollars to see the required Kwacha.';
                    }
                }

                function recalc() {
                    syncModeUi();
                    syncAmountSize();
                    const raw = parseFloat(amountEl.value);
                    if (!Number.isFinite(raw) || raw <= 0) {
                        previewEl.textContent = inputMode === 'MWK' ? '≈ 0.00 USD' : 'Requires 0.00 MWK';
                        submitBtn.textContent = 'Convert MWK to dollars';
                        return;
                    }

                    if (inputMode === 'MWK') {
                        previewEl.textContent = '≈ ' + fmt(raw / rate, 2) + ' USD';
                        submitBtn.textContent = 'Convert ' + fmt(raw, 2) + ' MWK';
                        return;
                    }

                    const mwkNeeded = raw * rate;
                    previewEl.textContent = 'Requires ' + fmt(mwkNeeded, 2) + ' MWK';
                    submitBtn.textContent = 'Convert ' + fmt(mwkNeeded, 2) + ' MWK';
                }

                function setMode(nextMode) {
                    inputMode = nextMode;
                    recalc();
                    amountEl.focus();
                }

                amountEl.addEventListener('input', recalc);
                amountEl.addEventListener('keyup', recalc);
                amountEl.addEventListener('change', recalc);
                amountEl.addEventListener('paste', function () {
                    window.setTimeout(recalc, 0);
                });
                switchBtn.addEventListener('click', function () {
                    setMode(inputMode === 'MWK' ? 'USD' : 'MWK');
                });
                formEl.addEventListener('submit', function () {
                    if (inputMode !== 'USD') {
                        return;
                    }

                    const raw = parseFloat(amountEl.value);
                    if (Number.isFinite(raw) && raw > 0) {
                        amountEl.value = (raw * rate).toFixed(2);
                    }
                });
                recalc();
                window.setTimeout(recalc, 0);
                window.setTimeout(recalc, 150);
            })();
        </script>
        @include('partials.mobile-nav')
    </body>
</html>
