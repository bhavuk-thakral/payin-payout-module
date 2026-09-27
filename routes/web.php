<?php

use Illuminate\Support\Facades\Route;

// Backpack registers its own /admin routes automatically once installed
// (php artisan backpack:install). This file is intentionally minimal —
// this assignment's focus is the API + admin CRUD, not a public website.

Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
        'docs' => 'See README.md for API usage and /admin for the Backpack panel.',
    ]);
});
