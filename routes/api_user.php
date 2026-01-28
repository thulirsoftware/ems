<?php

use App\Http\Controllers\Api\User\UserAuthController;

// Auth
Route::prefix('user')->group(function () {
    Route::post('register', [UserAuthController::class, 'register']);
    Route::post('login', [UserAuthController::class, 'login']);

    Route::middleware('user')->group(function () {
        Route::get('me', fn() => auth('users')->user());
        Route::post('logout', [UserAuthController::class, 'logout']);
    });
});
