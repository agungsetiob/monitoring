<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AntreanResepController;
use App\Http\Controllers\PatientMonitoringController;

Route::get('/pasien-igd', [PatientMonitoringController::class, 'index']);
Route::get('/api/patient-igd', [PatientMonitoringController::class, 'getPatients']);

Route::get('/resep', [AntreanResepController::class, 'index']);
Route::get('/api/antrean-farmasi/data', [AntreanResepController::class, 'getAntrean']);

