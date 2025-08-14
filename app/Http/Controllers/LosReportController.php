<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LosReportController extends Controller
{
    private $igd = 101010101;

    public function index(Request $request)
    {
        $start_date = $request->input('start_date', now()->toDateString());
        $end_date = $request->input('end_date', now()->toDateString());

        return inertia('Laporan/Los', [
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    public function getLosReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate   = $request->input('end_date', now()->toDateString());

        $page = max((int) $request->input('page', 1), 1);
        $perPage = max((int) $request->input('per_page', 20), 1);

        $aggRows = DB::connection('simgos')->select("
            SELECT
                COUNT(*) AS total_count,
                AVG(TIMESTAMPDIFF(SECOND, k.MASUK, k.KELUAR) / 3600) AS avg_hours,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR) < 60 THEN 1 ELSE 0 END) AS cat_lt1,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR) >= 60 AND TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR) < 180 THEN 1 ELSE 0 END) AS cat_1_3,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR) >= 180 AND TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR) < 360 THEN 1 ELSE 0 END) AS cat_3_6,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR) >= 360 AND TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR) < 480 THEN 1 ELSE 0 END) AS cat_6_8,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR) >= 480 THEN 1 ELSE 0 END) AS cat_gt8
            FROM pendaftaran.kunjungan k
            WHERE k.RUANGAN = ?
              AND k.STATUS <> 1
              AND DATE(k.MASUK) BETWEEN ? AND ?
              AND k.KELUAR IS NOT NULL
        ", [$this->igd, $startDate, $endDate]);

        $agg = $aggRows[0] ?? null;

        $total = $agg ? (int) $agg->total_count : 0;
        $averageLos = ($agg && $agg->avg_hours !== null) ? round((float) $agg->avg_hours, 2) : 0;

        $categories = [
            '<1h'  => $agg ? (int) $agg->cat_lt1  : 0,
            '1-3h' => $agg ? (int) $agg->cat_1_3  : 0,
            '3-6h' => $agg ? (int) $agg->cat_3_6  : 0,
            '6-8h' => $agg ? (int) $agg->cat_6_8  : 0,
            '>8h'  => $agg ? (int) $agg->cat_gt8  : 0,
        ];

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        if ($page > $lastPage) $page = $lastPage;
        $offset = ($page - 1) * $perPage;

        $results = DB::connection('simgos')->select("
            SELECT
                k.NOPEN,
                k.MASUK,
                k.KELUAR,
                p2.NAMA,
                p.NORM,
                TIMESTAMPDIFF(MINUTE, k.MASUK, k.KELUAR) AS LOS_MINUTES
            FROM pendaftaran.kunjungan k
            LEFT JOIN pendaftaran.pendaftaran p ON p.NOMOR = k.NOPEN
            LEFT JOIN master.pasien p2 ON p.NORM = p2.NORM
            WHERE k.RUANGAN = ?
              AND k.STATUS <> 1
              AND DATE(k.MASUK) BETWEEN ? AND ?
              AND k.KELUAR IS NOT NULL
            ORDER BY k.MASUK ASC
            LIMIT ? OFFSET ?
        ", [$this->igd, $startDate, $endDate, (int) $perPage, (int) $offset]);

        foreach ($results as &$row) {
            $row->LOS_HOURS = round(($row->LOS_MINUTES / 60), 2);
        }

        return response()->json([
            'success' => true,
            'average_los' => $averageLos,
            'categories' => $categories,
            'patients' => $results,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage
            ]
        ]);
    }
}
