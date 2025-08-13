<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
                'data' => $data,
                'unit' => $igd,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal mengambil data pasien',
                'error' => $e->getMessage(),
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
        $results = DB::connection('simgos')->select("
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
            LIMIT 20
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
        if ($item->RESUSITASI)
            return 'P1';
        if ($item->EMERGENCY)
            return 'P2';
        if ($item->URGENT)
            return 'P3';
        if ($item->LESS_URGENT)
            return 'P4';
        if ($item->NON_URGENT)
            return 'P5';
        if ($item->DOA)
            return 'DOA';
        return 'unclassified';
    }

    private function getIgdToRanapPatients($unitId, $startDate, $endDate, $perPage = 20)
    {
        return DB::connection('simgos')->table('pendaftaran.kunjungan as k')
            ->select(
                'p.NORM',
                'p2.NAMA',
                'k.MASUK',
                'pri.KUNJUNGAN',
                DB::raw("CONCAT_WS(' ', pg.GELAR_DEPAN, pg.NAMA, pg.GELAR_BELAKANG) AS DPJP_RANAP")
            )
            ->leftJoin('pendaftaran.pendaftaran as p', 'p.NOMOR', '=', 'k.NOPEN')
            ->leftJoin('master.pasien as p2', 'p.NORM', '=', 'p2.NORM')
            ->leftJoin('medicalrecord.perencanaan_rawat_inap as pri', 'pri.KUNJUNGAN', '=', 'k.NOMOR')
            ->leftJoin('master.dokter as d', 'd.ID', '=', 'pri.DOKTER')
            ->leftJoin('master.pegawai as pg', 'pg.NIP', '=', 'd.NIP')
            ->where('k.RUANGAN', $unitId)
            ->where('k.STATUS', 2)
            ->whereNotNull('pri.KUNJUNGAN')
            ->whereBetween(DB::raw('DATE(k.MASUK)'), [$startDate, $endDate])
            ->orderByDesc('k.MASUK')
            ->paginate($perPage);
    }

    private function getIgdRanapSummary($unitId, $startDate, $endDate)
    {
        $summary = DB::connection('simgos')->select("
        SELECT DATE(k.MASUK) AS tanggal, COUNT(*) AS total
        FROM pendaftaran.kunjungan k
        LEFT JOIN medicalrecord.perencanaan_rawat_inap pri ON pri.KUNJUNGAN = k.NOMOR
        WHERE k.RUANGAN = ?
        AND k.STATUS = 2
        AND pri.KUNJUNGAN IS NOT NULL
        AND DATE(k.MASUK) BETWEEN ? AND ?
        GROUP BY DATE(k.MASUK)
        ORDER BY tanggal ASC
    ", [$unitId, $startDate, $endDate]);

        $byDoctor = DB::connection('simgos')->select("
        SELECT 
            CONCAT_WS(' ', pg.GELAR_DEPAN, pg.NAMA, pg.GELAR_BELAKANG) AS dokter,
            COUNT(*) AS total
        FROM pendaftaran.kunjungan k
        LEFT JOIN medicalrecord.perencanaan_rawat_inap pri ON pri.KUNJUNGAN = k.NOMOR
        LEFT JOIN master.dokter d ON d.ID = pri.DOKTER
        LEFT JOIN master.pegawai pg ON pg.NIP = d.NIP
        WHERE k.RUANGAN = ?
        AND k.STATUS = 2
        AND pri.KUNJUNGAN IS NOT NULL
        AND DATE(k.MASUK) BETWEEN ? AND ?
        GROUP BY dokter
        ORDER BY total DESC
    ", [$unitId, $startDate, $endDate]);

        return [
            'per_hari' => $summary,
            'per_dokter' => $byDoctor
        ];
    }
    public function laporanIgdRanap(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $perPage = $request->input('per_page', 20);

        $data = $this->getIgdToRanapPatients($this->igd, $startDate, $endDate, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data pasien IGD lanjut rawat inap',
            'data' => $data
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function laporanIgdRanapView(Request $request)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $perPage = $request->input('per_page', 20);

        return inertia('Laporan/IgdRanap', [
            'patients' => $this->getIgdToRanapPatients($this->igd, $startDate, $endDate, $perPage),
            'summary' => $this->getIgdRanapSummary($this->igd, $startDate, $endDate),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'per_page' => (int) $perPage
        ]);
    }
    public function kepadatanIgd(Request $request)
    {
        $start = $request->start_date ?? now()->toDateString();
        $end = $request->end_date ?? now()->toDateString();

        // Per jam
        $data = DB::connection('simgos')->table('pendaftaran.kunjungan')
            ->selectRaw("EXTRACT(HOUR FROM MASUK) as jam, COUNT(*) as jumlah")
            ->where('RUANGAN', $this->igd)
            ->whereBetween(DB::raw('DATE(MASUK)'), [$start, $end])
            ->groupBy('jam')
            ->orderBy('jam')
            ->get();

        $jamLengkap = collect(range(0, 23))->map(function ($j) use ($data) {
            $found = $data->firstWhere('jam', $j);
            return [
                'jam' => $j,
                'jumlah' => $found->jumlah ?? 0
            ];
        });

        // Per shift
        $shiftData = DB::connection('simgos')->table('pendaftaran.kunjungan')
            ->selectRaw("
            CASE 
                WHEN EXTRACT(HOUR FROM MASUK) >= 8 AND EXTRACT(HOUR FROM MASUK) < 14 THEN 'Pagi'
                WHEN EXTRACT(HOUR FROM MASUK) >= 14 AND EXTRACT(HOUR FROM MASUK) < 20 THEN 'Siang'
                ELSE 'Malam'
            END AS shift,
            COUNT(*) as jumlah
        ")
            ->where('RUANGAN', $this->igd)
            ->whereBetween(DB::raw('DATE(MASUK)'), [$start, $end])
            ->groupBy('shift')
            ->orderByRaw("FIELD(shift, 'Pagi', 'Siang', 'Malam')")
            ->get();

        $shiftLengkap = collect(['Pagi', 'Siang', 'Malam'])->map(function ($s) use ($shiftData) {
            $found = $shiftData->firstWhere('shift', $s);
            return [
                'shift' => $s,
                'jumlah' => $found->jumlah ?? 0
            ];
        });

        return Inertia::render('Laporan/KepadatanIgd', [
            'start_date' => $start,
            'end_date' => $end,
            'data_per_jam' => $jamLengkap,
            'data_per_shift' => $shiftLengkap
        ]);
    }

}
