<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SensorReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SensorReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_stores_portuguese_sensor_fields_and_auxiliary_values(): void
    {
        $response = $this->postJson('/api/sensor-readings', $this->payload([
            'temperature' => 58.75,
            'vibracao_x' => 0.25,
            'vibracao_y' => 0.5,
            'vibracao_z' => 0.75,
            'nivel_fluido' => 'FALHA',
            'rpm' => 850,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.vibracao_x', 0.25)
            ->assertJsonPath('data.nivel_fluido', 'FALHA')
            ->assertJsonPath('data.rpm', 850)
            ->assertJsonPath('data.status_operacional', 'NORMAL');

        $this->assertDatabaseHas('sensor_readings', [
            'temperature' => 58.75,
            'vibracao_x' => 0.25,
            'vibracao_y' => 0.5,
            'vibracao_z' => 0.75,
            'nivel_fluido' => 'FALHA',
            'rpm' => 850,
            'status_operacional' => 'NORMAL',
        ]);
    }

    public function test_api_accepts_legacy_english_vibration_fields(): void
    {
        $payload = $this->payload();
        unset($payload['vibracao_x'], $payload['vibracao_y'], $payload['vibracao_z']);

        $response = $this->postJson('/api/sensor-readings', array_replace($payload, [
            'vibration_x' => 0.25,
            'vibration_y' => 0.5,
            'vibration_z' => 1.75,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.vibracao_z', 1.75)
            ->assertJsonPath('data.status_operacional', 'ATENCAO');
    }

    #[DataProvider('temperatureThresholds')]
    public function test_temperature_thresholds_classify_operational_status(float $temperature, string $expectedStatus): void
    {
        $response = $this->postJson('/api/sensor-readings', $this->payload([
            'temperature' => $temperature,
        ]));

        $response->assertCreated()->assertJsonPath('data.status_operacional', $expectedStatus);
    }

    public static function temperatureThresholds(): array
    {
        return [
            'normal at 60 degrees' => [60.0, 'NORMAL'],
            'attention above 60 degrees' => [60.01, 'ATENCAO'],
            'attention at 75 degrees' => [75.0, 'ATENCAO'],
            'critical above 75 degrees' => [75.01, 'CRITICO'],
        ];
    }

    #[DataProvider('vibrationThresholds')]
    public function test_each_vibration_axis_threshold_classifies_operational_status(
        string $axis,
        float $vibration,
        string $expectedStatus,
    ): void {
        $response = $this->postJson('/api/sensor-readings', $this->payload([
            $axis => $vibration,
        ]));

        $response->assertCreated()->assertJsonPath('data.status_operacional', $expectedStatus);
    }

    public static function vibrationThresholds(): array
    {
        return [
            'x normal at 1.5' => ['vibracao_x', 1.5, 'NORMAL'],
            'y attention above 1.5' => ['vibracao_y', 1.51, 'ATENCAO'],
            'z attention at 2.5' => ['vibracao_z', 2.5, 'ATENCAO'],
            'x critical above 2.5' => ['vibracao_x', 2.51, 'CRITICO'],
            'negative vibration uses its magnitude' => ['vibracao_y', -2.51, 'CRITICO'],
        ];
    }

    public function test_most_severe_temperature_or_vibration_status_wins(): void
    {
        $response = $this->postJson('/api/sensor-readings', $this->payload([
            'temperature' => 61,
            'vibracao_z' => 2.51,
        ]));

        $response->assertCreated()->assertJsonPath('data.status_operacional', 'CRITICO');
    }

    public function test_invalid_fluid_level_and_rpm_are_rejected_without_persisting(): void
    {
        $response = $this->postJson('/api/sensor-readings', $this->payload([
            'nivel_fluido' => 'BAIXO',
            'rpm' => -5,
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nivel_fluido', 'rpm']);

        $this->assertDatabaseCount('sensor_readings', 0);
    }

    public function test_latest_endpoint_returns_the_last_twenty_readings_in_chronological_order(): void
    {
        $readings = [];

        foreach (range(1, 21) as $index) {
            $readings[] = SensorReading::create([
                'device_id' => 'ESP32_01',
                'temperature' => 50 + $index,
                'vibracao_x' => 0.1,
                'vibracao_y' => 0.2,
                'vibracao_z' => 0.3,
                'nivel_fluido' => 'OK',
                'rpm' => 800,
                'status_operacional' => 'NORMAL',
                'created_at' => now()->addSeconds($index),
                'updated_at' => now()->addSeconds($index),
            ]);
        }

        $response = $this->getJson('/api/sensor-readings/latest');

        $response->assertOk()->assertJsonCount(20);
        $this->assertSame($readings[1]->id, $response->json('0.id'));
        $this->assertSame($readings[20]->id, $response->json('19.id'));
    }

    public function test_api_stores_full_esp32_payload_with_accelerometers_and_rpm_metadata(): void
    {
        $payload = [
            'device_id' => 'ESP32_MOTO_01',
            'sequence_number' => 125,
            'uptime_ms' => 625000,
            'recorded_at' => '2026-11-04T22:30:00Z',
            'temperature' => 42.8,
            'rpm' => 862.45,
            'rpm_status' => 'MEDINDO',
            'rpm_configuration' => 'AMBIGUA',
            'rpm_total_pulses' => 8950,
            'nivel_fluido' => null,
            'status_operacional' => 'TESTE',
            'accelerometers' => [
                ['id' => 1, 'channel' => 0, 'x_g' => 0.12, 'y_g' => 0.15, 'z_g' => 1.01, 'rms_g' => 1.028],
                ['id' => 2, 'channel' => 1, 'x_g' => 0.09, 'y_g' => 0.11, 'z_g' => 0.99, 'rms_g' => 1.000],
                ['id' => 3, 'channel' => 2, 'x_g' => 0.14, 'y_g' => 0.10, 'z_g' => 1.02, 'rms_g' => 1.034],
                ['id' => 4, 'channel' => 3, 'x_g' => 0.08, 'y_g' => 0.13, 'z_g' => 1.00, 'rms_g' => 1.012],
            ],
        ];

        $response = $this->postJson('/api/sensor-readings', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.device_id', 'ESP32_MOTO_01')
            ->assertJsonPath('data.sequence_number', 125)
            ->assertJsonPath('data.uptime_ms', 625000)
            ->assertJsonPath('data.temperature', 42.8)
            ->assertJsonPath('data.rpm', 862.45)
            ->assertJsonPath('data.rpm_status', 'MEDINDO')
            ->assertJsonPath('data.rpm_configuration', 'AMBIGUA')
            ->assertJsonPath('data.rpm_total_pulses', 8950)
            ->assertJsonPath('data.vibracao_x', 0.12)
            ->assertJsonPath('data.vibracao_y', 0.15)
            ->assertJsonPath('data.vibracao_z', 1.01)
            ->assertJsonPath('data.adxl1', 1.028)
            ->assertJsonPath('data.adxl2', 1)
            ->assertJsonPath('data.adxl3', 1.034)
            ->assertJsonPath('data.adxl4', 1.012)
            ->assertJsonPath('data.status_operacional', 'TESTE');

        $this->assertDatabaseHas('sensor_readings', [
            'device_id' => 'ESP32_MOTO_01',
            'sequence_number' => 125,
            'rpm_configuration' => 'AMBIGUA',
            'status_operacional' => 'TESTE',
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'device_id' => 'ESP32_01',
            'temperature' => 55.0,
            'vibracao_x' => 0.5,
            'vibracao_y' => 0.75,
            'vibracao_z' => 1.0,
            'nivel_fluido' => 'OK',
            'rpm' => 1200,
        ], $overrides);
    }
}
