<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TriageReportController extends Controller
{
    private $igd = 101010101;

    public function index(Request $request)
    {
        return inertia('Laporan/Triage', [
            'start_date' => $request->input('start_date', now()->toDateString()),
            'end_date' => $request->input('end_date', now()->toDateString())
        ]);
    }

    public function getSummary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $summary = $this->getTriageSummary($startDate, $endDate);

        return response()->json([
            'report' => $summary,
            'percentages' => $this->calculatePercentages($summary),
        ]);
    }

    public function getDailyTrendData(Request $request)
    {
        return response()->json([
            'daily_trend' => $this->getDailyTrend(
                $request->input('start_date'),
                $request->input('end_date')
            ),
        ]);
    }

    public function getAverageLosData(Request $request)
    {
        return response()->json([
            'avg_los' => $this->getAverageLos(
                $request->input('start_date'),
                $request->input('end_date')
            ),
        ]);
    }

    private function calculatePercentages($summary)
    {
        $total = array_sum((array) $summary);
        return $total > 0 ? array_map(
            fn($value) => round(($value / $total) * 100, 2),
            (array) $summary
        ) : array_fill_keys(['P1', 'P2', 'P3', 'P4', 'P5', 'DOA'], 0);
    }

    private function getTriageSummary($startDate, $endDate)
    {
        $query = DB::connection('simgos')
            ->table(DB::raw('(
            SELECT
                k.NOMOR,
                MAX(CASE WHEN JSON_EXTRACT(t.RESUSITASI, \'$.CHECKED\') IN (true, 1, \'1\') THEN 1 ELSE 0 END) AS RESUSITASI_CHECKED,
                MAX(CASE WHEN JSON_EXTRACT(t.EMERGENCY, \'$.CHECKED\') IN (true, 1, \'1\') THEN 1 ELSE 0 END) AS EMERGENCY_CHECKED,
                MAX(CASE WHEN JSON_EXTRACT(t.URGENT, \'$.CHECKED\') IN (true, 1, \'1\') THEN 1 ELSE 0 END) AS URGENT_CHECKED,
                MAX(CASE WHEN JSON_EXTRACT(t.LESS_URGENT, \'$.CHECKED\') IN (true, 1, \'1\') THEN 1 ELSE 0 END) AS LESS_URGENT_CHECKED,
                MAX(CASE WHEN JSON_EXTRACT(t.NON_URGENT, \'$.CHECKED\') IN (true, 1, \'1\') THEN 1 ELSE 0 END) AS NON_URGENT_CHECKED,
                MAX(CASE WHEN JSON_EXTRACT(t.DOA, \'$.CHECKED\') IN (true, 1, \'1\') THEN 1 ELSE 0 END) AS DOA_CHECKED
            FROM pendaftaran.kunjungan k
            LEFT JOIN medicalrecord.triage t ON t.KUNJUNGAN = k.NOMOR
            WHERE k.RUANGAN = ?
              AND k.STATUS = 2
              AND DATE(k.MASUK) BETWEEN ? AND ?
            GROUP BY k.NOMOR
        ) AS per_visit'))
            ->selectRaw("
            SUM(RESUSITASI_CHECKED) AS P1,
            SUM(EMERGENCY_CHECKED) AS P2,
            SUM(URGENT_CHECKED) AS P3,
            SUM(LESS_URGENT_CHECKED) AS P4,
            SUM(NON_URGENT_CHECKED) AS P5,
            SUM(DOA_CHECKED) AS DOA
        ")
            ->addBinding($this->igd, 'where')
            ->addBinding($startDate, 'where')
            ->addBinding($endDate, 'where')
            ->first();

        return $query ?: (object) ['P1' => 0, 'P2' => 0, 'P3' => 0, 'P4' => 0, 'P5' => 0, 'DOA' => 0];
    }

    private function getDailyTrend($startDate, $endDate)
    {
        return DB::connection('simgos')
            ->table('pendaftaran.kunjungan as k')
            ->leftJoin('medicalrecord.triage as t', 't.KUNJUNGAN', '=', 'k.NOMOR')
            ->selectRaw("
                DATE(k.MASUK) as tanggal,
                SUM(CASE WHEN JSON_EXTRACT(t.RESUSITASI, '$.CHECKED') IN (true, 1, '1') THEN 1 ELSE 0 END) AS P1,
                SUM(CASE WHEN JSON_EXTRACT(t.EMERGENCY, '$.CHECKED') IN (true, 1, '1') THEN 1 ELSE 0 END) AS P2,
                SUM(CASE WHEN JSON_EXTRACT(t.URGENT, '$.CHECKED') IN (true, 1, '1') THEN 1 ELSE 0 END) AS P3,
                SUM(CASE WHEN JSON_EXTRACT(t.LESS_URGENT, '$.CHECKED') IN (true, 1, '1') THEN 1 ELSE 0 END) AS P4,
                SUM(CASE WHEN JSON_EXTRACT(t.NON_URGENT, '$.CHECKED') IN (true, 1, '1') THEN 1 ELSE 0 END) AS P5,
                SUM(CASE WHEN JSON_EXTRACT(t.DOA, '$.CHECKED') IN (true, 1, '1') THEN 1 ELSE 0 END) AS DOA
            ")
            ->where('k.RUANGAN', $this->igd)
            ->where('k.STATUS', 2)
            ->whereBetween(DB::raw('DATE(k.MASUK)'), [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(k.MASUK)'))
            ->orderBy('tanggal')
            ->get();
    }

    private function getAverageLos($startDate, $endDate)
    {
        return DB::connection('simgos')
            ->table('pendaftaran.kunjungan as k')
            ->leftJoin('medicalrecord.triage as t', 't.KUNJUNGAN', '=', 'k.NOMOR')
            ->selectRaw("
                CASE
                    WHEN JSON_EXTRACT(t.RESUSITASI, '$.CHECKED') IN (true, 1, '1') THEN 'P1'
                    WHEN JSON_EXTRACT(t.EMERGENCY, '$.CHECKED') IN (true, 1, '1') THEN 'P2'
                    WHEN JSON_EXTRACT(t.URGENT, '$.CHECKED') IN (true, 1, '1') THEN 'P3'
                    WHEN JSON_EXTRACT(t.LESS_URGENT, '$.CHECKED') IN (true, 1, '1') THEN 'P4'
                    WHEN JSON_EXTRACT(t.NON_URGENT, '$.CHECKED') IN (true, 1, '1') THEN 'P5'
                    WHEN JSON_EXTRACT(t.DOA, '$.CHECKED') IN (true, 1, '1') THEN 'DOA'
                END AS kategori,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR)), 2) AS avg_los_minutes
            ")
            ->where('k.RUANGAN', $this->igd)
            ->where('k.STATUS', 2)
            ->whereNotNull('k.KELUAR')
            ->whereBetween(DB::raw('DATE(k.MASUK)'), [$startDate, $endDate])
            ->whereNotNull(DB::raw("
                CASE
                    WHEN JSON_EXTRACT(t.RESUSITASI, '$.CHECKED') IN (true, 1, '1') THEN 'P1'
                    WHEN JSON_EXTRACT(t.EMERGENCY, '$.CHECKED') IN (true, 1, '1') THEN 'P2'
                    WHEN JSON_EXTRACT(t.URGENT, '$.CHECKED') IN (true, 1, '1') THEN 'P3'
                    WHEN JSON_EXTRACT(t.LESS_URGENT, '$.CHECKED') IN (true, 1, '1') THEN 'P4'
                    WHEN JSON_EXTRACT(t.NON_URGENT, '$.CHECKED') IN (true, 1, '1') THEN 'P5'
                    WHEN JSON_EXTRACT(t.DOA, '$.CHECKED') IN (true, 1, '1') THEN 'DOA'
                END
            "))
            ->groupBy('kategori')
            ->orderByRaw("FIELD(kategori, 'P1', 'P2', 'P3', 'P4', 'P5', 'DOA')")
            ->get();
    }
}