<?php

namespace App\Http\Controllers;

use App\Domain\FX\FxSpotRateService;
use Illuminate\Http\JsonResponse;

class FxSpotRateController extends Controller
{
    public function __invoke(FxSpotRateService $fx): JsonResponse
    {
        $spot = $fx->getMwkPerUsd();

        return response()->json([
            'mwk_per_usd' => round($spot['rate'], 6),
            'as_of' => $spot['as_of'],
            'source' => $spot['source'],
        ]);
    }
}
