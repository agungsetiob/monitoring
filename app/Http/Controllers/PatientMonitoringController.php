<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PatientMonitoringController extends Controller
{
    private $igd = 101010101;

    /**
     * Display the initial view.
     */
    public function index()
    {
        return inertia('PasienIgd/Index', [
            'initialData' => $this->getPatientData($this->igd),
        ]);
    }

    /**
     * JSON API: Return the patient data.
     */
    public function getPatients(Request $request)
    {
        $data = $this->getPatientData($this->igd);
        return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Helper method to get the patient data.
     */
    private function getPatientData($igd)
    {
        try {
            $data = $this->getIgdPatients($igd);

            return [
                'success' => true,
                'message' => 'Data pasien berhasil diambil',
                'data'    => $data,
                'unit'    => $igd,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal mengambil data pasien',
                'error'   => $e->getMessage(),
            ];
        }
    }

    private function formatNama($nama)
    {
        $kata = explode(" ", $nama);
        $formatted = [];

        foreach ($kata as $word) {
            if (strlen($word) >= 3) {
                $formatted[] = substr($word, 0, 3) . "**";
            } else {
                $formatted[] = $word . "**";
            }
        }
        return implode(" ", $formatted);
    }

    private function formatNorm($norm)
    {
        $norm = str_pad($norm, 6, '0', STR_PAD_LEFT);
        return substr($norm, 0, 2) . '.' . substr($norm, 2, 2) . '.' . substr($norm, 4, 2);
    }

    private function getIgdPatients($unitId)
    {
        $results = DB::select("
            SELECT  
                k.NOPEN, 
                k.MASUK, 
                DATE_FORMAT(k.MASUK, '%d-%m-%Y %H:%i:%s') AS TANGGAL,
                p.NORM,
                p2.NAMA,
                p2.JENIS_KELAMIN,
                r.DESKRIPSI AS RUANGAN,
                k.NOMOR AS KUNJUNGAN_ID,
                MAX(CASE WHEN cppt.STATUS_SBAR = 1 THEN 1 ELSE 0 END) AS STATUS_SBAR,
                MAX(CASE WHEN cppt.STATUS_TBAK = 1 THEN 1 ELSE 0 END) AS STATUS_TBAK,
                MAX(CASE WHEN cppt.STATUS_SBAR = 1 THEN cppt.TANGGAL END) AS TANGGAL_SBAR,
                MAX(CASE WHEN cppt.STATUS_TBAK = 1 THEN cppt.TANGGAL END) AS TANGGAL_TBAK,
                pri.JENIS_RUANG_PERAWATAN,
                pri.JENIS_PERAWATAN,
                pri.NOMOR_REFERENSI,
                pri.DOKTER AS ID_DOKTER_SPRI,
                CONCAT_WS(' ', 
                    pg.GELAR_DEPAN, 
                    pg.NAMA, 
                    pg.GELAR_BELAKANG
                ) AS DPJP_RANAP,
                pri.DIBUAT_TANGGAL AS TANGGAL_DIBUAT_PERENCANAAN,
                t.RESUSITASI,
                t.EMERGENCY,
                t.URGENT,
                t.LESS_URGENT,
                t.NON_URGENT,
                t.DOA,
                t.KRITERIA,
                t.HANDOVER
            FROM pendaftaran.kunjungan k
            LEFT JOIN pendaftaran.pendaftaran p ON p.NOMOR = k.NOPEN
            LEFT JOIN master.pasien p2 ON p.NORM = p2.NORM
            LEFT JOIN master.ruangan r ON r.ID = k.RUANGAN
            LEFT JOIN medicalrecord.cppt cppt ON cppt.KUNJUNGAN = k.NOMOR
            LEFT JOIN medicalrecord.perencanaan_rawat_inap pri ON pri.KUNJUNGAN = k.NOMOR
            LEFT JOIN master.dokter d ON d.ID = pri.DOKTER
            LEFT JOIN master.pegawai pg ON pg.NIP = d.NIP
            LEFT JOIN medicalrecord.triage t ON t.KUNJUNGAN = k.NOMOR
            WHERE k.RUANGAN = ?
            AND k.STATUS = 1
            GROUP BY k.NOPEN, k.MASUK, p.NORM, p2.NAMA, p2.JENIS_KELAMIN, r.DESKRIPSI, k.NOMOR,
                    pri.JENIS_RUANG_PERAWATAN, pri.JENIS_PERAWATAN, pri.TANGGAL, pri.INDIKASI,
                    pri.DESKRIPSI, pri.DOKTER, pri.DIBUAT_TANGGAL, pg.GELAR_DEPAN, pg.NAMA, pg.GELAR_BELAKANG,
                    t.RESUSITASI, t.EMERGENCY, t.URGENT, t.LESS_URGENT, t.NON_URGENT, t.DOA, t.KRITERIA, t.HANDOVER
            ORDER BY k.MASUK DESC
            LIMIT 10
        ", [$unitId]);

        return $this->formatResults($results);
    }

    private function formatResults($results)
    {
        return array_map(function ($item) {
            // Format basic fields
            $item->NAMA = $this->formatNama($item->NAMA);
            $item->NORM = $this->formatNorm($item->NORM);
            $item->STATUS_TBAK = (int) $item->STATUS_TBAK;
            $item->STATUS_SBAR = (int) $item->STATUS_SBAR;
            $item->JENIS_KELAMIN = (int) $item->JENIS_KELAMIN;

            // Format triage JSON fields
            $triageFields = [
                'RESUSITASI', 
                'EMERGENCY', 
                'URGENT', 
                'LESS_URGENT', 
                'NON_URGENT', 
                'DOA'
            ];

            foreach ($triageFields as $field) {
                if (isset($item->$field)) {
                    $decoded = json_decode($item->$field, true);
                    $item->$field = (int) ($decoded['CHECKED'] ?? 0);
                } else {
                    $item->$field = 0;
                }
            }

            $item->TRIAGE_STATUS = $this->determineTriageStatus($item);

            return $item;
        }, $results);
    }

    private function determineTriageStatus($item)
    {
        if ($item->RESUSITASI) return 'P1';
        if ($item->EMERGENCY) return 'P2';
        if ($item->URGENT) return 'P3';
        if ($item->LESS_URGENT) return 'P4';
        if ($item->NON_URGENT) return 'P5';
        if ($item->DOA) return 'DOA';
        return 'unclassified';
    }
}
