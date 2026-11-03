<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SensorReadingController extends Controller
{
    public function index(): Response
    {
        $readings = SensorReading::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return Inertia::render('Dashboard', [
            'readings' => $readings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:255'],
            'temperature' => ['required', 'numeric'],
            'vibracao_x' => ['required_without:vibration_x', 'nullable', 'numeric'],
            'vibracao_y' => ['required_without:vibration_y', 'nullable', 'numeric'],
            'vibracao_z' => ['required_without:vibration_z', 'nullable', 'numeric'],
            'vibration_x' => ['required_without:vibracao_x', 'nullable', 'numeric'],
            'vibration_y' => ['required_without:vibracao_y', 'nullable', 'numeric'],
            'vibration_z' => ['required_without:vibracao_z', 'nullable', 'numeric'],
            'nivel_fluido' => ['nullable', Rule::in(['OK', 'FALHA'])],
            'fluid_level' => ['nullable', Rule::in(['OK', 'FALHA'])],
            'rpm' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
        ]);

        $temperature = (float) $validated['temperature'];
        $vibrationX = (float) ($validated['vibracao_x'] ?? $validated['vibration_x']);
        $vibrationY = (float) ($validated['vibracao_y'] ?? $validated['vibration_y']);
        $vibrationZ = (float) ($validated['vibracao_z'] ?? $validated['vibration_z']);
        $vibX = abs($vibrationX);
        $vibY = abs($vibrationY);
        $vibZ = abs($vibrationZ);

        $status = 'NORMAL';
        if ($temperature > 75 || $vibX > 2.5 || $vibY > 2.5 || $vibZ > 2.5) {
            $status = 'CRITICO';
        } elseif ($temperature > 60 || $vibX > 1.5 || $vibY > 1.5 || $vibZ > 1.5) {
            $status = 'ATENCAO';
        }

        $reading = SensorReading::create([
            'device_id' => $validated['device_id'] ?? 'ESP32_01',
            'temperature' => $temperature,
            'vibracao_x' => $vibrationX,
            'vibracao_y' => $vibrationY,
            'vibracao_z' => $vibrationZ,
            'nivel_fluido' => $validated['nivel_fluido'] ?? $validated['fluid_level'] ?? null,
            'rpm' => $validated['rpm'] ?? null,
            'status_operacional' => $status,
        ]);

        return response()->json([
            'message' => 'Leitura armazenada com sucesso!',
            'data' => $reading,
        ], 201);
    }

    public function getLatest(): JsonResponse
    {
        $readings = SensorReading::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return response()->json($readings);
    }
}
