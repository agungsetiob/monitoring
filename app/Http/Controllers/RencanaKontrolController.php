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
     * @return \Inertia\Response
     */
    public function showUpdateForm(Request $request)
    {
        $searchData = $request->query('data') ? json_decode($request->query('data'), true) : [];
        
        return Inertia::render('RencanaKontrol/Update', [
            'searchData' => $searchData
        ]);
    }

    /**
     * Search rencana kontrol data from BPJS API.
     */
    public function cariData(Request $request)
    {
        $request->validate([
            'no_kartu' => 'required|string',
            'bulan' => 'required|string',
            'tahun' => 'required|string',
            'filter' => 'nullable|string',
        ]);

        try {
            // Panggil API BPJS untuk cari data rencana kontrol
            // Jika filter kosong atau null, gunakan default value 2
            $filter = $request->filter ?: "2";
            
            $response = $this->apiService->getListRencanaKontrolByNoKartu(
                $request->bulan,
                $request->tahun,
                $request->no_kartu,
                $filter
            );

            if (isset($response['metaData']) && $response['metaData']['code'] === '200') {
                return response()->json([
                    'success' => true,
                    'message' => 'Data rencana kontrol berhasil ditemukan',
                    'data' => $response['response'] ?? [],
                    'metaData' => $response['metaData'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['metaData']['message'] ?? 'Data tidak ditemukan',
                    'code' => $response['metaData']['code'] ?? null,
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
     * Insert new rencana kontrol via BPJS API.
     */
    public function insertRencanaKontrol(Request $request)
    {
        $request->validate([
            'noSEP' => 'required|string',
            'kodeDokter' => 'required|string',
            'poliKontrol' => 'required|string',
            'tglRencanaKontrol' => 'required|date',
            'user' => 'required|string',
        ]);

        try {
            // Panggil API BPJS untuk insert rencana kontrol
            $response = $this->apiService->insertRencanaKontrol([
                'noSEP' => $request->noSEP,
                'kodeDokter' => $request->kodeDokter,
                'poliKontrol' => $request->poliKontrol,
                'tglRencanaKontrol' => $request->tglRencanaKontrol,
                'user' => $request->user,
            ]);

            if (isset($response['metaData']) && $response['metaData']['code'] === '200') {
                return response()->json([
                    'success' => true,
                    'message' => 'Rencana kontrol berhasil dibuat',
                    'data' => $response['response'] ?? [],
                    'metaData' => $response['metaData'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['metaData']['message'] ?? 'Gagal membuat rencana kontrol',
                    'code' => $response['metaData']['code'] ?? null,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            Log::error('Error saat insert rencana kontrol: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat rencana kontrol',
                'error' => $e->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Update rencana kontrol data via BPJS API.
     */
    public function updateRencanaKontrol(Request $request)
    {
        $request->validate([
            'noSuratKontrol' => 'required|string',
            'noSEP' => 'required|string',
            'kodeDokter' => 'required|string',
            'poliKontrol' => 'required|string',
            'tglRencanaKontrol' => 'required|date',
            'user' => 'required|string',
        ]);

        try {
            // Panggil API BPJS untuk update rencana kontrol
            $response = $this->apiService->updateRencanaKontrol([
                'noSuratKontrol' => $request->noSuratKontrol,
                'noSEP' => $request->noSEP,
                'kodeDokter' => $request->kodeDokter,
                'poliKontrol' => $request->poliKontrol,
                'tglRencanaKontrol' => $request->tglRencanaKontrol,
                'user' => $request->user,
            ]);

            if (isset($response['metaData']) && $response['metaData']['code'] === '200') {
                return response()->json([
                    'success' => true,
                    'message' => 'Rencana kontrol berhasil diupdate',
                    'data' => $response['response'] ?? [],
                    'metaData' => $response['metaData'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['metaData']['message'] ?? 'Gagal mengupdate rencana kontrol',
                    'code' => $response['metaData']['code'] ?? null,
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
     * Get referensi dokter DPJP from BPJS API.
     */
    public function getReferensiDokter(Request $request)
    {
        $request->validate([
            'jnsPelayanan' => 'required|string',
            'tglPelayanan' => 'required|date',
            'spesialisKode' => 'required|string',
        ]);

        try {
            // Panggil API BPJS untuk mendapatkan referensi dokter
            $response = $this->apiService->getReferensiDokter(
                $request->jnsPelayanan,
                $request->tglPelayanan,
                $request->spesialisKode
            );

            if (isset($response['metaData']) && $response['metaData']['code'] === '200') {
                return response()->json([
                    'success' => true,
                    'message' => 'Referensi dokter berhasil diambil',
                    'data' => $response['response'] ?? [],
                    'metaData' => $response['metaData'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['metaData']['message'] ?? 'Gagal mengambil referensi dokter',
                    'code' => $response['metaData']['code'] ?? null,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            Log::error('Error saat mengambil referensi dokter: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil referensi dokter',
                'error' => $e->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Insert SEP version 2.0 via BPJS API.
     */
    public function insertSEP20(Request $request)
    {
        $request->validate([
            'noKartu' => 'required|string',
            'tglSep' => 'required|date',
            'ppkPelayanan' => 'required|string',
            'jnsPelayanan' => 'required|string',
            'klsRawat' => 'required|string',
            'noMR' => 'required|string',
            'rujukan' => 'required|array',
            'catatan' => 'nullable|string',
            'diagAwal' => 'required|string',
            'poli' => 'required|array',
            'cob' => 'required|array',
            'katarak' => 'required|array',
            'jaminan' => 'required|array',
            'tujuanKunj' => 'required|string',
            'flagProcedure' => 'required|string',
            'kdPenunjang' => 'nullable|string',
            'assesmentPel' => 'required|string',
            'skdp' => 'required|array',
            'dpjpLayan' => 'required|string',
            'noTelp' => 'required|string',
            'user' => 'required|string',
        ]);

        try {
            $response = $this->apiService->insertSEP20($request->all());

            if (isset($response['metaData']) && $response['metaData']['code'] === '200') {
                return response()->json([
                    'success' => true,
                    'message' => 'SEP 2.0 berhasil dibuat',
                    'data' => $response['response'] ?? [],
                    'metaData' => $response['metaData'],
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['metaData']['message'] ?? 'Gagal membuat SEP 2.0',
                    'code' => $response['metaData']['code'] ?? null,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            Log::error('Error saat insert SEP 2.0: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat SEP 2.0',
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
            'kodePoli' => 'required|string',
            'tanggal' => 'required|date',
        ]);

        try {
            $response = $this->apiService->getDokterList(
                $request->kodePoli,
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
                    'message' => $response['message'] ?? 'Gagal mengambil daftar dokter',
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