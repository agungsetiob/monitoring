<?php

namespace App\Http\Controllers;

use App\Services\ApolApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ApolController extends Controller
{
    protected $apiService;

    public function __construct(ApolApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Display the APOL index page.
     */
    public function index()
    {
        return inertia('Apol/Index', [
            'defaultKdppk' => config('services.apol.apotek_ppk')
        ]);
    }

    public function hapusObat(Request $request)
    {
        $data = [
            'nosepapotek' => $request->input('nosepapotek') ?: $request->input('nosjp') ?: $request->input('nosep'),
            'noresep' => $request->input('noresep'),
            'kodeobat' => $request->input('kodeobat') ?: $request->input('kdobat'),
            'tipeobat' => strtoupper((string) $request->input('tipeobat', 'N')),
        ];

        $v = \Validator::make($data, [
            'nosepapotek' => 'required|string',
            'noresep' => 'required|string',
            'kodeobat' => 'required|string',
            'tipeobat' => 'in:N,K',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal: ' . $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $resp = $this->apiService->hapusObat($data['nosepapotek'], $data['noresep'], $data['kodeobat'], $data['tipeobat']);

        return $this->handleApiResponse($resp);
    }


    public function getDaftarPelayananObat(Request $request, $nosep = null)
    {
        // Ambil dari path param atau query param
        $nosep = $nosep ?: $request->query('nosep');

        // Validasi sederhana
        $validator = \Validator::make(['nosep' => $nosep], [
            'nosep' => 'required|string',
        ], [
            'nosep.required' => 'Nomor SEP Apotek/SJP (nosep) harus diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            \Log::info('APOL getDaftarPelayananObat', ['nosep' => $nosep]);

            $response = $this->apiService->getDaftarPelayananObat($nosep);

            // Pakai handler universal yang sudah kamu buat
            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            \Log::error('Error getDaftarPelayananObat: ' . $e->getMessage(), [
                'nosep' => $nosep,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar pelayanan obat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Normalisasi listobat yang kadang object tunggal, kadang array.
     * @param mixed $list
     * @return array<int, array<string,mixed>>
     */
    private function normalizeListObat($list): array
    {
        if (empty($list))
            return [];

        // jika server kadang kirim object tunggal
        if (
            is_array($list) && (
                isset($list['kodeobat']) || isset($list['kdobat']) ||
                isset($list['KdObat']) || isset($list['KDOBAT']) ||
                isset($list['kd_obat'])
            )
        ) {
            $list = [$list];
        }

        if (!is_array($list))
            return [];

        $out = [];
        foreach ($list as $it) {
            if (!is_array($it))
                continue;

            $kode = $it['kodeobat'] ?? $it['kdobat'] ?? $it['KdObat'] ?? $it['KDOBAT'] ?? $it['kd_obat'] ?? null;
            $tipe = $it['tipeobat'] ?? $it['tipeObat'] ?? $it['tipe_obat'] ?? 'N';

            $it['kodeobat'] = (string) $kode;
            $it['tipeobat'] = strtoupper((string) $tipe);

            $out[] = $it;
        }

        return array_values($out);
    }


    /**
     * Handle AJAX request untuk mendapatkan daftar resep.
     */
    public function ajaxGetDaftarResep(Request $request)
    {
        $request->merge([
            'kdppk' => $request->kdppk ?: config('services.apol.apotek_ppk')
        ]);
        // Validation dengan pesan error yang lebih jelas
        $validator = \Validator::make($request->all(), [
            'kdppk' => 'required|string|max:20',
            'KdJnsObat' => 'nullable|string|in:0,1,2,3',
            'JnsTgl' => 'required|string|in:TGLPELSJP,TGLRSP',
            'TglMulai' => 'required|string',
            'TglAkhir' => 'required|string',
        ], [
            'kdppk.required' => 'Kode PPK harus diisi',
            'kdppk.max' => 'Kode PPK maksimal 20 karakter',
            'JnsTgl.in' => 'Jenis tanggal harus TGLPELSJP Atau TGLRSP',
            'TglMulai.required' => 'Tanggal mulai harus diisi',
            'TglAkhir.required' => 'Tanggal akhir harus diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Set default value untuk KdJnsObat jika tidak diisi
            $kdJnsObat = $request->KdJnsObat ?: "0";

            // Log input data
            Log::info('APOL Request Input', [
                'kdppk' => $request->kdppk,
                'KdJnsObat' => $kdJnsObat,
                'JnsTgl' => $request->JnsTgl,
                'TglMulai' => $request->TglMulai,
                'TglAkhir' => $request->TglAkhir,
            ]);

            // Call the main service method (akan mencoba semua format otomatis)
            $response = $this->apiService->getDaftarResep(
                $request->kdppk,
                $kdJnsObat,
                $request->JnsTgl,
                $request->TglMulai,
                $request->TglAkhir
            );

            // Handle response dari service
            return $this->handleApiResponse($response);

        } catch (\Exception $e) {
            Log::error('Error saat mengambil daftar resep via AJAX: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil daftar resep',
                'error' => $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }

    /**
     * Handle API response dari service
     */
    private function handleApiResponse($response)
    {
        // Check response format
        if (isset($response['metaData'])) {
            $code = $response['metaData']['code'] ?? null;
            $message = $response['metaData']['message'] ?? 'Unknown response';

            if ($code === '200' || $code === 200) {
                return response()->json([
                    'success' => true,
                    'message' => 'Daftar resep berhasil diambil',
                    'data' => $response['response'] ?? [],
                    'metaData' => $response['metaData'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'code' => $code,
                    'metaData' => $response['metaData'],
                ], $this->getHttpStatusCode($code));
            }
        }

        // Handle response tanpa metaData
        if (isset($response['success'])) {
            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Daftar resep berhasil diambil',
                    'data' => $response['data'] ?? $response['response'] ?? [],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Request gagal',
                    'error' => $response['error'] ?? null,
                ], 400);
            }
        }

        // Handle direct data response
        if (isset($response['response']) || isset($response['data'])) {
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diterima dari server',
                'data' => $response['response'] ?? $response['data'] ?? $response,
            ]);
        }

        // Fallback: assume it's data if it's an array
        if (is_array($response)) {
            return response()->json([
                'success' => true,
                'message' => 'Data diterima dari server',
                'data' => $response,
            ]);
        }

        // If nothing matches, return error
        return response()->json([
            'success' => false,
            'message' => 'Format response tidak dikenali',
            'raw_response' => $response,
        ], 500);
    }

    /**
     * Get HTTP status code from BPJS response code
     */
    private function getHttpStatusCode($code)
    {
        switch ($code) {
            case '200':
            case 200:
                return 200;
            case '400':
            case 400:
                return 400;
            case '404':
            case 404:
                return 404;
            case '500':
            case 500:
                return 500;
            default:
                return 400;
        }
    }

    /**
     * Get daftar resep dari BPJS APOL API (alias untuk ajaxGetDaftarResep).
     */
    public function getDaftarResep(Request $request)
    {
        return $this->ajaxGetDaftarResep($request);
    }

    /**
     * Get summary statistik daftar resep.
     */
    public function getSummaryResep(Request $request)
    {
        $request->validate([
            'kdppk' => 'required|string',
            'KdJnsObat' => 'nullable|string',
            'JnsTgl' => 'required|string|in:TGLPELSJP,TGLRSP',
            'TglMulai' => 'required|string',
            'TglAkhir' => 'required|string',
        ]);

        try {
            // Set default value untuk KdJnsObat jika tidak diisi
            $kdJnsObat = $request->KdJnsObat ?: "0";

            $response = $this->apiService->getDaftarResep(
                $request->kdppk,
                $kdJnsObat,
                $request->JnsTgl,
                $request->TglMulai,
                $request->TglAkhir
            );

            // Check if successful and get data
            $isSuccessful = false;
            $data = [];

            if (isset($response['metaData']) && ($response['metaData']['code'] === '200' || $response['metaData']['code'] === 200)) {
                $isSuccessful = true;
                $data = $response['response'] ?? [];
            } elseif (isset($response['success']) && $response['success']) {
                $isSuccessful = true;
                $data = $response['data'] ?? $response['response'] ?? [];
            }

            if ($isSuccessful) {
                // Hitung summary statistik
                $summary = [
                    'total_resep' => count($data),
                    'total_obat' => 0,
                    'total_biaya' => 0,
                    'by_jenis_obat' => [],
                    'by_periode' => [
                        'tanggal_mulai' => $request->TglMulai,
                        'tanggal_akhir' => $request->TglAkhir,
                    ],
                ];

                // Proses data untuk mendapatkan statistik lebih detail
                if (is_array($data) && count($data) > 0) {
                    $jenisObat = [];
                    $totalBiaya = 0;

                    foreach ($data as $item) {
                        // Count by jenis obat
                        $jenis = $item['jenisObat'] ?? 'Unknown';
                        $jenisObat[$jenis] = ($jenisObat[$jenis] ?? 0) + 1;

                        // Sum total biaya
                        if (isset($item['totalBiaya'])) {
                            $totalBiaya += (float) $item['totalBiaya'];
                        }
                    }

                    $summary['by_jenis_obat'] = $jenisObat;
                    $summary['total_biaya'] = $totalBiaya;
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Summary resep berhasil diambil',
                    'data' => $data,
                    'summary' => $summary,
                    'metaData' => $response['metaData'] ?? null,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Data tidak ditemukan',
                    'code' => $response['metaData']['code'] ?? null,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error saat mengambil summary resep: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil summary resep',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Hapus resep berdasarkan nosjp, refasalsjp, dan noresep.
     */
    public function hapusResep(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'nosjp' => 'required|string',
            'refasalsjp' => 'required|string',
            'noresep' => 'required|string',
        ], [
            'nosjp.required' => 'No SJP harus diisi',
            'refasalsjp.required' => 'Ref Asal SJP harus diisi',
            'noresep.required' => 'No Resep harus diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            Log::info('APOL HapusResep Request Input', [
                'nosjp' => $request->nosjp,
                'refasalsjp' => $request->refasalsjp,
                'noresep' => $request->noresep,
            ]);

            $response = $this->apiService->hapusResep(
                $request->nosjp,
                $request->refasalsjp,
                $request->noresep
            );

            return $this->handleApiResponse($response);

        } catch (\Exception $e) {
            Log::error('Error saat hapus resep via AJAX: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus resep',
                'error' => $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }

    /**
     * Show form untuk filter daftar resep.
     */
    public function showFilterForm()
    {
        return Inertia::render('Apol/FilterResep');
    }
}