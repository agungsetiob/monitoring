<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntreanResepController;
use App\Http\Controllers\PatientMonitoringController;

Route::get('/', function () {
    return inertia('Landing');
})->name('landing-page');

Route::get('/pasien-igd', [PatientMonitoringController::class, 'index'])
->name('pasien-igd.index');
Route::get('/api/patient-igd', [PatientMonitoringController::class, 'getPatients'])
->name('api.patient-igd');

Route::get('/resep', [AntreanResepController::class, 'index'])
->name('antrean-farmasi.index');
Route::get('/api/antrean-farmasi/data', [AntreanResepController::class, 'getAntrean'])
->name('api.antrean-farmasi.data');

