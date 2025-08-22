<?php
use App\Http\Controllers\LosReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntreanResepController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PatientMonitoringController;
use App\Http\Controllers\RencanaKontrolController;
use App\Http\Controllers\TriageReportController;
use Inertia\Inertia;

Route::get('/', function () {
    return inertia('Landing');
})->name('landing-page');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['web', 'auth', 'throttle:60,1', 'role:igd,admin'])->group(function () {
    Route::get('/e418d78f33c3716d01a492eec5ba33dc', [PatientMonitoringController::class, 'index'])
        ->name('pasien-igd.index');
    Route::get('/api/patient-igd', [PatientMonitoringController::class, 'getPatients'])
        ->name('api.patient-igd');
    Route::get('/laporan-igd-ranap', [PatientMonitoringController::class, 'laporanIgdRanapView'])->name('laporan.igd-ranap');
    Route::get('/laporan-igd-ranap/data', [PatientMonitoringController::class, 'laporanIgdRanap'])->name('laporan.ranap.data');
    Route::get('/laporan/kepadatan-igd', [PatientMonitoringController::class, 'kepadatanIgd'])->name('laporan.kepadatan-igd');

    Route::get('laporan', fn() => Inertia::render('Laporan/Index'))->name('laporan.index');
    Route::get('laporan/triage', [TriageReportController::class, 'index'])->name('laporan.triage');
    Route::get('/laporan/triage/summary', [TriageReportController::class, 'getSummary'])->name('laporan.triage.summary');
    Route::get('/laporan/triage/daily-trend', [TriageReportController::class, 'getDailyTrendData'])->name('laporan.triage.daily-trend');
    Route::get('/laporan/triage/average-los', [TriageReportController::class, 'getAverageLosData'])->name('laporan.triage.average-los');

    Route::get('/laporan-los', [LosReportController::class, 'index'])->name('laporan.los');
    Route::get('/laporan-los/data', [LosReportController::class, 'getLosReport'])->name('laporan.los.data');
    
    // Rencana Kontrol Routes
    Route::get('/rencana-kontrol', [RencanaKontrolController::class, 'index'])->name('rencana-kontrol.index');
    Route::get('/rencana-kontrol/update', [RencanaKontrolController::class, 'showUpdateForm'])->name('rencana-kontrol.show-update');
    Route::post('/rencana-kontrol/cari-data', [RencanaKontrolController::class, 'cariData'])->name('rencana-kontrol.cari-data');
    Route::post('/rencana-kontrol/update', [RencanaKontrolController::class, 'updateRencanaKontrol'])->name('rencana-kontrol.update');
});

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/resep', [AntreanResepController::class, 'index'])
        ->name('antrean-farmasi.index');

    Route::get('/api/antrean-farmasi/data', [AntreanResepController::class, 'getAntrean'])
        ->name('api.antrean-farmasi.data');
});

Route::get('/posts-client', function () {
    return Inertia::render('Blog/Index');
});