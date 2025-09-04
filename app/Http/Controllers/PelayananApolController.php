<?php

namespace App\Http\Controllers;

use App\Services\SimgosService;
use Illuminate\Http\Request;

class PelayananApolController extends Controller
{
    protected SimgosService $simgos;

    public function __construct(SimgosService $simgos)
    {
        $this->simgos = $simgos;
    }

    /**
     * Ambil data resep klaim terpisah dari SIMGOS
     */
    public function resepKlaimTerpisah(Request $request)
    {
        $filters = $request->only([
            'PAWAL',
            'PAKHIR',
            'page',
            'start',
            'limit',
            'JENIS',
            'JENIS_RESEP'
        ]);

        $data = $this->simgos->getResep($filters);

        return response()->json($data);
    }

    /**
     * Ambil detail resep (list obat) dari SIMGOS
     */
    public function resepDetil(Request $request)
    {
        $resepId = $request->get('RESEP');

        if (!$resepId) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter RESEP wajib diisi'
            ], 422);
        }

        $data = $this->simgos->getResepDetil(['RESEP' => $resepId]);

        return response()->json($data);
    }
}
