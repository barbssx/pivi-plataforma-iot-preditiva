<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('database:import-sqlite {--source= : Caminho do arquivo SQLite que será importado}')]
#[Description('Importa usuários e leituras do SQLite para a conexão padrão sem modificar a origem')]
class ImportSqliteDatabase extends Command
{
    public function handle(): int
    {
        $sourcePath = $this->option('source') ?: config('database.connections.sqlite_import.database');

        if (! is_string($sourcePath) || ! is_file($sourcePath)) {
            $this->components->error('O arquivo SQLite de origem não foi encontrado.');

            return self::FAILURE;
        }

        $targetConnectionName = DB::getDefaultConnection();
        $target = DB::connection($targetConnectionName);

        if ($targetConnectionName === 'sqlite_import') {
            $this->components->error('A conexão de destino não pode ser a conexão SQLite de importação.');

            return self::FAILURE;
        }

        if ($target->getDriverName() === 'sqlite') {
            $targetPath = config("database.connections.{$targetConnectionName}.database");

            if (is_string($targetPath) && $targetPath !== ':memory:' && realpath($targetPath) === realpath($sourcePath)) {
                $this->components->error('A origem e o destino não podem ser o mesmo arquivo SQLite.');

                return self::FAILURE;
            }
        }

        config(['database.connections.sqlite_import.database' => $sourcePath]);
        DB::purge('sqlite_import');

        $source = DB::connection('sqlite_import');
        $sourceSchema = Schema::connection('sqlite_import');
        $targetSchema = Schema::connection($targetConnectionName);

        foreach (['users', 'sensor_readings'] as $table) {
            if (! $sourceSchema->hasTable($table) || ! $targetSchema->hasTable($table)) {
                $this->components->error("A tabela {$table} precisa existir na origem e no destino. Execute as migrações do destino antes de importar.");

                return self::FAILURE;
            }
        }

        $userSourceColumns = $sourceSchema->getColumnListing('users');
        $readingSourceColumns = $sourceSchema->getColumnListing('sensor_readings');
        $userTargetColumns = $targetSchema->getColumnListing('users');
        $readingTargetColumns = $targetSchema->getColumnListing('sensor_readings');

        if (! $this->hasColumns($userSourceColumns, ['id', 'name', 'email', 'password'])
            || ! $this->hasColumns($readingSourceColumns, ['id', 'temperature'])
            || ! $this->hasAnyColumn($readingSourceColumns, ['vibracao_x', 'vibration_x'])
            || ! $this->hasAnyColumn($readingSourceColumns, ['vibracao_y', 'vibration_y'])
            || ! $this->hasAnyColumn($readingSourceColumns, ['vibracao_z', 'vibration_z'])
            || ! $this->hasAnyColumn($readingSourceColumns, ['status_operacional', 'status'])
            || ! $this->hasColumns($userTargetColumns, ['id', 'name', 'email', 'password'])
            || ! $this->hasColumns($readingTargetColumns, [
                'id',
                'device_id',
                'temperature',
                'vibracao_x',
                'vibracao_y',
                'vibracao_z',
                'status_operacional',
            ])) {
            $this->components->error('O esquema SQLite de origem não corresponde a um esquema de usuários e telemetria suportado.');

            return self::FAILURE;
        }

        $usersImported = 0;
        $readingsImported = 0;
        $destinationIsOccupied = false;

        $target->transaction(function () use (
            $source,
            $target,
            $userSourceColumns,
            $readingSourceColumns,
            $userTargetColumns,
            $readingTargetColumns,
            &$usersImported,
            &$readingsImported,
            &$destinationIsOccupied,
        ): void {
            if ($target->table('users')->exists() || $target->table('sensor_readings')->exists()) {
                $destinationIsOccupied = true;

                return;
            }

            $source->table('users')->orderBy('id')->chunk(500, function (Collection $users) use (
                $target,
                $userSourceColumns,
                $userTargetColumns,
                &$usersImported,
            ): void {
                $records = $users->map(fn (object $user): array => $this->mapColumns(
                    (array) $user,
                    $userSourceColumns,
                    $userTargetColumns,
                ))->all();

                if ($records !== []) {
                    $target->table('users')->insert($records);
                    $usersImported += count($records);
                }
            });

            $source->table('sensor_readings')->orderBy('id')->chunk(500, function (Collection $readings) use (
                $target,
                $readingSourceColumns,
                $readingTargetColumns,
                &$readingsImported,
            ): void {
                $records = $readings->map(fn (object $reading): array => $this->mapReading(
                    (array) $reading,
                    $readingSourceColumns,
                    $readingTargetColumns,
                ))->all();

                if ($records !== []) {
                    $target->table('sensor_readings')->insert($records);
                    $readingsImported += count($records);
                }
            });
        });

        if ($destinationIsOccupied) {
            $this->components->error('O destino já contém usuários ou leituras; a importação foi cancelada sem gravar dados.');

            return self::FAILURE;
        }

        $this->components->info("Importação concluída: {$usersImported} usuário(s) e {$readingsImported} leitura(s). A origem não foi alterada.");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $required
     */
    private function hasColumns(array $columns, array $required): bool
    {
        return array_diff($required, $columns) === [];
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $candidates
     */
    private function hasAnyColumn(array $columns, array $candidates): bool
    {
        return array_intersect($columns, $candidates) !== [];
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $sourceColumns
     * @param  array<int, string>  $targetColumns
     * @return array<string, mixed>
     */
    private function mapColumns(array $record, array $sourceColumns, array $targetColumns): array
    {
        $columns = array_intersect($sourceColumns, $targetColumns);

        return array_intersect_key($record, array_flip($columns));
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $sourceColumns
     * @param  array<int, string>  $targetColumns
     * @return array<string, mixed>
     */
    private function mapReading(array $record, array $sourceColumns, array $targetColumns): array
    {
        $mapped = $this->mapColumns($record, $sourceColumns, $targetColumns);

        foreach ([
            'vibracao_x' => ['vibracao_x', 'vibration_x'],
            'vibracao_y' => ['vibracao_y', 'vibration_y'],
            'vibracao_z' => ['vibracao_z', 'vibration_z'],
            'status_operacional' => ['status_operacional', 'status'],
            'nivel_fluido' => ['nivel_fluido', 'fluid_level'],
        ] as $targetColumn => $sourceCandidates) {
            if (in_array($targetColumn, $targetColumns, true)) {
                foreach ($sourceCandidates as $sourceColumn) {
                    if (in_array($sourceColumn, $sourceColumns, true)) {
                        $mapped[$targetColumn] = $record[$sourceColumn];

                        break;
                    }
                }
            }
        }

        if (in_array('rpm', $targetColumns, true) && ! in_array('rpm', $sourceColumns, true)) {
            $mapped['rpm'] = null;
        }

        return $mapped;
    }
}
