<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'name' => 'contact-api',
            'app' => config('app.name'),
            'status' => '0.1.0',
        ]);
    })->name('health');
    // Rutas de autenticación
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
    });
    //Rutas de contactos
    Route::prefix('contacts')->middleware('auth:sanctum')->group(function () {
        Route::get('/me', [\App\Http\Controllers\ContactController::class, 'getContact'])->name('contacts.me');
        Route::delete('/{id}', [\App\Http\Controllers\ContactController::class, 'deleteContact'])->name('contacts.delete');
    });
});
