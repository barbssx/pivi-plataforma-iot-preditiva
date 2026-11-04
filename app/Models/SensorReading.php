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
        'temperature',
        'vibracao_x',
        'vibracao_y',
        'vibracao_z',
        'nivel_fluido',
        'rpm',
        'status_operacional',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'vibracao_x' => 'float',
            'vibracao_y' => 'float',
            'vibracao_z' => 'float',
            'rpm' => 'integer',
        ];
    }
}
