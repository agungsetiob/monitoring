<?php

namespace App\Services;

use App\Models\LogKirimResep;
use App\Models\LogKirimResepDetil;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Repositories\LogKirimResepRepository;
use App\Repositories\LogKirimResepDetilRepository;

class ApolApiService
{
    private $baseUrl;
    private $consId;
    private $secretKey;
    private $userKey;
    private $timeout;
    protected LogKirimResepRepository $logRepo;
    protected LogKirimResepDetilRepository $logKirimResepDetil;

    public function __construct()
    {
        $this->baseUrl = config('services.apol.base_url');
        $this->consId = config('services.apol.cons_id');
        $this->secretKey = config('services.apol.secret_key');
        $this->userKey = config('services.apol.user_key');
        $this->timeout = config('services.apol.timeout', 30);
        $this->logRepo = app(LogKirimResepRepository::class);
        $this->logKirimResepDetil = app(LogKirimResepDetilRepository::class);
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
            if (strpos($tanggal, 'T') !== false) {
                $tanggal = str_replace('T', ' ', $tanggal);
                if (strlen($tanggal) == 16) { // 2025-01-01 00:00
                    $tanggal .= ':00';
                }
            }

            $date = \Carbon\Carbon::parse($tanggal);
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning('Error formatting date: ' . $e->getMessage() . ' for input: ' . $tanggal);
            return $tanggal;
        }
    }

    /**
     * Daftar Resep - BPJS APOL API
     */
    public function getDaftarResep($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir)
    {
        try {
            $result = $this->requestDaftarResep($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir);

            if ($this->isValidResponse($result)) {
                return $result;
            }

            Log::info("Response tidak valid atau kosong", [
                'status' => $result['status'] ?? 'unknown',
                'metaData' => $result['metaData'] ?? null,
                'response' => $result['response'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::warning("Request daftar resep gagal: " . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Tidak ada data resep pada rentang tanggal tersebut',
            'response' => [],
            'metaData' => [
                'code' => '204',
                'message' => 'No Content'
            ]
        ];
    }


    private function isValidResponse($result)
    {
        return isset($result['metaData']) &&
            ($result['metaData']['code'] == '200' || $result['metaData']['code'] == '204');
    }


    /**
     * Raw JSON string dalam form data
     */
    private function requestDaftarResep($kdppk, $kdJnsObat, $jnsTgl, $tglMulai, $tglAkhir)
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

        Log::info('APOL Request (Daftar Resep)', [
            'body' => $requestBody
        ]);

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->withBody($requestBody, 'application/x-www-form-urlencoded')
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

    protected function markResepAsBatal(string $nosjp): void
    {
        try {
            $updated = LogKirimResep::where('NOSJP', $nosjp)
                ->update([
                    'STATUS' => 9,
                    'RESPONSE' => 'batal',
                ]);

            \Log::info("Update log_kirim_resep batal: NOSJP=$nosjp, updated=$updated");

        } catch (\Throwable $e) {
            \Log::error("Exception saat update log_kirim_resep batal: " . $e->getMessage(), [
                'nosjp' => $nosjp,
            ]);
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
                    $this->markResepAsBatal($nosjp);
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

    protected function markObatAsBatal(string $noSepApotek, string $kodeObat): void
    {
        try {
            $resep = LogKirimResep::where('NOSJP', $noSepApotek)->first();

            if (!$resep || !$resep->KUNJUNGAN) {
                \Log::warning("Gagal update resep_detil: KUNJUNGAN tidak ditemukan untuk NOSJP $noSepApotek");
                return;
            }

            $updated = LogKirimResepDetil::where('KUNJUNGAN', $resep->KUNJUNGAN)
                ->where('KDOBT', $kodeObat)
                ->update([
                    'STATUS' => 9,
                    'RESPONSE' => 'batal',
                ]);

            \Log::info("Update resep_detil batal: KUNJUNGAN={$resep->KUNJUNGAN}, KDBAT=$kodeObat, updated=$updated");

        } catch (\Throwable $e) {
            \Log::error("Exception saat update resep_detil batal: " . $e->getMessage(), [
                'nosjp' => $noSepApotek,
                'kodeobat' => $kodeObat,
            ]);
        }
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
            //'/obat/hapus',
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
                    $result = $this->processResponse($resp, $ts);
                    $this->markObatAsBatal($noSepApotek, $kodeObat);
                    return $result;
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

    /**
     * Simpan resep baru ke BPJS APOL
     */
    public function simpanResep(array $data)
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

        $payload = [
            'TGLSJP' => $this->formatTanggal($data['TGLSJP'] ?? now()),
            'REFASALSJP' => $data['REFASALSJP'] ?? '',
            'POLIRSP' => $data['POLIRSP'] ?? '',
            'KDJNSOBAT' => $data['KDJNSOBAT'] ?? '1',
            'NORESEP' => $data['NORESEP'] ?? '',
            'IDUSERSJP' => $data['IDUSERSJP'] ?? '',
            'TGLRSP' => $this->formatTanggal($data['TGLRSP'] ?? now()),
            'TGLPELRSP' => $this->formatTanggal($data['TGLPELRSP'] ?? now()),
            'KdDokter' => $data['KdDokter'] ?? '0',
            'iterasi' => $data['iterasi'] ?? '0'
        ];

        Log::info('APOL Simpan Resep Request', ['payload' => $payload]);

        $requestBody = json_encode($payload);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->withBody($requestBody, 'application/x-www-form-urlencoded')
                ->post($this->baseUrl . '/sjpresep/v3/insert');

            $result = $this->processResponse($response, $ts);

            $meta = $result['metaData'] ?? [];
            $bpjsResponse = $result['response'] ?? [];

            $logData = array_merge($payload, [
                'KUNJUNGAN' => $data['KUNJUNGAN'] ?? null,
                'NOSJP' => $bpjsResponse['noApotik'] ?? null,
                'STATUS' => ($meta['code'] ?? null) == '200' ? 1 : 9,
                'RESPONSE' => $meta['message'] ?? 'Unknown',
            ]);

            $this->logRepo->upsert($logData);

            // Jika sukses, kirim detail obat
            if (($meta['code'] ?? null) == '200' && isset($data['DETAIL']) && is_array($data['DETAIL'])) {
                foreach ($data['DETAIL'] as $item) {
                    $detailPayload = [
                        'NOSJP' => $bpjsResponse['noApotik'] ?? '',
                        'NORESEP' => $payload['NORESEP'],
                        'KDOBT' => $item['REFERENSI']['DPHO']['kodeobat'] ?? '',
                        'NMOBAT' => $item['REFERENSI']['DPHO']['namaobat'] ?? '',
                        'SIGNA1OBT' => $item['SIGNA1'] ?? $item['REFERENSI']['FREKUENSIATURAN']['SIGNA1'] ?? 1,
                        'SIGNA2OBT' => $item['SIGNA2'] ?? $item['REFERENSI']['FREKUENSIATURAN']['SIGNA2'] ?? 1,
                        'JMLOBT' => $item['JUMLAH'] ?? 1,
                        'JHO' => 1,
                        'CatKhsObt' => $item['RACIKAN'] == 1 ? 'Racikan' : 'Non Racikan',
                        'KUNJUNGAN' => $data['KUNJUNGAN'] ?? null,
                        'REF_FARMASI' => $item['ID'] ?? null,
                    ];

                    if ($item['RACIKAN'] == 1) {
                        $detailPayload['JNSROBT'] = $item['REFERENSI']['JNSROBT'] ?? 'R.01';
                        $detailPayload['PERMINTAAN'] = $item['PERMINTAAN'] ?? 1;

                        $res = $this->insertResepRacik($detailPayload);
                    } else {
                        $res = $this->insertResepNonRacik($detailPayload);
                    }

                    // Simpan log detail
                    $detailPayload['RESPONSE'] = $res['message'] ?? 'Unknown';
                    $detailPayload['STATUS'] = $res['success'] ? 1 : 0;
                    $this->logKirimResepDetil->simpan($detailPayload);
                }
            }

            return $result;
        } catch (\Exception $e) {
            $logData = $payload;
            $logData['STATUS'] = 9;
            $logData['RESPONSE'] = $e->getMessage();
            $logData['NOSJP'] = null;

            $this->logRepo->upsert($logData);

            return [
                'success' => false,
                'message' => 'Gagal menyimpan resep: ' . $e->getMessage(),
                'metaData' => ['code' => '500', 'message' => $e->getMessage()]
            ];
        }
    }

    public function insertResepRacik(array $data): array
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

        $requestBody = json_encode($data);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->withBody($requestBody, 'application/x-www-form-urlencoded')
                ->post($this->baseUrl . '/obatracikan/v3/insert');

            $result = $this->processResponse($response, $ts);
            $meta = $result['metaData'] ?? [];

            return [
                'success' => ($meta['code'] ?? null) == '200',
                'code' => $meta['code'] ?? '500',
                'message' => $meta['message'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            Log::error('Gagal insert resep racikan', ['error' => $e->getMessage(), 'data' => $data]);

            return [
                'success' => false,
                'code' => '500',
                'message' => 'HTTP request failed: ' . $e->getMessage()
            ];
        }
    }


    public function insertResepNonRacik(array $data): array
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

        $requestBody = json_encode($data);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->withBody($requestBody, 'application/x-www-form-urlencoded')
                ->post($this->baseUrl . '/obatnonracikan/v3/insert');

            $result = $this->processResponse($response, $ts);
            $meta = $result['metaData'] ?? [];

            return [
                'success' => ($meta['code'] ?? null) == '200',
                'code' => $meta['code'] ?? '500',
                'message' => $meta['message'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            Log::error('Gagal insert resep non-racikan', ['error' => $e->getMessage(), 'data' => $data]);

            return [
                'success' => false,
                'code' => '500',
                'message' => 'HTTP request failed: ' . $e->getMessage()
            ];
        }
    }
}