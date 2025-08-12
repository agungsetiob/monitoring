<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TriageReportController extends Controller
{
    private $igd = 101010101;

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        return inertia('Laporan/Triage', [
            'report' => $this->getTriageSummary($startDate, $endDate, $this->igd),
            'daily_trend' => $this->getDailyTrend($startDate, $endDate, $this->igd),
            'percentages' => $this->getPercentages($startDate, $endDate, $this->igd),
            'avg_los' => $this->getAverageLos($startDate, $endDate, $this->igd),
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    /** 1. Summary total per kategori */
    private function getTriageSummary($startDate, $endDate, $unitId)
    {
        $results = DB::connection('simgos')->select("
            SELECT
                SUM(RESUSITASI_CHECKED) AS P1,
                SUM(EMERGENCY_CHECKED)   AS P2,
                SUM(URGENT_CHECKED)      AS P3,
                SUM(LESS_URGENT_CHECKED) AS P4,
                SUM(NON_URGENT_CHECKED)  AS P5,
                SUM(DOA_CHECKED)         AS DOA
            FROM (
                SELECT
                    k.NOMOR,
                    MAX(CASE WHEN JSON_EXTRACT(t.RESUSITASI, '$.CHECKED') IN (true, 1, '1') THEN 1 ELSE 0 END) AS RESUSITASI_CHECKED,
                    MAX(CASE WHEN JSON_EXTRACT(t.EMERGENCY, '$.CHECKED')   IN (true, 1, '1') THEN 1 ELSE 0 END) AS EMERGENCY_CHECKED,
                    MAX(CASE WHEN JSON_EXTRACT(t.URGENT, '$.CHECKED')      IN (true, 1, '1') THEN 1 ELSE 0 END) AS URGENT_CHECKED,
                    MAX(CASE WHEN JSON_EXTRACT(t.LESS_URGENT, '$.CHECKED') IN (true, 1, '1') THEN 1 ELSE 0 END) AS LESS_URGENT_CHECKED,
                    MAX(CASE WHEN JSON_EXTRACT(t.NON_URGENT, '$.CHECKED')  IN (true, 1, '1') THEN 1 ELSE 0 END) AS NON_URGENT_CHECKED,
                    MAX(CASE WHEN JSON_EXTRACT(t.DOA, '$.CHECKED')         IN (true, 1, '1') THEN 1 ELSE 0 END) AS DOA_CHECKED
                FROM pendaftaran.kunjungan k
                LEFT JOIN medicalrecord.triage t ON t.KUNJUNGAN = k.NOMOR
                WHERE k.RUANGAN = ?
                  AND k.STATUS = 2
                  AND DATE(k.MASUK) BETWEEN ? AND ?
                GROUP BY k.NOMOR
            ) AS per_visit
        ", [$unitId, $startDate, $endDate]);

        return $results[0] ?? (object) [
            'P1' => 0,
            'P2' => 0,
            'P3' => 0,
            'P4' => 0,
            'P5' => 0,
            'DOA' => 0
        ];
    }

    /** Tren harian */
    private function getDailyTrend($startDate, $endDate, $unitId)
    {
        return DB::connection('simgos')->select("
            SELECT
                DATE(k.MASUK) as tanggal,
                SUM(JSON_EXTRACT(t.RESUSITASI, '$.CHECKED') IN (true, 1, '1')) AS P1,
                SUM(JSON_EXTRACT(t.EMERGENCY, '$.CHECKED')   IN (true, 1, '1')) AS P2,
                SUM(JSON_EXTRACT(t.URGENT, '$.CHECKED')      IN (true, 1, '1')) AS P3,
                SUM(JSON_EXTRACT(t.LESS_URGENT, '$.CHECKED') IN (true, 1, '1')) AS P4,
                SUM(JSON_EXTRACT(t.NON_URGENT, '$.CHECKED')  IN (true, 1, '1')) AS P5,
                SUM(JSON_EXTRACT(t.DOA, '$.CHECKED')         IN (true, 1, '1')) AS DOA
            FROM pendaftaran.kunjungan k
            LEFT JOIN medicalrecord.triage t ON t.KUNJUNGAN = k.NOMOR
            WHERE k.RUANGAN = ?
              AND k.STATUS = 2
              AND DATE(k.MASUK) BETWEEN ? AND ?
            GROUP BY DATE(k.MASUK)
            ORDER BY DATE(k.MASUK)
        ", [$unitId, $startDate, $endDate]);
    }

    /** Persentase per kategori */
    private function getPercentages($startDate, $endDate, $unitId)
    {
        $summary = $this->getTriageSummary($startDate, $endDate, $unitId);
        $total = $summary->P1 + $summary->P2 + $summary->P3 + $summary->P4 + $summary->P5 + $summary->DOA;

        $percentages = [];
        foreach (['P1', 'P2', 'P3', 'P4', 'P5', 'DOA'] as $cat) {
            $percentages[$cat] = $total > 0 ? round(($summary->$cat / $total) * 100, 2) : 0;
        }
        return $percentages;
    }

    /** Rata-rata LOS per kategori */
    private function getAverageLos($startDate, $endDate, $unitId)
    {
        return DB::connection('simgos')->select("
        SELECT kategori, ROUND(AVG(TIMESTAMPDIFF(MINUTE, data.MASUK, data.KELUAR)), 2) AS avg_los_minutes
        FROM (
            SELECT
                k.MASUK, k.KELUAR,
                CASE
                    WHEN JSON_EXTRACT(t.RESUSITASI, '$.CHECKED') IN (true, 1, '1') THEN 'P1'
                    WHEN JSON_EXTRACT(t.EMERGENCY, '$.CHECKED')   IN (true, 1, '1') THEN 'P2'
                    WHEN JSON_EXTRACT(t.URGENT, '$.CHECKED')      IN (true, 1, '1') THEN 'P3'
                    WHEN JSON_EXTRACT(t.LESS_URGENT, '$.CHECKED') IN (true, 1, '1') THEN 'P4'
                    WHEN JSON_EXTRACT(t.NON_URGENT, '$.CHECKED')  IN (true, 1, '1') THEN 'P5'
                    WHEN JSON_EXTRACT(t.DOA, '$.CHECKED')         IN (true, 1, '1') THEN 'DOA'
                END as kategori
            FROM pendaftaran.kunjungan k
            LEFT JOIN medicalrecord.triage t ON t.KUNJUNGAN = k.NOMOR
            WHERE k.RUANGAN = ?
              AND k.STATUS = 2
              AND k.KELUAR IS NOT NULL
              AND DATE(k.MASUK) BETWEEN ? AND ?
        ) as data
        WHERE kategori IS NOT NULL
        GROUP BY kategori
    ", [$unitId, $startDate, $endDate]);
    }

}
