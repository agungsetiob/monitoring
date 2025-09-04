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

        $existing = LogKirimResep::where('KUNJUNGAN', $kunjungan)->get();
        if($existing->count() > 0) {
            Log::warning('LogKirimResep records found for KUNJUNGAN', ['kunjungan' => $kunjungan, 'count' => $existing->count()]);
        }

        if ($existing->count() > 0) {
            $record = $existing->first();
            foreach ($data as $key => $value) {
                $record->$key = $value;
            }
            $record->save();

            Log::info('LogKirimResep updated', [
                'kunjungan' => $kunjungan,
                'id' => $record->id,
                'updated_fields' => array_keys($data)
            ]);
        } else {
            $record = LogKirimResep::create($data);

            Log::info('LogKirimResep inserted', [
                'kunjungan' => $kunjungan,
                'id' => $record->id,
                'inserted_fields' => array_keys($data)
            ]);
        }

        return $record;
    }
}
