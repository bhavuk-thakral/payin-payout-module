<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayinRequest;
use App\Models\Payin;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayinController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    /**
     * POST /api/v1/payins
     * Initiates a new pay-in for the authenticated merchant.
     */
    public function store(StorePayinRequest $request)
    {
        $merchant = $request->attributes->get('merchant');

        try {
            $payin = $this->paymentService->initiatePayin($merchant, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Pay-in initiated',
                'data' => [
                    'transaction_id' => $payin->transaction_id,
                    'status' => $payin->status,
                    'amount' => $payin->amount,
                    'currency' => $payin->currency,
                    'created_at' => $payin->created_at,
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('single')->error('Payin initiation failed', [
                'merchant_id' => $merchant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not initiate pay-in',
            ], 500);
        }
    }

    /**
     * GET /api/v1/payins/{transaction_id}
     * Check the status of a previously initiated pay-in.
     */
    public function show(Request $request, string $transactionId)
    {
        $merchant = $request->attributes->get('merchant');

        $payin = Payin::where('transaction_id', $transactionId)
            ->where('merchant_id', $merchant->id)
            ->first();

        if (! $payin) {
            return response()->json(['success' => false, 'message' => 'Pay-in not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $payin]);
    }

    /**
     * GET /api/v1/payins
     * List pay-ins for the authenticated merchant (optionally filtered by status).
     */
    public function index(Request $request)
    {
        $merchant = $request->attributes->get('merchant');

        $payins = Payin::where('merchant_id', $merchant->id)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $payins]);
    }
}
