<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
     * Membuat timestamp UTC dalam detik sesuai standar BPJS.
     *
     * @return string
     */
    private function makeTimestampSecondsUTC()
    {
        return (string) time();
    }

    /**
     * Generate X-signature: base64(HMAC-SHA256(consumerID&timestamp, consumerSecret))
     * Sesuai dokumentasi BPJS.
     *
     * @param string $consId
     * @param string $timestamp
     * @param string $secret
     * @return string
     */
    private function makeSignature($consId, $timestamp, $secret)
    {
        $data = $consId . '&' . $timestamp;
        return base64_encode(hash_hmac('sha256', $data, $secret, true));
    }

    /**
     * Decrypt dan decompress response BPJS.
     * 1) AES-256-CBC dengan key = SHA256(consId + secret + timestamp)
     *    IV = 16 byte pertama dari hash yang sama
     * 2) Hasil masih kompresi LZString → decompress
     *
     * @param string $cipherBase64
     * @param string $consId
     * @param string $secret
     * @param string $timestamp
     * @return array
     */
    private function decryptAndDecompress($cipherBase64, $consId, $secret, $timestamp)
    {
        try {
            $keyMaterial = $consId . $secret . $timestamp;
            
            // Decrypt using the simplified method
            $decrypted = $this->stringDecrypt($keyMaterial, $cipherBase64);
            
            if ($decrypted === false) {
                throw new \Exception('Gagal decrypt data');
            }

            // Decompress LZString
            $jsonText = $this->decompress($decrypted);
            
            if (!$jsonText) {
                throw new \Exception('Gagal decompress LZString: hasil kosong/invalid');
            }

            return json_decode($jsonText, true);
        } catch (\Exception $e) {
            Log::error('Error decrypt and decompress: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * String decrypt function using AES-256-CBC.
     *
     * @param string $key
     * @param string $string
     * @return string|false
     */
    private function stringDecrypt($key, $string)
    {
        $encrypt_method = 'AES-256-CBC';
        
        // hash
        $key_hash = hex2bin(hash('sha256', $key));
        
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);
        
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
        
        return $output;
    }
    
    /**
     * LZString decompress function using nullpunkt/lz-string-php library.
     *
     * @param string $string
     * @return string|null
     */
    private function decompress($string)
    {
        return \LZCompressor\LZString::decompressFromEncodedURIComponent($string);
    }

    /**
     * Ambil Data Nomor Surat Kontrol berdasarkan No Kartu.
     *
     * @param string $bulan "01" .. "12"
     * @param string $tahun "2025" (4 digit)
     * @param string $noKartu
     * @param string $filter 1: tanggal entri, 2: tgl rencana kontrol
     * @return array
     */
    public function getListRencanaKontrolByNoKartu($bulan, $tahun, $noKartu, $filter)
    {
        try {
            $ts = $this->makeTimestampSecondsUTC();
            $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

            $endpoint = "/RencanaKontrol/ListRencanaKontrol/Bulan/{$bulan}/Tahun/{$tahun}/Nokartu/{$noKartu}/filter/{$filter}";
            
            $headers = [
                'X-cons-id' => $this->consId,
                'X-timestamp' => $ts,
                'X-signature' => $signature,
                'user_key' => $this->userKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ];

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->get($this->baseUrl . $endpoint);

            if (!$response->successful()) {
                throw new \Exception('HTTP request failed: ' . $response->status());
            }

            $data = $response->json();
            
            if (!$data) {
                throw new \Exception('Response kosong dari server BPJS');
            }

            // Jika response sudah plaintext JSON, langsung kembalikan
            if (isset($data['response']) && is_array($data['response'])) {
                return $data;
            }

            // Jika terenkripsi (string base64), lakukan decrypt+decompress
            if (isset($data['response']) && is_string($data['response'])) {
                $decryptedObj = $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'],
                    'response' => $decryptedObj,
                ];
            }

            // Beberapa implementasi meletakkan payload terenkripsi pada field 'data'
            if (isset($data['data']) && is_string($data['data'])) {
                $decryptedObj = $this->decryptAndDecompress($data['data'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'] ?? null,
                    'response' => $decryptedObj,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error saat mengambil list rencana kontrol: ' . $e->getMessage(), [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'no_kartu' => $noKartu,
                'filter' => $filter,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Insert Rencana Kontrol.
     *
     * @param array $rencanaKontrolData
     * @param array $options
     * @return array
     */
    public function insertRencanaKontrol($rencanaKontrolData, $options = [])
    {
        try {
            $ts = $this->makeTimestampSecondsUTC();
            $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

            $endpoint = '/RencanaKontrol/insert';
            $contentType = isset($options['contentTypeJson']) && $options['contentTypeJson'] 
                ? 'application/json' 
                : 'application/x-www-form-urlencoded';

            $headers = [
                'X-cons-id' => $this->consId,
                'X-timestamp' => $ts,
                'X-signature' => $signature,
                'user_key' => $this->userKey,
                'Content-Type' => $contentType,
                'Accept' => 'application/json',
            ];

            // Bungkus sesuai skema: { request: { ... } }
            $body = ['request' => $rencanaKontrolData];
            $bodyString = json_encode($body);

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->withBody($bodyString, $contentType)
                ->post($this->baseUrl . $endpoint);

            if (!$response->successful()) {
                throw new \Exception('HTTP request failed: ' . $response->status());
            }

            $data = $response->json();
            
            if (!$data) {
                throw new \Exception('Response kosong dari server BPJS');
            }

            if (isset($data['response']) && is_string($data['response'])) {
                $decryptedObj = $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'],
                    'response' => $decryptedObj,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error saat insert rencana kontrol: ' . $e->getMessage(), [
                'data' => $rencanaKontrolData,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat insert rencana kontrol',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update rencana kontrol.
     *
     * @param array $rencanaKontrolData
     * @param array $options
     * @return array
     */
    public function updateRencanaKontrol($rencanaKontrolData, $options = [])
    {
        try {
            $ts = $this->makeTimestampSecondsUTC();
            $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

            $endpoint = '/RencanaKontrol/Update';
            $contentType = isset($options['contentTypeJson']) && $options['contentTypeJson'] 
                ? 'application/json' 
                : 'application/x-www-form-urlencoded';

            $headers = [
                'X-cons-id' => $this->consId,
                'X-timestamp' => $ts,
                'X-signature' => $signature,
                'user_key' => $this->userKey,
                'Content-Type' => $contentType,
                'Accept' => 'application/json',
            ];

            // Bungkus sesuai skema: { request: { ... } }
            $body = ['request' => $rencanaKontrolData];
            $bodyString = json_encode($body);

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->withBody($bodyString, $contentType)
                ->put($this->baseUrl . $endpoint);

            if (!$response->successful()) {
                throw new \Exception('HTTP request failed: ' . $response->status());
            }

            $data = $response->json();
            
            if (!$data) {
                throw new \Exception('Response kosong dari server BPJS');
            }

            if (isset($data['response']) && is_string($data['response'])) {
                $decryptedObj = $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'],
                    'response' => $decryptedObj,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error saat mengupdate rencana kontrol: ' . $e->getMessage(), [
                'data' => $rencanaKontrolData,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate rencana kontrol',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Insert SEP versi 2.0.
     *
     * @param array $sepData
     * @param array $options
     * @return array
     */
    public function insertSEP20($sepData, $options = [])
    {
        try {
            $ts = $this->makeTimestampSecondsUTC();
            $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

            $endpoint = '/SEP/2.0/insert';
            $contentType = isset($options['contentTypeJson']) && $options['contentTypeJson'] 
                ? 'application/json' 
                : 'application/x-www-form-urlencoded';

            $headers = [
                'X-cons-id' => $this->consId,
                'X-timestamp' => $ts,
                'X-signature' => $signature,
                'user_key' => $this->userKey,
                'Content-Type' => $contentType,
                'Accept' => 'application/json',
            ];

            // Bungkus sesuai skema: { request: { ... } }
            $body = ['request' => $sepData];
            $bodyString = json_encode($body);

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->withBody($bodyString, $contentType)
                ->post($this->baseUrl . $endpoint);

            if (!$response->successful()) {
                throw new \Exception('HTTP request failed: ' . $response->status());
            }

            $data = $response->json();
            
            if (!$data) {
                throw new \Exception('Response kosong dari server BPJS');
            }

            if (isset($data['response']) && is_string($data['response'])) {
                $decryptedObj = $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'],
                    'response' => $decryptedObj,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error saat insert SEP 2.0: ' . $e->getMessage(), [
                'data' => $sepData,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat insert SEP 2.0',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get referensi dokter DPJP.
     *
     * @param string $jnsPelayanan
     * @param string $tglPelayanan
     * @param string $spesialisKode
     * @return array
     */
    public function getReferensiDokter($jnsPelayanan, $tglPelayanan, $spesialisKode)
    {
        try {
            $ts = $this->makeTimestampSecondsUTC();
            $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

            $endpoint = "/referensi/dokter/pelayanan/{$jnsPelayanan}/tglPelayanan/{$tglPelayanan}/Spesialis/{$spesialisKode}";

            $headers = [
                'X-cons-id' => $this->consId,
                'X-timestamp' => $ts,
                'X-signature' => $signature,
                'user_key' => $this->userKey,
                'Accept' => 'application/json',
            ];

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->get($this->baseUrl . $endpoint);

            if (!$response->successful()) {
                throw new \Exception('HTTP request failed: ' . $response->status());
            }

            $data = $response->json();
            
            if (!$data) {
                throw new \Exception('Response kosong dari server BPJS');
            }

            if (isset($data['response']) && is_string($data['response'])) {
                $decryptedObj = $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'],
                    'response' => $decryptedObj,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error saat mengambil referensi dokter: ' . $e->getMessage(), [
                'jnsPelayanan' => $jnsPelayanan,
                'tglPelayanan' => $tglPelayanan,
                'spesialisKode' => $spesialisKode,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil referensi dokter',
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
     * Get Detail by Nomor Surat Kontrol - Melihat data SEP untuk keperluan rencana kontrol berdasarkan nomor surat kontrol.
     *
     * @param string $noSuratKontrol Nomor Surat Kontrol Peserta
     * @return array
     */
    public function GetDetailByNoSuratKontrol($noSuratKontrol)
    {
        try {
            $ts = $this->makeTimestampSecondsUTC();
            $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

            $endpoint = "/RencanaKontrol/noSuratKontrol/{$noSuratKontrol}";
            
            $headers = [
                'X-cons-id' => $this->consId,
                'X-timestamp' => $ts,
                'X-signature' => $signature,
                'user_key' => $this->userKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ];

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->get($this->baseUrl . $endpoint);

            if (!$response->successful()) {
                throw new \Exception('HTTP request failed: ' . $response->status());
            }

            $data = $response->json();
            
            if (!$data) {
                throw new \Exception('Response kosong dari server BPJS');
            }

            // Jika response sudah plaintext JSON, langsung kembalikan
            if (isset($data['response']) && is_array($data['response'])) {
                return $data;
            }

            // Jika terenkripsi (string base64), lakukan decrypt+decompress
            if (isset($data['response']) && is_string($data['response'])) {
                $decryptedObj = $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'],
                    'response' => $decryptedObj,
                ];
            }

            // Beberapa implementasi meletakkan payload terenkripsi pada field 'data'
            if (isset($data['data']) && is_string($data['data'])) {
                $decryptedObj = $this->decryptAndDecompress($data['data'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'] ?? null,
                    'response' => $decryptedObj,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error saat mengambil data SEP by no surat kontrol: ' . $e->getMessage(), [
                'no_surat_kontrol' => $noSuratKontrol,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data SEP',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Jadwal Praktek Dokter untuk Rencana Kontrol.
     *
     * @param string $jnsKontrol Jenis kontrol (1: SPRI, 2: Rencana Kontrol)
     * @param string $kdPoli Kode poli
     * @param string $tglRencanaKontrol Tanggal rencana kontrol (format yyyy-MM-dd)
     * @return array
     */
    public function getJadwalPraktekDokter($jnsKontrol, $kdPoli, $tglRencanaKontrol)
    {
        try {
            $ts = $this->makeTimestampSecondsUTC();
            $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

            $endpoint = "/RencanaKontrol/JadwalPraktekDokter/JnsKontrol/{$jnsKontrol}/KdPoli/{$kdPoli}/TglRencanaKontrol/{$tglRencanaKontrol}";
            
            $headers = [
                'X-cons-id' => $this->consId,
                'X-timestamp' => $ts,
                'X-signature' => $signature,
                'user_key' => $this->userKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ];

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->get($this->baseUrl . $endpoint);

            if (!$response->successful()) {
                throw new \Exception('HTTP request failed: ' . $response->status());
            }

            $data = $response->json();
            
            if (!$data) {
                throw new \Exception('Response kosong dari server BPJS');
            }

            // Jika response sudah plaintext JSON, langsung kembalikan
            if (isset($data['response']) && is_array($data['response'])) {
                return $data;
            }

            // Jika terenkripsi (string base64), lakukan decrypt+decompress
            if (isset($data['response']) && is_string($data['response'])) {
                $decryptedObj = $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'],
                    'response' => $decryptedObj,
                ];
            }

            // Beberapa implementasi meletakkan payload terenkripsi pada field 'data'
            if (isset($data['data']) && is_string($data['data'])) {
                $decryptedObj = $this->decryptAndDecompress($data['data'], $this->consId, $this->secretKey, $ts);
                return [
                    'metaData' => $data['metaData'] ?? null,
                    'response' => $decryptedObj,
                ];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error saat mengambil jadwal praktek dokter: ' . $e->getMessage(), [
                'jns_kontrol' => $jnsKontrol,
                'kd_poli' => $kdPoli,
                'tgl_rencana_kontrol' => $tglRencanaKontrol,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil jadwal praktek dokter',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make HTTP request to BPJS API with encryption/decryption support.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param array $options
     * @return array
     */
    private function makeRequest($method, $endpoint, $data = [], $options = [])
    {
        $maxRetries = 3;
        $retryDelay = 1; // seconds
        $useEncryption = $options['useEncryption'] ?? false;
        $contentType = $options['contentType'] ?? 'application/json';

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $ts = $this->makeTimestampSecondsUTC();
                $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);
                
                $headers = [
                    'X-cons-id' => $this->consId,
                    'X-timestamp' => $ts,
                    'X-signature' => $signature,
                    'user_key' => $this->userKey,
                    'Content-Type' => $contentType,
                    'Accept' => 'application/json',
                ];
                
                $url = $this->baseUrl . $endpoint;
                $body = $data;
                
                // Wrap data if encryption is used
                if ($useEncryption && !empty($data)) {
                    $body = ['request' => $data];
                }
                
                $bodyString = json_encode($body);
                
                $httpClient = Http::withHeaders($headers)->timeout($this->timeout);
                
                switch (strtoupper($method)) {
                    case 'GET':
                        $response = $httpClient->get($url);
                        break;
                    case 'POST':
                        $response = $httpClient->withBody($bodyString, $contentType)->post($url);
                        break;
                    case 'PUT':
                        $response = $httpClient->withBody($bodyString, $contentType)->put($url);
                        break;
                    case 'DELETE':
                        $response = $httpClient->delete($url);
                        break;
                    default:
                        throw new \Exception('Unsupported HTTP method: ' . $method);
                }

                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    // Handle encrypted response
                    if ($useEncryption && isset($responseData['response']) && is_string($responseData['response'])) {
                        $decryptedObj = $this->decryptAndDecompress($responseData['response'], $this->consId, $this->secretKey, $ts);
                        return [
                        'success' => true,
                        'data' => [
                            'metaData' => $responseData['metaData'] ?? null,
                            'response' => $decryptedObj,
                        ],
                        'status_code' => $response->status(),
                    ];
                }

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

                // Handle specific HTTP status codes
                if ($response->status() === 429) {
                    // Rate limited, wait longer before retry
                    sleep($retryDelay * 2);
                    continue;
                }

                if ($response->status() >= 500) {
                    // Server error, retry
                    sleep($retryDelay);
                    continue;
                }

                // Client error, don't retry
                return [
                    'success' => false,
                    'message' => 'HTTP Error: ' . $response->status(),
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ];
            } catch (\Exception $e) {
                Log::error('HTTP request failed (attempt ' . $attempt . '): ' . $e->getMessage(), [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'data' => $data,
                ]);

                if ($attempt === $maxRetries) {
                    return [
                        'success' => false,
                        'message' => 'Request failed after ' . $maxRetries . ' attempts: ' . $e->getMessage(),
                        'error' => $e->getMessage(),
                    ];
                }

                sleep($retryDelay);
            }

            $retryDelay *= 2; // Exponential backoff
        }

        return [
            'success' => false,
            'message' => 'Request failed after maximum retries',
        ];
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