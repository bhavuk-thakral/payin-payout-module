<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backpack admin CRUD routes
|--------------------------------------------------------------------------
| After `php artisan backpack:install`, this file already exists —
| just append the four Route::crud() lines below to it (or replace
| the file with this one). All routes are prefixed with /admin and
| protected by Backpack's own auth middleware automatically.
*/

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () {
    Route::crud('merchant', 'MerchantCrudController');
    Route::crud('payin', 'PayinCrudController');
    Route::crud('payout', 'PayoutCrudController');
    Route::crud('wallet', 'WalletCrudController');
});
