<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'name' => 'malquiel',
            'app' => config('app.name'),
            'status' => '0.1.0',
        ]);
    })->name('health');
     // Rutas de autenticación
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login');
});
