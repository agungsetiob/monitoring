<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SimgosService
{
    protected string $baseUrl = 'http://192.168.0.10/webservice';

    /**
     * Login ke SIMGOS dan simpan cookies ke cache
     */
    protected function login(): array
    {
        $cookiesArray = Cache::get('simgos_cookies');
        if ($cookiesArray) {
            return $cookiesArray;
        }

        $credentials = [
            'LOGIN' => env('SIMGOS_API_USER'),
            'PASSWORD' => env('SIMGOS_API_PASS'),
        ];

        $loginResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$this->baseUrl}/authentication/login", $credentials);

        if (!$loginResponse->successful()) {
            throw new \Exception("Gagal login ke webservice: " . $loginResponse->body(), $loginResponse->status());
        }

        // Ambil cookies
        $cookieJar = $loginResponse->cookies();
        $cookiesArray = [];
        foreach ($cookieJar->toArray() as $cookie) {
            $cookiesArray[$cookie['Name']] = $cookie['Value'];
        }

        // Simpan ke cache (30 menit)
        Cache::put('simgos_cookies', $cookiesArray, now()->addMinutes(30));

        return $cookiesArray;
    }

    /**
     * Request wrapper dengan auto-login & auto-relogin
     */
    protected function request(string $endpoint, array $params = [])
    {
        $cookies = $this->login();

        $response = Http::withCookies($cookies, parse_url($this->baseUrl, PHP_URL_HOST))
            ->acceptJson()
            ->get("{$this->baseUrl}/$endpoint", $params);

        // Jika session expired → login ulang
        if ($response->status() === 401) {
            Cache::forget('simgos_cookies');
            $cookies = $this->login();
            $response = Http::withCookies($cookies, parse_url($this->baseUrl, PHP_URL_HOST))
                ->acceptJson()
                ->get("{$this->baseUrl}/$endpoint", $params);
        }

        return $response;
    }

    /**
     * Ambil daftar resep
     */
    public function getResep(array $filters = [])
    {
        $defaultParams = [
            'PAWAL' => now()->startOfMonth()->toDateString(),
            'PAKHIR' => now()->endOfMonth()->toDateString(),
            'page'   => 1,
            'start'  => 0,
            'limit'  => 10,
        ];

        $response = $this->request('apotekonline/resep', array_merge($defaultParams, $filters));

        if (!$response->successful()) {
            return [
                'error' => 'Gagal mengambil resep',
                'status' => $response->status(),
                'body' => $response->body(),
            ];
        }

        return $response->json();
    }
}
