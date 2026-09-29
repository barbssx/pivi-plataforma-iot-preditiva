<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->default('ESP31_01');
            $table->decimal('temperature', 5,2);
            $table->decimal('vibration_x', 5,2);
            $table->decimal('vibration_y', 5,2);
            $table->decimal('vibration_z', 5,2);
            $table->string('status')->default('NORMAL');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
