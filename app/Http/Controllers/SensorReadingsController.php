<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SensorReadingController extends Controller
{
    public function index()
    {
        $readings = SensorReading::latest()->take(20)->get()->reverse()->values();

        return Inertia::render('Dashboard', [
            'readings' => $readings
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id'   => 'nullable|string',
            'temperature' => 'required|numeric',
            'vibration_x' => 'required|numeric',
            'vibration_y' => 'required|numeric',
            'vibration_z' => 'required|numeric',
        ]);

        // Lógica simples de classificação preditiva/alerta
        $temp = $validated['temperature'];
        $vibX = abs($validated['vibration_x']);
        $vibY = abs($validated['vibration_y']);
        $vibZ = abs($validated['vibration_z']);

        $status = 'NORMAL';
        if ($temp > 75 || $vibX > 2.5 || $vibY > 2.5 || $vibZ > 2.5) {
            $status = 'CRITICO';
        } elseif ($temp > 60 || $vibX > 1.5 || $vibY > 1.5 || $vibZ > 1.5) {
            $status = 'ATENCAO';
        }

        $reading = SensorReading::create([
            'device_id'   => $validated['device_id'] ?? 'ESP32_01',
            'temperature' => $validated['temperature'],
            'vibration_x' => $validated['vibration_x'],
            'vibration_y' => $validated['vibration_y'],
            'vibration_z' => $validated['vibration_z'],
            'status'      => $status,
        ]);

        return response()->json([
            'message' => 'Leitura armazenada com sucesso!',
            'data'    => $reading,
        ], 201);
    }

    public function getLatest()
    {
        $readings = SensorReading::latest()->take(30)->get()->reverse()->values();
        return response()->json($readings);
    }
}
