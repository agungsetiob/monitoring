<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RencanaKontrolApiService
{
    private $baseUrl;
    private $consId;
    private $secretKey;
    private $userKey;
    private $timeout;

    public function __construct()
    {
        // Konfigurasi API dari config/services.php
        $this->baseUrl = config('services.rencana_kontrol.base_url');
        $this->consId = config('services.rencana_kontrol.cons_id');
        $this->secretKey = config('services.rencana_kontrol.secret_key');
        $this->userKey = config('services.rencana_kontrol.user_key');
        $this->timeout = config('services.rencana_kontrol.timeout', 30);
    }

    /**
     * Cari data rencana kontrol berdasarkan nomor kartu dan tanggal SEP.
     *
     * @param string $noKartu
     * @param string $tanggalSep
     * @return array
     */
    public function cariData($noKartu, $tanggalSep)
    {
        try {
            $endpoint = '/RencanaKontrol/ListRencanaKontrol';
            $params = [
                'noka' => $noKartu,
                'tglSEP' => $tanggalSep,
            ];

            $response = $this->makeRequest('GET', $endpoint, $params);

            if ($response['success']) {
                return [
                    'success' => true,
                    'data' => $this->transformCariDataResponse($response['data']),
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Data tidak ditemukan',
            ];
        } catch (\Exception $e) {
            Log::error('Error saat mencari data rencana kontrol: ' . $e->getMessage(), [
                'no_kartu' => $noKartu,
                'tanggal_sep' => $tanggalSep,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mencari data',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update rencana kontrol.
     *
     * @param array $data
     * @return array
     */
    public function updateRencanaKontrol($data)
    {
        try {
            $endpoint = '/RencanaKontrol/UpdateRencanaKontrol';
            $payload = [
                'noSEP' => $data['no_sep'],
                'noKartu' => $data['no_kartu'],
                'tglRencanaKontrol' => $data['tanggal_rencana'],
                'poliKontrol' => $data['poli_kontrol'],
                'dokter' => $data['dokter'],
                'user' => $data['user'],
            ];

            $response = $this->makeRequest('POST', $endpoint, $payload);

            if ($response['success']) {
                return [
                    'success' => true,
                    'data' => $this->transformUpdateResponse($response['data']),
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Gagal mengupdate rencana kontrol',
            ];
        } catch (\Exception $e) {
            Log::error('Error saat mengupdate rencana kontrol: ' . $e->getMessage(), [
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate rencana kontrol',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil daftar poli.
     *
     * @return array
     */
    public function getPoliList()
    {
        try {
            // Cache daftar poli selama 1 jam
            return Cache::remember('poli_list', 3600, function () {
                $endpoint = '/referensi/poli';
                $response = $this->makeRequest('GET', $endpoint);

                if ($response['success']) {
                    return [
                        'success' => true,
                        'data' => $this->transformPoliListResponse($response['data']),
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Gagal mengambil daftar poli',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error saat mengambil daftar poli: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil daftar poli',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil daftar dokter berdasarkan poli dan tanggal.
     *
     * @param string $kodePoli
     * @param string $tanggal
     * @return array
     */
    public function getDokterList($kodePoli, $tanggal)
    {
        try {
            $endpoint = '/referensi/dokter';
            $params = [
                'jnsPelayanan' => '2', // Rawat Jalan
                'tglPelayanan' => $tanggal,
                'spesialis' => $kodePoli,
            ];

            $response = $this->makeRequest('GET', $endpoint, $params);

            if ($response['success']) {
                return [
                    'success' => true,
                    'data' => $this->transformDokterListResponse($response['data']),
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal mengambil daftar dokter',
            ];
        } catch (\Exception $e) {
            Log::error('Error saat mengambil daftar dokter: ' . $e->getMessage(), [
                'kode_poli' => $kodePoli,
                'tanggal' => $tanggal,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil daftar dokter',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make HTTP request to third party API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    private function makeRequest($method, $endpoint, $data = [])
    {
        $url = $this->baseUrl . $endpoint;

        // Generate headers untuk setiap request
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-cons-id' => $this->consId,
            'X-timestamp' => now()->timestamp,
            'X-signature' => $this->generateSignature(),
            'user_key' => $this->userKey,
        ];

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->retry(3, 1000) // Retry 3 kali dengan delay 1 detik
            ->when($method === 'GET', function ($http) use ($url, $data) {
                return $http->get($url, $data);
            })
            ->when($method === 'POST', function ($http) use ($url, $data) {
                return $http->post($url, $data);
            });

        if ($response->successful()) {
            $responseData = $response->json();
            
            // Sesuaikan dengan format response API BPJS
            if (isset($responseData['metaData']['code']) && $responseData['metaData']['code'] === '200') {
                return [
                    'success' => true,
                    'data' => $responseData['response'] ?? [],
                    'message' => $responseData['metaData']['message'] ?? 'Success',
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['metaData']['message'] ?? 'Request failed',
                'code' => $responseData['metaData']['code'] ?? null,
            ];
        }

        return [
            'success' => false,
            'message' => 'HTTP request failed: ' . $response->status(),
            'code' => $response->status(),
        ];
    }

    /**
     * Generate signature untuk autentikasi API.
     *
     * @return string
     */
    private function generateSignature()
    {
        // Implementasi signature sesuai dengan dokumentasi API BPJS
        $timestamp = now()->timestamp;

        $data = $this->consId . '&' . $timestamp;
        $signature = hash_hmac('sha256', $data, $this->secretKey, true);
        
        return base64_encode($signature);
    }

    /**
     * Transform response data dari cari data.
     *
     * @param array $data
     * @return array
     */
    private function transformCariDataResponse($data)
    {
        // Sesuaikan dengan struktur response API yang sebenarnya
        return [
            'no_kartu' => $data['noKartu'] ?? '',
            'nama_peserta' => $data['nama'] ?? '',
            'no_sep' => $data['noSEP'] ?? '',
            'tanggal_sep' => $data['tglSEP'] ?? '',
            'poli_asal' => $data['poliAsal'] ?? '',
            'diagnosa' => $data['diagnosa'] ?? '',
            'terapi' => $data['terapi'] ?? '',
        ];
    }

    /**
     * Transform response data dari update rencana kontrol.
     *
     * @param array $data
     * @return array
     */
    private function transformUpdateResponse($data)
    {
        return [
            'no_surat_kontrol' => $data['noSuratKontrol'] ?? '',
            'tanggal_terbit' => $data['tglTerbitKontrol'] ?? '',
            'tanggal_rencana' => $data['tglRencanaKontrol'] ?? '',
            'poli_kontrol' => $data['namaPoliKontrol'] ?? '',
            'dokter' => $data['namaDokter'] ?? '',
        ];
    }

    /**
     * Transform response data dari daftar poli.
     *
     * @param array $data
     * @return array
     */
    private function transformPoliListResponse($data)
    {
        return collect($data)->map(function ($item) {
            return [
                'kode' => $item['kode'] ?? '',
                'nama' => $item['nama'] ?? '',
            ];
        })->toArray();
    }

    /**
     * Transform response data dari daftar dokter.
     *
     * @param array $data
     * @return array
     */
    private function transformDokterListResponse($data)
    {
        return collect($data)->map(function ($item) {
            return [
                'kode' => $item['kode'] ?? '',
                'nama' => $item['nama'] ?? '',
            ];
        })->toArray();
    }
}