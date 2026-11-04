<?php

use App\Http\Controllers\SensorReadingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/sensor-readings', [SensorReadingController::class, 'store']);
Route::get('/sensor-readings/latest', [SensorReadingController::class, 'getLatest']);
