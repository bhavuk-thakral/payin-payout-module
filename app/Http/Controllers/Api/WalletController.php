<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    /**
     * GET /api/wallets/{merchant_id}
     */
    public function show(int $merchantId): JsonResponse
    {
        $wallet = Wallet::where('merchant_id', $merchantId)->first();

        if (!$wallet) {
            return response()->json(['success' => false, 'message' => 'Wallet not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $wallet]);
    }
}
