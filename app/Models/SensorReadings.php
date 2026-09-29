<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorReadings extends Model
{
    use HasFactory;

    protected $table = 'sensor_readings';
    protected $fillable = [
        'id',
        'device_id',
        'temperature',
        'vibration_x',
        'vibration_y',
        'vibration_z',
        'status',
    ];
}
