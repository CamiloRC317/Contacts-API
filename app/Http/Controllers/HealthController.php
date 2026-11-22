<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Estado del servicio.
     *
     * @unauthenticated
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'version' => '0.2.0',
        ]);
    }
}
