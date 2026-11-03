<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SQLiteImportTest extends TestCase
{
    use RefreshDatabase;

    private ?string $sourceDatabasePath = null;

    protected function tearDown(): void
    {
        DB::purge('sqlite_import');
        config(['database.connections.sqlite_import.database' => database_path('database.sqlite')]);

        if ($this->sourceDatabasePath !== null && is_file($this->sourceDatabasePath)) {
            unlink($this->sourceDatabasePath);
        }

        parent::tearDown();
    }

    public function test_it_imports_users_and_legacy_sensor_readings_without_losing_hashes_or_timestamps(): void
    {
        $this->createSourceDatabase();
        $this->insertSourceUser();
        $this->insertSourceReading();

        $this->artisan('database:import-sqlite', ['--source' => $this->sourceDatabasePath])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'id' => 17,
            'email' => 'operator@example.com',
            'password' => '$2y$12$existing-password-hash',
            'email_verified_at' => '2026-01-02 03:04:05',
            'created_at' => '2026-01-01 00:00:00',
        ]);

        $this->assertDatabaseHas('sensor_readings', [
            'id' => 28,
            'device_id' => 'ESP32_01',
            'temperature' => 72.5,
            'vibracao_x' => 0.2,
            'vibracao_y' => 1.6,
            'vibracao_z' => 0.4,
            'status_operacional' => 'ATENCAO',
            'nivel_fluido' => null,
            'rpm' => null,
            'created_at' => '2026-01-03 04:05:06',
        ]);
    }

    public function test_it_imports_operational_fields_when_the_source_schema_has_them(): void
    {
        $this->createSourceDatabase(withOperationalFields: true);
        $this->insertSourceUser();
        $this->insertSourceReading(withOperationalFields: true);

        $this->artisan('database:import-sqlite', ['--source' => $this->sourceDatabasePath])
            ->assertExitCode(0);

        $this->assertDatabaseHas('sensor_readings', [
            'id' => 28,
            'vibracao_z' => 0.4,
            'nivel_fluido' => 'FALHA',
            'rpm' => 850,
        ]);
    }

    public function test_it_refuses_to_import_into_a_non_empty_destination(): void
    {
        $this->createSourceDatabase();
        $this->insertSourceUser();
        $this->insertSourceReading();

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Existing user',
            'email' => 'existing@example.com',
            'password' => 'existing-hash',
        ]);

        $this->artisan('database:import-sqlite', ['--source' => $this->sourceDatabasePath])
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('sensor_readings', 0);
        $this->assertDatabaseHas('users', ['id' => 1, 'email' => 'existing@example.com']);
    }

    private function createSourceDatabase(bool $withOperationalFields = false): void
    {
        $path = tempnam(database_path(), 'sqlite-import-');

        $this->assertNotFalse($path);
        $this->sourceDatabasePath = $path;

        config(['database.connections.sqlite_import.database' => $path]);
        DB::purge('sqlite_import');

        Schema::connection('sqlite_import')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::connection('sqlite_import')->create('sensor_readings', function (Blueprint $table) use ($withOperationalFields): void {
            $table->id();
            $table->string('device_id')->default('ESP31_01');
            $table->float('temperature');

            if ($withOperationalFields) {
                $table->float('vibracao_x');
                $table->float('vibracao_y');
                $table->float('vibracao_z');
                $table->string('status_operacional');
                $table->string('nivel_fluido')->nullable();
                $table->integer('rpm')->nullable();
            } else {
                $table->float('vibration_x');
                $table->float('vibration_y');
                $table->float('vibration_z');
                $table->string('status');
            }

            $table->timestamps();
        });
    }

    private function insertSourceUser(): void
    {
        DB::connection('sqlite_import')->table('users')->insert([
            'id' => 17,
            'name' => 'Telemetry operator',
            'email' => 'operator@example.com',
            'email_verified_at' => '2026-01-02 03:04:05',
            'password' => '$2y$12$existing-password-hash',
            'remember_token' => 'existing-remember-token',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-04 05:06:07',
        ]);
    }

    private function insertSourceReading(bool $withOperationalFields = false): void
    {
        $reading = [
            'id' => 28,
            'device_id' => 'ESP32_01',
            'temperature' => 72.5,
            'created_at' => '2026-01-03 04:05:06',
            'updated_at' => '2026-01-03 04:05:07',
        ];

        if ($withOperationalFields) {
            $reading += [
                'vibracao_x' => 0.2,
                'vibracao_y' => 1.6,
                'vibracao_z' => 0.4,
                'status_operacional' => 'ATENCAO',
                'nivel_fluido' => 'FALHA',
                'rpm' => 850,
            ];
        } else {
            $reading += [
                'vibration_x' => 0.2,
                'vibration_y' => 1.6,
                'vibration_z' => 0.4,
                'status' => 'ATENCAO',
            ];
        }

        DB::connection('sqlite_import')->table('sensor_readings')->insert($reading);
    }
}
