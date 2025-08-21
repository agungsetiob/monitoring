<?php

namespace App\Http\Controllers;

use App\Services\RencanaKontrolApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RencanaKontrolController extends Controller
{
    protected $apiService;

    public function __construct(RencanaKontrolApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    /**
     * Display the rencana kontrol index page.
     */
    public function index()
    {
        return inertia('RencanaKontrol/Index');
    }

    /**
     * Display the update form page for rencana kontrol.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function showUpdateForm(Request $request)
    {
        $searchData = $request->query('data') ? json_decode($request->query('data'), true) : [];
        
        return Inertia::render('RencanaKontrol/Update', [
            'searchData' => $searchData
        ]);
    }

    /**
     * Search rencana kontrol data from third party API.
     */
    public function cariData(Request $request)
    {
        $request->validate([
            'no_kartu' => 'required|string',
            'tanggal_sep' => 'required|date',
        ]);

        try {
            // Panggil API pihak ketiga untuk cari data
            $response = $this->apiService->cariData(
                $request->no_kartu,
                $request->tanggal_sep
            );

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data rencana kontrol berhasil ditemukan',
                    'data' => $response['data'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Data tidak ditemukan',
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            Log::error('Error saat mencari data rencana kontrol: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mencari data',
                'error' => $e->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Update rencana kontrol data via third party API.
     */
    public function updateRencanaKontrol(Request $request)
    {
        $request->validate([
            'no_sep' => 'required|string',
            'no_kartu' => 'required|string',
            'tanggal_rencana' => 'required|date',
            'poli_kontrol' => 'required|string',
            'dokter' => 'required|string',
            'user' => 'required|string',
        ]);

        try {
            // Panggil API pihak ketiga untuk update rencana kontrol
            $response = $this->apiService->updateRencanaKontrol([
                'no_sep' => $request->no_sep,
                'no_kartu' => $request->no_kartu,
                'tanggal_rencana' => $request->tanggal_rencana,
                'poli_kontrol' => $request->poli_kontrol,
                'dokter' => $request->dokter,
                'user' => $request->user,
            ]);

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Rencana kontrol berhasil diupdate',
                    'data' => $response['data'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Gagal mengupdate rencana kontrol',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            Log::error('Error saat mengupdate rencana kontrol: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate rencana kontrol',
                'error' => $e->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Get list of available poli for rencana kontrol.
     */
    public function getPoliList()
    {
        try {
            // Panggil API pihak ketiga untuk mendapatkan daftar poli
            $response = $this->apiService->getPoliList();

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Daftar poli berhasil diambil',
                    'data' => $response['data'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil daftar poli',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            Log::error('Error saat mengambil daftar poli: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil daftar poli',
                'error' => $e->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Get list of available doctors for specific poli.
     */
    public function getDokterList(Request $request)
    {
        $request->validate([
            'kode_poli' => 'required|string',
            'tanggal' => 'required|date',
        ]);

        try {
            // Panggil API pihak ketiga untuk mendapatkan daftar dokter
            $response = $this->apiService->getDokterList(
                $request->kode_poli,
                $request->tanggal
            );

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Daftar dokter berhasil diambil',
                    'data' => $response['data'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil daftar dokter',
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            Log::error('Error saat mengambil daftar dokter: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil daftar dokter',
                'error' => $e->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }


}