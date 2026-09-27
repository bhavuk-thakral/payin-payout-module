<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
        'docs' => 'See README.md for API usage and /admin for the Backpack panel.',
    ]);
});
