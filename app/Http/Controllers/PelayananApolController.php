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
}
