<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayoutRequest;
use App\Models\Payout;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayoutController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    /**
     * POST /api/v1/payouts
     * Initiates a new payout for the authenticated merchant.
     * Funds are reserved (debited) immediately; see PaymentService::initiatePayout().
     */
    public function store(StorePayoutRequest $request)
    {
        $merchant = $request->attributes->get('merchant');

        try {
            $payout = $this->paymentService->initiatePayout($merchant, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Payout initiated',
                'data' => [
                    'transaction_id' => $payout->transaction_id,
                    'status' => $payout->status,
                    'amount' => $payout->amount,
                    'currency' => $payout->currency,
                    'created_at' => $payout->created_at,
                ],
            ], 201);
        } catch (\RuntimeException $e) {
            // e.g. insufficient wallet balance
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::channel('single')->error('Payout initiation failed', [
                'merchant_id' => $merchant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not initiate payout',
            ], 500);
        }
    }

    /**
     * GET /api/v1/payouts/{transaction_id}
     */
    public function show(Request $request, string $transactionId)
    {
        $merchant = $request->attributes->get('merchant');

        $payout = Payout::where('transaction_id', $transactionId)
            ->where('merchant_id', $merchant->id)
            ->first();

        if (! $payout) {
            return response()->json(['success' => false, 'message' => 'Payout not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $payout]);
    }

    /**
     * GET /api/v1/payouts
     */
    public function index(Request $request)
    {
        $merchant = $request->attributes->get('merchant');

        $payouts = Payout::where('merchant_id', $merchant->id)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $payouts]);
    }
}
