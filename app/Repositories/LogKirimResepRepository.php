<?php

namespace App\Repositories;

use App\Models\LogKirimResep;
use Illuminate\Support\Facades\Log;

class LogKirimResepRepository
{
    /**
     * Insert atau update record LogKirimResep
     */
    public function upsert(array $data): LogKirimResep
    {
        $kunjungan = $data['KUNJUNGAN'] ?? '0';

        $record = LogKirimResep::updateOrCreate(
            ['KUNJUNGAN' => $kunjungan],
            $data
        );

        if ($record->wasRecentlyCreated) {
            Log::info('LogKirimResep inserted', [
                'kunjungan' => $kunjungan,
                'id' => $record->id,
                'inserted_fields' => array_keys($data)
            ]);
        } else {
            Log::info('LogKirimResep updated', [
                'kunjungan' => $kunjungan,
                'id' => $record->id,
                'updated_fields' => array_keys($data)
            ]);
        }

        return $record;
    }
}
