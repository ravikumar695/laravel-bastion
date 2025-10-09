<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bastion API Routes
|--------------------------------------------------------------------------
|
| These routes are only registered if you enable them in the config file.
| Set 'routes.enabled' => true in config/bastion.php
|
*/

// Example routes - customize as needed
Route::middleware('bastion')->group(function (): void {
    Route::get('/tokens', fn() => auth()->user()->bastionTokens);
});
