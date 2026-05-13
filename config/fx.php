<?php

return [

    /*
    |--------------------------------------------------------------------------
    | USD → MWK spot reference
    |--------------------------------------------------------------------------
    |
    | Live rates are fetched from a public JSON feed (updated daily) and
    | cached. If the request fails, FX_FALLBACK_MWK_PER_USD is used.
    | See: https://github.com/fawazahmed0/exchange-api (Cloudflare mirror).
    |
    */
    'spot_url' => env('FX_SPOT_URL', 'https://latest.currency-api.pages.dev/v1/currencies/usd.json'),

    'spot_cache_ttl_seconds' => (int) env('FX_SPOT_CACHE_TTL', 900),

    'fallback_mwk_per_usd' => (float) env('FX_FALLBACK_MWK_PER_USD', 1700.0),

    'min_deposit_mwk_minor' => (int) env('FX_MIN_DEPOSIT_MWK_MINOR', 10_000),

    'max_deposit_mwk_minor' => (int) env('FX_MAX_DEPOSIT_MWK_MINOR', 50_000_000),

    'conversion_spread_bps' => (int) env('FX_CONVERSION_SPREAD_BPS', 150),

    'min_conversion_mwk_minor' => (int) env('FX_MIN_CONVERSION_MWK_MINOR', 1_000),

];
