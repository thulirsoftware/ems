<?php

Route::prefix('web/v1')->middleware('web')->group(function () {
    require __DIR__.'/api_shared.php';
});

Route::get('web/v1/csrf-token', function () {
    return response()->json([
        'token' => csrf_token()
    ]);
});
