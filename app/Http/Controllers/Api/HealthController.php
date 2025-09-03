<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Check application health status.
     */
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'connected';
        } catch (\Exception $e) {
            $checks['database'] = 'failed';
            $status = 'unhealthy';
        }

        // Redis check
        try {
            Redis::ping();
            $checks['redis'] = 'connected';
        } catch (\Exception $e) {
            $checks['redis'] = 'failed';
            $status = 'unhealthy';
        }

        // Memory check
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
        $checks['memory_usage'] = round($memoryUsage, 2) . ' MB';

        $statusCode = $status === 'healthy' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $statusCode);
    }
}
