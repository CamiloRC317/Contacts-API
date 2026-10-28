<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'name' => 'malquiel',
            'app' => config('app.name'),
            'status' => '0.1.0',
        ]);
    })->name('health');
});
