<?php
namespace App\Repositories;

use App\Models\LogKirimResepDetil;
use Illuminate\Support\Facades\Log;

class LogKirimResepDetilRepository
{
    public function simpan(array $data): LogKirimResepDetil
    {
        $record = new LogKirimResepDetil();

        $record->KUNJUNGAN   = $data['KUNJUNGAN'] ?? '0';
        $record->REF_FARMASI = $data['REF_FARMASI'] ?? null;
        $record->JNSROBT     = $data['JNSROBT'] ?? null;
        $record->KDOBT       = $data['KDOBT'] ?? null;
        $record->SIGNA1OBT   = $data['SIGNA1OBT'] ?? 1;
        $record->SIGNA2OBT   = $data['SIGNA2OBT'] ?? 1;
        $record->PERMINTAAN  = $data['PERMINTAAN'] ?? null;
        $record->JMLOBT      = $data['JMLOBT'] ?? 1;
        $record->JHO         = $data['JHO'] ?? 1;
        $record->CatKhsObt   = $data['CatKhsObt'] ?? 'Non Racikan';
        $record->RESPONSE    = $data['RESPONSE'] ?? 'Unknown';
        $record->STATUS      = $data['STATUS'] ?? 0;

        $record->save();

        Log::info('LogResepDetil saved', [
            'kunjungan' => $record->KUNJUNGAN,
            'ref_farmasi' => $record->REF_FARMASI,
            'status' => $record->STATUS,
            'response' => $record->RESPONSE
        ]);

        return $record;
    }
}
