<?php

namespace App\Domain\FX;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class FxSpotRateService
{
    /**
     * @return array{rate: float, as_of: ?string, source: string}
     */
    public function getMwkPerUsd(): array
    {
        $ttl = max(60, (int) config('fx.spot_cache_ttl_seconds', 900));
        $key = 'fx:spot:mwk_per_usd:v1';

        return Cache::remember($key, $ttl, function (): array {
            $url = (string) config('fx.spot_url');

            try {
                $response = Http::timeout(12)
                    ->acceptJson()
                    ->get($url);

                if ($response->successful()) {
                    $mwk = data_get($response->json(), 'usd.mwk');
                    if (is_numeric($mwk) && (float) $mwk > 0) {
                        return [
                            'rate' => (float) $mwk,
                            'as_of' => data_get($response->json(), 'date'),
                            'source' => 'currency-api',
                        ];
                    }
                }
            } catch (Throwable) {
                // fall through to fallback
            }

            return [
                'rate' => (float) config('fx.fallback_mwk_per_usd', 1700),
                'as_of' => null,
                'source' => 'fallback',
            ];
        });
    }

    public function forgetCachedSpot(): void
    {
        Cache::forget('fx:spot:mwk_per_usd:v1');
    }
}
