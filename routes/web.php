<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntreanResepController;
use App\Http\Controllers\PatientMonitoringController;

Route::get('/', function () {
    return inertia('Landing');
})->name('landing-page');

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/e418d78f33c3716d01a492eec5ba33dc', [PatientMonitoringController::class, 'index'])
        ->name('pasien-igd.index');
    
    Route::get('/api/patient-igd', [PatientMonitoringController::class, 'getPatients'])
        ->name('api.patient-igd');

    Route::get('/resep', [AntreanResepController::class, 'index'])
        ->name('antrean-farmasi.index');
    
    Route::get('/api/antrean-farmasi/data', [AntreanResepController::class, 'getAntrean'])
        ->name('api.antrean-farmasi.data');
});
