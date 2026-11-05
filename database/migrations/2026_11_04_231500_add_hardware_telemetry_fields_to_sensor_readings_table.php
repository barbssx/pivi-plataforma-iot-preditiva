<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->float('rpm')->nullable()->change();
            $table->unsignedBigInteger('sequence_number')->nullable()->after('device_id');
            $table->unsignedBigInteger('uptime_ms')->nullable()->after('sequence_number');
            $table->string('rpm_status')->nullable()->after('rpm');
            $table->string('rpm_configuration')->nullable()->after('rpm_status');
            $table->unsignedBigInteger('rpm_total_pulses')->nullable()->after('rpm_configuration');
            $table->float('adxl1')->nullable()->after('vibracao_z');
            $table->float('adxl2')->nullable()->after('adxl1');
            $table->float('adxl3')->nullable()->after('adxl2');
            $table->float('adxl4')->nullable()->after('adxl3');
            $table->json('accelerometers_data')->nullable()->after('adxl4');
        });
    }

    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->dropColumn([
                'sequence_number',
                'uptime_ms',
                'rpm_status',
                'rpm_configuration',
                'rpm_total_pulses',
                'adxl1',
                'adxl2',
                'adxl3',
                'adxl4',
                'accelerometers_data',
            ]);
            $table->integer('rpm')->nullable()->change();
        });
    }
};
