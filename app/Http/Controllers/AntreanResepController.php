<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AntreanResepController extends Controller
{
    private $depoList = [
        'rajal' => 102040201,
        'ranap' => 102040202,
        'ok'    => 102040203,
        'igd'   => 102040204,
    ];

    /**
     * Display the initial Inertia view.
     */
    public function index(Request $request)
    {
        $selectedDepo = $request->input('depo', 'rajal');
        $idDepo = $this->depoList[$selectedDepo] ?? $this->depoList['rajal'];

        return inertia('AntreanFarmasi/Index', [
            'initialData'  => $this->getAntreanData($idDepo),
            'depoOptions'  => array_keys($this->depoList),
            'selectedDepo' => $selectedDepo,
        ]);
    }

    /**
     * JSON API: Return the antrian data.
     */
    public function getAntrean(Request $request)
    {
        $selectedDepo = $request->input('depo', 'rajal');
        $idDepo = $this->depoList[$selectedDepo] ?? $this->depoList['rajal'];
        $data = $this->getAntreanData($idDepo);

        return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Helper method to get the antrian data.
     */
    private function getAntreanData($idDepo)
    {
        try {
            $data = [
                'belum_diterima' => $this->getBelumDiterima($idDepo),
                'dilayani'       => $this->getDilayani($idDepo),
                'selesai'        => $this->getSelesai($idDepo),
                'final'          => $this->getFinal($idDepo),
            ];

            return [
                'success' => true,
                'message' => 'Data antrian berhasil diambil',
                'data'    => $data,
                'depo'    => $idDepo,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal mengambil data antrian',
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

    private function getBelumDiterima($idDepo)
    {
        $results = DB::select("
            SELECT 
                k.NOPEN, 
                DATE_FORMAT(or2.TANGGAL, '%d-%m-%Y %H:%i:%s') AS TANGGAL,
                p.NORM,
                p2.NAMA,
                p2.JENIS_KELAMIN,
                or2.CITO,
                MAX(CASE WHEN odr.RACIKAN = 1 THEN 1 ELSE 0 END) AS RACIKAN,
                r.DESKRIPSI AS ASAL_RUANGAN
            FROM layanan.order_resep or2
            LEFT JOIN pendaftaran.kunjungan k ON k.NOMOR = or2.KUNJUNGAN
            LEFT JOIN pendaftaran.pendaftaran p ON p.NOMOR = k.NOPEN
            LEFT JOIN master.pasien p2 ON p.NORM = p2.NORM
            LEFT JOIN master.ruangan r ON r.ID = k.RUANGAN
            LEFT JOIN layanan.order_detil_resep odr ON odr.ORDER_ID = or2.NOMOR
            WHERE or2.TUJUAN = ?
              AND or2.STATUS = 1
              AND DATE(k.MASUK) = CURDATE()
            GROUP BY or2.NOMOR
            ORDER BY k.MASUK DESC
            LIMIT 10
        ", [$idDepo]);

        return $this->formatResults($results);
    }

    private function getDilayani($idDepo)
    {
        $results = DB::select("
            SELECT 
                k.NOPEN, 
                DATE_FORMAT(k.MASUK, '%d-%m-%Y %H:%i:%s') AS TANGGAL,
                p.NORM,
                p2.NAMA,
                p2.JENIS_KELAMIN,
                or2.CITO,
                MAX(CASE WHEN odr.RACIKAN = 1 THEN 1 ELSE 0 END) AS RACIKAN,
                rasal.DESKRIPSI AS ASAL_RUANGAN
            FROM pendaftaran.kunjungan k
            LEFT JOIN pendaftaran.pendaftaran p ON p.NOMOR = k.NOPEN
            LEFT JOIN layanan.order_resep or2 ON or2.NOMOR = k.REF
            LEFT JOIN layanan.order_detil_resep odr ON odr.ORDER_ID = or2.NOMOR 
            LEFT JOIN pendaftaran.kunjungan pk ON pk.NOMOR = or2.KUNJUNGAN
            LEFT JOIN master.ruangan rasal ON rasal.ID = pk.RUANGAN
            LEFT JOIN master.pasien p2 ON p.NORM = p2.NORM
            LEFT JOIN master.ruangan r ON r.ID = k.RUANGAN
            WHERE k.RUANGAN = ?
              AND k.STATUS = 1
              AND DATE(k.MASUK) = CURDATE()
            GROUP BY 
                k.NOPEN, k.REF, k.NOMOR, k.MASUK, p.NORM, p2.NAMA, or2.CITO, rasal.DESKRIPSI
            ORDER BY k.MASUK DESC
            LIMIT 10
        ", [$idDepo]);

        return $this->formatResults($results);
    }

    private function getSelesai($idDepo)
    {
        $results = DB::select("
            SELECT 
                k.NOPEN, 
                DATE_FORMAT(k.MASUK, '%d-%m-%Y %H:%i:%s') AS TANGGAL,
                p.NORM,
                p2.NAMA,
                p2.JENIS_KELAMIN,
                or2.CITO,
                MAX(CASE WHEN odr.RACIKAN = 1 THEN 1 ELSE 0 END) AS RACIKAN,
                rasal.DESKRIPSI AS ASAL_RUANGAN
            FROM pendaftaran.kunjungan k
            LEFT JOIN pendaftaran.pendaftaran p ON p.NOMOR = k.NOPEN
            LEFT JOIN layanan.order_resep or2 ON or2.NOMOR = k.REF
            LEFT JOIN layanan.order_detil_resep odr ON odr.ORDER_ID = or2.NOMOR
            LEFT JOIN pendaftaran.kunjungan pk ON pk.NOMOR = or2.KUNJUNGAN
            LEFT JOIN master.ruangan rasal ON rasal.ID = pk.RUANGAN
            LEFT JOIN master.pasien p2 ON p.NORM = p2.NORM
            LEFT JOIN master.ruangan r ON r.ID = k.RUANGAN
            WHERE 
                k.RUANGAN = ?
                AND k.STATUS = 2
                AND NOT EXISTS (
                    SELECT 1 
                    FROM layanan.telaah_awal_resep tar 
                    WHERE tar.RESEP = k.NOMOR 
                      AND tar.STATUS = 1
                )
                AND DATE(k.MASUK) = CURDATE()
            GROUP BY 
                k.NOPEN, k.REF, k.NOMOR, k.MASUK, p.NORM, p2.NAMA, or2.CITO, rasal.DESKRIPSI
            ORDER BY k.MASUK DESC
            LIMIT 10
        ", [$idDepo]);

        return $this->formatResults($results);
    }

    private function getFinal($idDepo)
    {
        $results = DB::select("
            SELECT 
                k.NOPEN, 
                DATE_FORMAT(k.KELUAR, '%d-%m-%Y %H:%i:%s') AS TANGGAL,
                p.NORM,
                p2.NAMA,
                p2.JENIS_KELAMIN,
                or2.CITO,
                MAX(CASE WHEN odr.RACIKAN = 1 THEN 1 ELSE 0 END) AS RACIKAN,
                rasal.DESKRIPSI AS ASAL_RUANGAN
            FROM pendaftaran.kunjungan k
            LEFT JOIN pendaftaran.pendaftaran p ON p.NOMOR = k.NOPEN
            LEFT JOIN layanan.order_resep or2 ON or2.NOMOR = k.REF
            LEFT JOIN layanan.order_detil_resep odr ON odr.ORDER_ID = or2.NOMOR
            LEFT JOIN pendaftaran.kunjungan pk ON pk.NOMOR = or2.KUNJUNGAN
            LEFT JOIN master.ruangan rasal ON rasal.ID = pk.RUANGAN
            LEFT JOIN master.pasien p2 ON p.NORM = p2.NORM
            LEFT JOIN master.ruangan r ON r.ID = k.RUANGAN
            WHERE 
                k.RUANGAN = ?
                AND k.STATUS = 2
                AND EXISTS (
                    SELECT 1 
                    FROM layanan.telaah_awal_resep tar 
                    WHERE tar.RESEP = k.NOMOR 
                      AND tar.STATUS = 1
                )
                AND DATE(k.KELUAR) = CURDATE()
            GROUP BY 
                k.NOPEN, k.REF, k.NOMOR, k.MASUK, p.NORM, p2.NAMA, or2.CITO, rasal.DESKRIPSI
            ORDER BY k.MASUK DESC
            LIMIT 12
        ", [$idDepo]);

        return $this->formatResults($results);
    }

    private function formatResults($results)
    {
        return array_map(function ($item) {
            $item->NAMA = $this->formatNama($item->NAMA);
            $item->NORM = $this->formatNorm($item->NORM);
            return $item;
        }, $results);
    }
}
