<?php
use App\Http\Controllers\LosReportController;
use App\Http\Controllers\PelayananApolController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntreanResepController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PatientMonitoringController;
use App\Http\Controllers\RencanaKontrolController;
use App\Http\Controllers\ApolController;
use App\Http\Controllers\TriageReportController;
use App\Http\Controllers\TesApolController;
use Inertia\Inertia;

Route::get('/', function () {
    return inertia('Landing');
})->name('landing-page');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['web', 'auth', 'throttle:60,1', 'role:igd,admin'])->group(function () {
    Route::get('e418d78f33c3716d01a492eec5ba33dc', [PatientMonitoringController::class, 'index'])
        ->name('pasien-igd.index');
    Route::get('api/patient-igd', [PatientMonitoringController::class, 'getPatients'])
        ->name('api.patient-igd');
    Route::get('laporan-igd-ranap', [PatientMonitoringController::class, 'laporanIgdRanapView'])->name('laporan.igd-ranap');
    Route::get('laporan-igd-ranap/data', [PatientMonitoringController::class, 'laporanIgdRanap'])->name('laporan.ranap.data');
    Route::get('laporan/kepadatan-igd', [PatientMonitoringController::class, 'kepadatanIgd'])->name('laporan.kepadatan-igd');

    Route::get('laporan', fn() => Inertia::render('Laporan/Index'))->name('laporan.index');
    Route::get('laporan/triage', [TriageReportController::class, 'index'])->name('laporan.triage');
    Route::get('laporan/triage/summary', [TriageReportController::class, 'getSummary'])->name('laporan.triage.summary');
    Route::get('laporan/triage/daily-trend', [TriageReportController::class, 'getDailyTrendData'])->name('laporan.triage.daily-trend');
    Route::get('laporan/triage/average-los', [TriageReportController::class, 'getAverageLosData'])->name('laporan.triage.average-los');

    Route::get('laporan-los', [LosReportController::class, 'index'])->name('laporan.los');
    Route::get('laporan-los/data', [LosReportController::class, 'getLosReport'])->name('laporan.los.data');

    // Rencana Kontrol Routes
    Route::get('rencana-kontrol', [RencanaKontrolController::class, 'index'])->name('rencana-kontrol.index');
    Route::get('rencana-kontrol/update', [RencanaKontrolController::class, 'showUpdateForm'])->name('rencana-kontrol.show-update');
    Route::post('rencana-kontrol/cari-data', [RencanaKontrolController::class, 'cariData'])->name('rencana-kontrol.cari-data');
    Route::post('rencana-kontrol/detail', [RencanaKontrolController::class, 'getDetailByNoSuratKontrol'])->name('rencana-kontrol.detail');
    Route::post('rencana-kontrol/jadwal-praktek-dokter', [RencanaKontrolController::class, 'getJadwalPraktekDokter'])->name('rencana-kontrol.jadwal-praktek-dokter');
    Route::post('rencana-kontrol/update', [RencanaKontrolController::class, 'updateRencanaKontrol'])->name('rencana-kontrol.update');
});

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('resep', [AntreanResepController::class, 'index'])
        ->name('antrean-farmasi.index');

    Route::get('api/antrean-farmasi/data', [AntreanResepController::class, 'getAntrean'])
        ->name('api.antrean-farmasi.data');
});

Route::middleware(['web', 'auth', 'throttle:60,1', 'role:admin'])->group(function () {
    Route::get('rencana-kontrol', [RencanaKontrolController::class, 'index'])->name('rencana-kontrol.index');
    Route::get('rencana-kontrol/update', [RencanaKontrolController::class, 'showUpdateForm'])->name('rencana-kontrol.show-update');
    Route::post('rencana-kontrol/cari-data', [RencanaKontrolController::class, 'cariData'])->name('rencana-kontrol.cari-data');
    Route::post('rencana-kontrol/detail', [RencanaKontrolController::class, 'getDetailByNoSuratKontrol'])->name('rencana-kontrol.detail');
    Route::post('rencana-kontrol/update', [RencanaKontrolController::class, 'updateRencanaKontrol'])->name('rencana-kontrol.update');
});
Route::middleware(['web', 'auth', 'throttle:60,1', 'role:admin'])->group(function () {
    Route::get('apol', [ApolController::class, 'index'])->name('apol.index');
    Route::get('apol-simgos-plugin', [ApolController::class, 'simgosPlugin'])->name('apol.simgos-plugin');
    Route::post('apol/daftar-resep', [ApolController::class, 'daftarResep']);
    Route::post('apol/summary-resep', [ApolController::class, 'getSummaryResep']);
    Route::delete('apol/hapus-resep', [ApolController::class, 'hapusResep'])->name('apol.hapus-resep');
    Route::post('apol/hapus-obat', [ApolController::class, 'hapusObat']);
    Route::get('apol/pelayanan/obat/daftar/{nosep?}', [ApolController::class, 'getDaftarPelayananObat'])
        ->name('apol.obat.daftar');
    Route::post('apol/simpan-resep', [ApolController::class, 'simpanResep'])->name('apol.simpan-resep');
    Route::post('apol/update-item-resep', [ApolController::class, 'updateItemResep'])->name('apol.update-item-resep');
    
    //route from ws simgos
    Route::get('resep-klaim-terpisah', function () {
        return inertia('Apol/ResepSimgos');
    })->name('resep-klaim-terpisah');
    Route::get('resep-simgos-plugin', [ApolController::class, 'resepSimgosPlugin']);
    Route::get('resep-simgos', [PelayananApolController::class, 'resepKlaimTerpisah']);
    Route::get('resep-detil', [PelayananApolController::class, 'resepDetil']);


    // Opsional: versi query param fallback ?nosep=...
    Route::get('apol/pelayanan/obat/daftar', [ApolController::class, 'getDaftarPelayananObat']);
    Route::post('apol/hapus-resep', [ApolController::class, 'hapusResep']); // alias untuk klien yg tidak support DELETE
    Route::get('apol/debug/config', [TesApolController::class, 'debugConfig'])->name('debug.config');
    Route::get('apol/debug/endpoint', [TesApolController::class, 'testEndpoint'])->name('debug.endpoint');
    Route::get('apol/debug/auth', [TesApolController::class, 'testAuthentication'])->name('debug.auth');
    Route::get('apol/debug/doc-format', [TesApolController::class, 'testDocumentationFormat'])->name('debug.doc-format');
    Route::get('apol/debug/connection', [TesApolController::class, 'testConnection'])->name('debug.connection');
});
