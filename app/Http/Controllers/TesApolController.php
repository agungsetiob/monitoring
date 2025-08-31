<?php

namespace App\Http\Controllers;

use App\Services\TesApolApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TesApolController extends Controller
{
    protected $apiService;

    public function __construct(TesApolApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Test API connection and configuration
     */
    public function testConnection()
    {
        try {
            $result = $this->apiService->testEndpoint();

            return response()->json([
                'success' => true,
                'message' => 'Connection test completed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Debug method untuk testing konfigurasi
     */
    public function debugConfig()
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $config = [
            'base_url' => config('services.apol.base_url'),
            'cons_id' => config('services.apol.cons_id'),
            'secret_key' => config('services.apol.secret_key') ? '***set***' : 'NOT SET',
            'user_key' => config('services.apol.user_key') ? '***set***' : 'NOT SET',
            'timeout' => config('services.apol.timeout', 30),
        ];

        return response()->json([
            'success' => true,
            'message' => 'APOL Configuration Debug',
            'config' => $config,
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Test endpoint method
     */
    public function testEndpoint()
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        try {
            $result = $this->apiService->testEndpoint();

            return response()->json([
                'success' => true,
                'message' => 'Endpoint test completed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test authentication dengan sample data
     */
    public function testAuthentication()
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        try {
            $result = $this->apiService->testAuthentication();

            return response()->json([
                'success' => true,
                'message' => 'Authentication test completed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test dengan format dokumentasi BPJS
     */
    public function testDocumentationFormat()
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        try {
            $result = $this->apiService->attemptDocumentationFormat(
                '1712A001',
                '0',
                'TGLPELSJP,TGLRSP',
                '2025-08-01 00:00:00',
                '2025-08-31 23:59:59'
            );

            return response()->json([
                'success' => true,
                'message' => 'Documentation format test completed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Documentation format test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}