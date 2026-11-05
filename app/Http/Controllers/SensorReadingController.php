<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SensorReading;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        $input = $request->all();

        if (isset($input['accelerometers']) && is_array($input['accelerometers']) && count($input['accelerometers']) > 0) {
            $first = $input['accelerometers'][0];
            if (!isset($input['vibracao_x']) && !isset($input['vibration_x']) && isset($first['x_g'])) {
                $input['vibracao_x'] = $first['x_g'];
            }
            if (!isset($input['vibracao_y']) && !isset($input['vibration_y']) && isset($first['y_g'])) {
                $input['vibracao_y'] = $first['y_g'];
            }
            if (!isset($input['vibracao_z']) && !isset($input['vibration_z']) && isset($first['z_g'])) {
                $input['vibracao_z'] = $first['z_g'];
            }

            foreach ($input['accelerometers'] as $index => $accel) {
                if (is_array($accel)) {
                    $num = $accel['id'] ?? ($index + 1);
                    $key = 'adxl' . $num;
                    if (!isset($input[$key])) {
                        $input[$key] = $accel['rms_g'] ?? $accel['x_g'] ?? null;
                    }
                }
            }
        }

        if (!isset($input['rpm_configuration'])) {
            $input['rpm_configuration'] = $input['configuracao'] ?? $input['configuration'] ?? null;
        }

        $validated = Validator::make($input, [
            'device_id' => ['nullable', 'string', 'max:255'],
            'sequence_number' => ['nullable', 'integer'],
            'uptime_ms' => ['nullable', 'integer'],
            'recorded_at' => ['nullable', 'string'],
            'timestamp' => ['nullable', 'string'],
            'temperature' => ['required', 'numeric'],
            'vibracao_x' => ['required_without:vibration_x', 'nullable', 'numeric'],
            'vibracao_y' => ['required_without:vibration_y', 'nullable', 'numeric'],
            'vibracao_z' => ['required_without:vibration_z', 'nullable', 'numeric'],
            'vibration_x' => ['required_without:vibracao_x', 'nullable', 'numeric'],
            'vibration_y' => ['required_without:vibracao_y', 'nullable', 'numeric'],
            'vibration_z' => ['required_without:vibracao_z', 'nullable', 'numeric'],
            'adxl1' => ['nullable', 'numeric'],
            'adxl2' => ['nullable', 'numeric'],
            'adxl3' => ['nullable', 'numeric'],
            'adxl4' => ['nullable', 'numeric'],
            'accelerometers' => ['nullable', 'array'],
            'nivel_fluido' => ['nullable', Rule::in(['OK', 'FALHA'])],
            'fluid_level' => ['nullable', Rule::in(['OK', 'FALHA'])],
            'rpm' => ['nullable', 'numeric', 'min:0'],
            'rpm_status' => ['nullable', 'string', 'max:50'],
            'rpm_configuration' => ['nullable', 'string', 'max:50'],
            'rpm_total_pulses' => ['nullable', 'integer', 'min:0'],
            'status_operacional' => ['nullable', 'string', 'max:50'],
        ])->validate();

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

        $finalStatus = ($validated['status_operacional'] ?? null) === 'TESTE'
            ? 'TESTE'
            : $status;

        $attributes = [
            'device_id' => $validated['device_id'] ?? 'ESP32_01',
            'sequence_number' => isset($validated['sequence_number']) ? (int) $validated['sequence_number'] : null,
            'uptime_ms' => isset($validated['uptime_ms']) ? (int) $validated['uptime_ms'] : null,
            'temperature' => $temperature,
            'vibracao_x' => $vibrationX,
            'vibracao_y' => $vibrationY,
            'vibracao_z' => $vibrationZ,
            'adxl1' => isset($input['adxl1']) ? (float) $input['adxl1'] : null,
            'adxl2' => isset($input['adxl2']) ? (float) $input['adxl2'] : null,
            'adxl3' => isset($input['adxl3']) ? (float) $input['adxl3'] : null,
            'adxl4' => isset($input['adxl4']) ? (float) $input['adxl4'] : null,
            'accelerometers_data' => $input['accelerometers'] ?? null,
            'nivel_fluido' => $validated['nivel_fluido'] ?? $validated['fluid_level'] ?? null,
            'rpm' => isset($validated['rpm']) ? (float) $validated['rpm'] : null,
            'rpm_status' => $validated['rpm_status'] ?? null,
            'rpm_configuration' => $validated['rpm_configuration'] ?? null,
            'rpm_total_pulses' => isset($validated['rpm_total_pulses']) ? (int) $validated['rpm_total_pulses'] : null,
            'status_operacional' => $finalStatus,
        ];

        if (isset($input['recorded_at']) || isset($input['timestamp'])) {
            $timeString = $input['recorded_at'] ?? $input['timestamp'];
            try {
                $attributes['created_at'] = Carbon::parse($timeString);
            } catch (\Throwable) {
            }
        }

        $reading = SensorReading::create($attributes);

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
