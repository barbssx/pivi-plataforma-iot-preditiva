<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    use HasFactory;

    protected $table = 'sensor_readings';

    protected $fillable = [
        'device_id',
        'sequence_number',
        'uptime_ms',
        'temperature',
        'rpm',
        'rpm_status',
        'rpm_configuration',
        'rpm_total_pulses',
        'vibracao_x',
        'vibracao_y',
        'vibracao_z',
        'adxl1',
        'adxl2',
        'adxl3',
        'adxl4',
        'accelerometers_data',
        'nivel_fluido',
        'status_operacional',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'rpm' => 'float',
            'sequence_number' => 'integer',
            'uptime_ms' => 'integer',
            'rpm_total_pulses' => 'integer',
            'vibracao_x' => 'float',
            'vibracao_y' => 'float',
            'vibracao_z' => 'float',
            'adxl1' => 'float',
            'adxl2' => 'float',
            'adxl3' => 'float',
            'adxl4' => 'float',
            'accelerometers_data' => 'array',
        ];
    }
}
