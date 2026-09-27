<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;

class EnsureMerchantApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Missing X-API-KEY header',
            ], 401);
        }

        $merchant = Merchant::where('api_key', $apiKey)->first();

        if (! $merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }

        if (! $merchant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant is not active',
            ], 403);
        }

        $request->attributes->set('merchant', $merchant);

        return $next($request);
    }
}