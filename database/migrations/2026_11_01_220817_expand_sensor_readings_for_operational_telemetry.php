<?php

declare(strict_types=1);

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
        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->renameColumn('vibration_x', 'vibracao_x');
            $table->renameColumn('vibration_y', 'vibracao_y');
            $table->renameColumn('vibration_z', 'vibracao_z');
            $table->renameColumn('status', 'status_operacional');
        });

        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->float('temperature')->change();
            $table->float('vibracao_x')->change();
            $table->float('vibracao_y')->change();
            $table->float('vibracao_z')->change();
            $table->string('nivel_fluido')->nullable();
            $table->integer('rpm')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->dropColumn(['nivel_fluido', 'rpm']);
        });

        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->renameColumn('vibracao_x', 'vibration_x');
            $table->renameColumn('vibracao_y', 'vibration_y');
            $table->renameColumn('vibracao_z', 'vibration_z');
            $table->renameColumn('status_operacional', 'status');
        });
    }
};
