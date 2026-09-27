<?php

use App\Http\Controllers\Api\PayinController;
use App\Http\Controllers\Api\PayoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes below require a valid merchant X-API-KEY header
| (see App\Http\Middleware\EnsureMerchantApiKey).
*/

Route::prefix('v1')->middleware('merchant.api')->group(function () {
    Route::post('/payins', [PayinController::class, 'store']);
    Route::get('/payins', [PayinController::class, 'index']);
    Route::get('/payins/{transaction_id}', [PayinController::class, 'show']);

    Route::post('/payouts', [PayoutController::class, 'store']);
    Route::get('/payouts', [PayoutController::class, 'index']);
    Route::get('/payouts/{transaction_id}', [PayoutController::class, 'show']);
});
