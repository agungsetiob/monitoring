<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Response;

class TesApolApiService
{
    private $baseUrl;
    private $consId;
    private $secretKey;
    private $userKey;
    private $timeout;

    public function __construct()
    {
        // Konfigurasi API dari config/services.php
        $this->baseUrl = config('services.apol.base_url');
        $this->consId = config('services.apol.cons_id');
        $this->secretKey = config('services.apol.secret_key');
        $this->userKey = config('services.apol.user_key');
        $this->timeout = config('services.apol.timeout', 30);
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
     */
    private function decryptAndDecompress($cipherBase64, $consId, $secret, $timestamp)
    {
        try {
            $keyMaterial = $consId . $secret . $timestamp;
            $decrypted = $this->stringDecrypt($keyMaterial, $cipherBase64);

            if ($decrypted === false) {
                throw new \Exception('Gagal decrypt data');
            }

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

    private function stringDecrypt($key, $string)
    {
        $encrypt_method = 'AES-256-CBC';
        $key_hash = hex2bin(hash('sha256', $key));
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
        return $output;
    }

    private function decompress($string)
    {
        return \LZCompressor\LZString::decompressFromEncodedURIComponent($string);
    }

    /**
     * Format tanggal sesuai requirement BPJS APOL
     */
    private function formatTanggal($tanggal)
    {
        try {
            // Convert dari format frontend ke format BPJS
            if (strpos($tanggal, 'T') !== false) {
                // Format: 2025-01-01T00:00 -> 2025-01-01 00:00:00
                $tanggal = str_replace('T', ' ', $tanggal);
                if (strlen($tanggal) == 16) { // 2025-01-01 00:00
                    $tanggal .= ':00';
                }
            }

            // Pastikan format datetime lengkap
            $date = \Carbon\Carbon::parse($tanggal);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning('Error formatting date: ' . $e->getMessage() . ' for input: ' . $tanggal);
            return $tanggal; // Return original if parsing fails
        }
    }

    /**
     * Daftar Resep - BPJS APOL API
     * Mencoba berbagai format request sesuai dokumentasi BPJS
     */
    public function getDaftarResep($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir)
    {
        $attempts = [
            'raw_json_body' => function () use ($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir) {
                return $this->attemptAlternativeDateFormat($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir);
            },
            'documentation_format' => function () use ($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir) {
                return $this->attemptDocumentationFormat($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir);
            },
        ];

        $lastError = null;

        foreach ($attempts as $method => $callback) {
            try {
                Log::info("Trying method: {$method}");
                $result = $callback();

                if ($this->isSuccessfulResponse($result)) {
                    Log::info("SUCCESS with method: {$method}");
                    return $result;
                }

                // Log unsuccessful attempt but continue
                Log::info("Method {$method} returned: " . json_encode([
                    'status' => $result['status'] ?? 'unknown',
                    'is_html' => strpos(json_encode($result), '<html') !== false,
                    'has_metadata' => isset($result['metaData'])
                ]));

            } catch (\Exception $e) {
                Log::warning("Method {$method} failed: " . $e->getMessage());
                $lastError = $e;
                continue;
            }
        }

        // All methods failed, return error
        Log::error('All request methods failed', [
            'last_error' => $lastError ? $lastError->getMessage() : 'Unknown error',
            'parameters' => compact('kdppk', 'kdJnsObat', 'jnsTgl', 'tglMulai', 'tglAkhir')
        ]);

        return [
            'success' => false,
            'message' => 'Data Tidak Ditemukan',
            'error' => $lastError ? $lastError->getMessage() : 'Unknown error',
            'metaData' => [
                'code' => '500',
                'message' => 'Data Tidak Ditemukan'
            ]
        ];
    }

    /**
     * Method 4: Raw JSON string dalam form data (seperti RencanaKontrol)
     */
    private function attemptAlternativeDateFormat($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir)
    {
        $ts = $this->makeTimestampSecondsUTC();
        $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

        $headers = [
            'X-cons-id' => $this->consId,
            'X-timestamp' => $ts,
            'X-signature' => $signature,
            'user_key' => $this->userKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ];

        $requestBody = json_encode([
            'kdppk' => $kdppk,
            'KdJnsObat' => $kdJnsObat,
            'JnsTgl' => $jnsTgl,
            'TglMulai' => $this->formatTanggal($tglMulai),
            'TglAkhir' => $this->formatTanggal($tglAkhir),
        ]);

        Log::info('APOL Request (Raw JSON Body)', [
            'body' => $requestBody
        ]);

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->withBody($requestBody, 'application/x-www-form-urlencoded')
            ->post($this->baseUrl . '/daftarresep');

        return $this->processResponse($response, $ts);
    }

    /**
     * Method 5: Format seperti dokumentasi example
     */
    public function attemptDocumentationFormat($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir)
    {
        $ts = $this->makeTimestampSecondsUTC();
        $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

        $headers = [
            'X-cons-id' => $this->consId,
            'X-timestamp' => $ts,
            'X-signature' => $signature,
            'user_key' => $this->userKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ];

        // Persis seperti contoh di dokumentasi
        $requestData = json_encode([
            "kdppk" => $kdppk,
            "KdJnsObat" => $kdJnsObat,
            "JnsTgl" => $jnsTgl,
            "TglMulai" => $this->formatTanggal($tglMulai),
            "TglAkhir" => $this->formatTanggal($tglAkhir)
        ]);

        Log::info('APOL Request (Documentation Format)', [
            'raw_body' => $requestData
        ]);

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->withBody($requestData, 'application/x-www-form-urlencoded')
            ->post($this->baseUrl . '/daftarresep');

        return $this->processResponse($response, $ts);
    }

    /**
     * Process HTTP response
     */
    private function processResponse($response, $timestamp)
    {
        Log::info('APOL Response', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body_preview' => substr($response->body(), 0, 500) . '...'
        ]);

        // Kalau status sukses 200
        if ($response->successful()) {
            $data = $response->json();

            if (!$data) {
                return [
                    'success' => false,
                    'metaData' => ['code' => '204', 'message' => 'Data tidak ada / kosong'],
                    'response' => []
                ];
            }

            // Handle encrypted response
            if (isset($data['response']) && is_string($data['response'])) {
                try {
                    $decryptedObj = $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $timestamp);

                    if (empty($decryptedObj)) {
                        return [
                            'success' => false,
                            'metaData' => ['code' => '204', 'message' => 'Data tidak ada / kosong'],
                            'response' => []
                        ];
                    }

                    return [
                        'metaData' => $data['metaData'] ?? ['code' => 200, 'message' => 'OK'],
                        'response' => $decryptedObj,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Failed to decrypt response: ' . $e->getMessage());
                }
            }

            // Kalau ada response tapi kosong/null
            if (isset($data['response']) && empty($data['response'])) {
                return [
                    'success' => false,
                    'metaData' => ['code' => '204', 'message' => 'Data tidak ada / kosong'],
                    'response' => []
                ];
            }

            return $data;
        }

        // Kalau status bukan 200
        $errorMessage = 'HTTP request failed: ' . $response->status();
        $body = $response->body();

        if ($this->looksLikeHtmlError($response)) {
            $errorMessage .= ' - Server returned HTML error page';
        } else {
            $errorMessage .= ' - Response: ' . $body;
        }

        return [
            'success' => false,
            'metaData' => ['code' => (string) $response->status(), 'message' => $errorMessage],
            'response' => []
        ];
    }


    /**
     * Check if response is successful
     */
    private function isSuccessfulResponse($response)
    {
        if (!is_array($response)) {
            return false;
        }

        // Check metaData code
        if (isset($response['metaData']['code'])) {
            $code = $response['metaData']['code'];
            return $code === '200' || $code === 200;
        }

        // Check success flag
        if (isset($response['success'])) {
            return $response['success'] === true;
        }

        // If we have response data and no error indicators, assume success
        if (isset($response['response']) || isset($response['data'])) {
            return true;
        }

        return false;
    }

    /**
     * Test method untuk debugging endpoint dengan proper authentication
     */
    public function testEndpoint()
    {
        try {
            $ts = $this->makeTimestampSecondsUTC();
            $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

            $headers = [
                'X-cons-id' => $this->consId,
                'X-timestamp' => $ts,
                'X-signature' => $signature,
                'user_key' => $this->userKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            Log::info('Testing APOL Endpoint with Authentication', [
                'base_url' => $this->baseUrl,
                'full_endpoint' => $this->baseUrl . '/daftarresep',
                'headers' => array_merge($headers, [
                    'X-signature' => substr($signature, 0, 20) . '...',
                    'user_key' => substr($this->userKey, 0, 10) . '...',
                ])
            ]);

            // Test dengan proper authentication headers
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($this->baseUrl . '/daftarresep');

            return [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_preview' => substr($response->body(), 0, 500),
                'is_html' => strpos($response->body(), '<html') !== false,
                'authentication_test' => 'with_headers'
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test authentication dengan sample data
     */
    public function testAuthentication()
    {
        try {
            $ts = $this->makeTimestampSecondsUTC();
            $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

            $headers = [
                'X-cons-id' => $this->consId,
                'X-timestamp' => $ts,
                'X-signature' => $signature,
                'user_key' => $this->userKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            // Test dengan sample request data
            $sampleData = [
                'kdppk' => '0112A017',
                'KdJnsObat' => '0',
                'JnsTgl' => 'TGLPELSJP',
                'TglMulai' => '2025-08-01 00:00:00',
                'TglAkhir' => '2025-08-31 23:59:59',
            ];

            Log::info('Testing Authentication with Sample Data', [
                'timestamp' => $ts,
                'cons_id' => $this->consId,
                'signature_preview' => substr($signature, 0, 20) . '...',
                'user_key_preview' => substr($this->userKey, 0, 10) . '...',
                'sample_data' => $sampleData
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->post($this->baseUrl . '/daftarresep', $sampleData);

            return [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'is_html' => strpos($response->body(), '<html') !== false,
                'test_type' => 'authentication_with_sample_data',
                'request_headers' => array_merge($headers, ['X-signature' => '***masked***']),
                'request_data' => $sampleData
            ];
        } catch (\Exception $e) {
            Log::error('Authentication test error: ' . $e->getMessage());
            return [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
    public function hapusResep(string $nosjp, string $refasalsjp, string $noresep)
    {
        $attempts = [
            'delete_raw_json_as_urlencoded' => function () use ($nosjp, $refasalsjp, $noresep) {
                return $this->attemptHapusResepRawJsonAsUrlencoded($nosjp, $refasalsjp, $noresep);
            },
        ];

        $lastError = null;

        foreach ($attempts as $name => $cb) {
            try {
                Log::info("HapusResep: mencoba metode {$name}");
                $result = $cb();

                if ($this->isSuccessfulResponse($result)) {
                    Log::info("HapusResep: SUKSES dengan metode {$name}");
                    return $result;
                }

                Log::info("HapusResep: metode {$name} tidak sukses", [
                    'has_meta' => isset($result['metaData']),
                    'meta_code' => $result['metaData']['code'] ?? null,
                ]);

            } catch (\Exception $e) {
                $lastError = $e;
                Log::warning("HapusResep: metode {$name} gagal: " . $e->getMessage());
            }
        }

        Log::error('HapusResep: semua metode gagal', [
            'last_error' => $lastError?->getMessage(),
            'params' => compact('nosjp', 'refasalsjp', 'noresep')
        ]);

        return [
            'success' => false,
            'message' => 'Semua metode request hapusresep gagal',
            'error' => $lastError?->getMessage() ?? 'Unknown error',
            'metaData' => ['code' => '500', 'message' => 'Failed delete request'],
        ];
    }

    private function attemptHapusResepRawJsonAsUrlencoded(string $nosjp, string $refasalsjp, string $noresep)
    {
        $ts = $this->makeTimestampSecondsUTC();
        $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

        $headers = [
            'X-cons-id' => $this->consId,
            'X-timestamp' => $ts,
            'X-signature' => $signature,
            'user_key' => $this->userKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $rawJson = json_encode([
            'nosjp' => $nosjp,
            'refasalsjp' => $refasalsjp,
            'noresep' => $noresep,
        ]);

        Log::info('APOL HapusResep (DELETE raw JSON body as urlencoded)', ['raw' => $rawJson]);

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->withBody($rawJson, 'application/x-www-form-urlencoded')
            ->delete($this->baseUrl . '/hapusresep');

        return $this->processResponse($response, $ts);
    }
    public function getDaftarPelayananObat(string $noSep)
    {
        $ts = $this->makeTimestampSecondsUTC();
        $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);

        $headers = [
            'X-cons-id' => $this->consId,
            'X-timestamp' => $ts,
            'X-signature' => $signature,
            'user_key' => $this->userKey,
            'Accept' => 'application/json',
        ];

        $url = rtrim($this->baseUrl, '/') . "/pelayanan/obat/daftar/" . rawurlencode($noSep);
        \Log::info('APOL getDaftarPelayananObat', ['url' => $url]);

        $resp = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->get($url);

        if ($resp->status() === 404) {
            throw new \Exception('HTTP request failed: 404 - Response: No Mapping Rule matched');
        }

        $data = $this->processResponse($resp, $ts);
        $respArr = $data['response'] ?? $data;

        $rootList = $respArr['listobat'] ?? [];
        $detailList = $respArr['detailsep']['listobat'] ?? [];

        $candidate = [];

        foreach ([$detailList, $rootList] as $lst) {
            if (empty($lst))
                continue;

            // Normalisasi jika server kirim object tunggal
            if (
                is_array($lst) && (
                    isset($lst['kodeobat']) || isset($lst['kdobat']) ||
                    isset($lst['KdObat']) || isset($lst['KDOBAT']) ||
                    isset($lst['kd_obat'])
                )
            ) {
                $lst = [$lst];
            }

            if (!is_array($lst))
                continue;

            foreach ($lst as $it) {
                if (!is_array($it))
                    continue;

                $kode = $it['kodeobat'] ?? $it['kdobat'] ?? $it['KdObat'] ?? $it['KDOBAT'] ?? $it['kd_obat'] ?? null;
                $tipe = $it['tipeobat'] ?? $it['tipeObat'] ?? $it['tipe_obat'] ?? 'N';
                $harga = $it['harga'];

                $it['kodeobat'] = (string) $kode;
                $it['tipeobat'] = strtoupper((string) $tipe);
                $it['harga'] = (string) $harga;

                $candidate[] = $it;
            }
        }

        // Unik-kan berdasarkan kodeobat
        $candidate = array_values(array_reduce($candidate, function ($acc, $it) {
            $acc[(string) ($it['kodeobat'] ?? '')] = $it;
            return $acc;
        }, []));

        // Sinkronisasi ke dua lokasi
        $respArr['listobat'] = $candidate;
        $respArr['detailsep'] = $respArr['detailsep'] ?? [];
        $respArr['detailsep']['listobat'] = $candidate;

        return [
            'metaData' => $data['metaData'] ?? null,
            'response' => $respArr,
        ];
    }

    private function looksLikeHtmlError(Response $resp): bool
    {
        $ctHeader = $resp->header('Content-Type');              // bisa string/null
        $ct = is_array($ctHeader) ? implode(',', $ctHeader) : (string) $ctHeader;
        $ct = strtolower($ct);

        if ($ct !== '' && str_contains($ct, 'text/html')) {
            return true;
        }

        $body = (string) $resp->body();
        if ($body === '')
            return false;

        $sample = substr($body, 0, 1024); // cukup cek sebagian awal
        return stripos($sample, '<html') !== false
            || stripos($sample, '<!doctype html') !== false
            || stripos($sample, 'request error') !== false
            || stripos($sample, '<title>service</title>') !== false;
    }

    public function hapusObat(string $noSepApotek, string $noResep, string $kodeObat, string $tipeObat = 'N') 
    {
        $ts = $this->makeTimestampSecondsUTC();
        $signature = $this->makeSignature($this->consId, $ts, $this->secretKey);
        $base = rtrim($this->baseUrl, '/');

        $payload = [
            'nosepapotek' => trim($noSepApotek),
            'noresep' => trim($noResep),
            'kodeobat' => trim($kodeObat),
            'tipeobat' => strtoupper($tipeObat ?: 'N'),
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'X-cons-id' => $this->consId,
            'X-timestamp' => $ts,
            'X-signature' => $signature,
            'user_key' => $this->userKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        // Path yang biasanya dipakai, sesuaikan kalau ada variasi IGW
        $paths = [
            '/obat/hapus',
            '/pelayanan/obat/hapus',
        ];

        foreach ($paths as $path) {
            $url = $base . $path;
            $client = \Http::withHeaders($headers)->timeout($this->timeout);

            try {
                // format: Content-Type: x-www-form-urlencoded, body: <json>
                $resp = $client->withBody($json, 'application/x-www-form-urlencoded')->delete($url);

                \Log::info('APOL hapusObat', [
                    'url' => $url,
                    'status' => $resp->status(),
                    'ct' => $resp->header('Content-Type'),
                    'body_preview' => substr((string) $resp->body(), 0, 300) . '...',
                ]);

                $ct = strtolower((string) $resp->header('Content-Type'));
                $looksHtml = str_contains($ct, 'text/html') ||
                    stripos((string) $resp->body(), '<html') !== false;

                // Jika balasan HTML atau 400/404/405 → coba path berikutnya
                if ($looksHtml || in_array($resp->status(), [400, 404, 405])) {
                    continue;
                }

                if ($resp->successful()) {
                    return $this->processResponse($resp, $ts);
                }

                if ($parsed = $this->tryParseErrorBody($resp, $ts)) {
                    return $parsed;
                }

            } catch (\Throwable $e) {
                \Log::warning('APOL hapusObat exception: ' . $e->getMessage(), [
                    'url' => $url,
                ]);
                continue;
            }
        }

        \Log::error('APOL hapusObat: semua attempt gagal', ['payload' => $payload]);

        return [
            'success' => false,
            'metaData' => [
                'code' => '404',
                'message' => 'No Mapping Rule matched untuk hapusObat',
            ],
            'response' => [
                'hint' => 'Cek mapping IGW: DELETE + Content-Type: x-www-form-urlencoded + raw JSON body',
            ],
        ];
    }

    // helper opsional biar metaData.message 4xx tetap kebaca
    private function tryParseErrorBodyFromThrowable(\Throwable $e, $ts)
    {
        if (method_exists($e, 'response') && $e->response()) {
            try {
                $resp = $e->response();
                $data = $resp->json();
                if (isset($data['response']) && is_string($data['response'])) {
                    return [
                        'metaData' => $data['metaData'] ?? null,
                        'response' => $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $ts),
                    ];
                }
                return $data;
            } catch (\Throwable $x) {
            }
        }
        return null;
    }


    // helper opsional: coba baca body JSON walau status 400 supaya kelihatan "metaData.message"
    private function tryParseErrorBody(Response $resp, $ts)
    {
        try {
            $data = $resp->json();
            if (is_array($data)) {
                // kalau ada ciphertext 'response', tetap coba decrypt biar tahu alasan gagal
                if (isset($data['response']) && is_string($data['response'])) {
                    $decoded = $this->decryptAndDecompress($data['response'], $this->consId, $this->secretKey, $ts);
                    return ['metaData' => $data['metaData'] ?? null, 'response' => $decoded];
                }
                return $data;
            }
        } catch (\Throwable $e) {
            // ignore, lanjut ke attempt berikutnya
        }
        return null;
    }
}