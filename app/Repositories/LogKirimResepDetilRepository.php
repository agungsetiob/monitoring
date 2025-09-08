<?php
namespace App\Repositories;

use App\Models\LogKirimResepDetil;
use Illuminate\Support\Facades\Log;

class LogKirimResepDetilRepository
{
    public function simpan(array $data): LogKirimResepDetil
    {
        $kunjungan = $data['KUNJUNGAN'] ?? '0';
        $refFarmasi = $data['REF_FARMASI'] ?? null;

        $record = LogKirimResepDetil::updateOrCreate(
            [
                'KUNJUNGAN' => $kunjungan,
                'REF_FARMASI' => $refFarmasi,
            ],
            [
                'JNSROBT' => $data['JNSROBT'] ?? null,
                'KDOBT' => $data['KDOBT'] ?? null,
                'SIGNA1OBT' => $data['SIGNA1OBT'] ?? 1,
                'SIGNA2OBT' => $data['SIGNA2OBT'] ?? 1,
                'PERMINTAAN' => $data['PERMINTAAN'] ?? null,
                'JMLOBT' => $data['JMLOBT'] ?? 1,
                'JHO' => $data['JHO'] ?? 1,
                'CatKhsObt' => $data['CatKhsObt'] ?? 'Non Racikan',
                'RESPONSE' => $data['RESPONSE'] ?? 'Unknown',
                'STATUS' => $data['STATUS'] ?? 0,
            ]
        );

        Log::info('LogResepDetil ' . ($record->wasRecentlyCreated ? 'inserted' : 'updated'), [
            'kunjungan' => $record->KUNJUNGAN,
            'ref_farmasi' => $record->REF_FARMASI,
            'status' => $record->STATUS,
            'response' => $record->RESPONSE
        ]);

        return $record;
    }
}
