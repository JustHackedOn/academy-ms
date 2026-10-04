<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup
                            {--keep=14 : Number of nightly backups to retain}
                            {--path= : Directory (relative to storage/app) to write into}';

    protected $description = 'Dump the entire database to a restorable .sql file in storage/backups';

    public function handle(): int
    {
        $directory = trim((string) ($this->option('path') ?: 'backups'), '/');
        $filename = 'academy-'.now()->format('Y-m-d-His').'.sql';
        $relativePath = $directory.'/'.$filename;

        try {
            $sql = $this->dump();
        } catch (\Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        Storage::disk('local')->put($relativePath, $sql);
        $this->prune((int) $this->option('keep'), $directory);

        $size = Storage::disk('local')->size($relativePath);
        $this->info(sprintf(
            'Backup written: %s (%s KB)',
            $relativePath,
            number_format($size / 1024, 1)
        ));

        return self::SUCCESS;
    }

    /**
     * Build a plain SQL dump (schema + data) through PDO, so no mysqldump binary
     * is required on shared hosting.
     */
    protected function dump(): string
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        $tables = collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn (string $table) => str_starts_with($table, '_cache') || str_starts_with($table, '_puri'))
            ->values();

        $out = '-- '.academy_name()." database backup\n";
        $out .= '-- Generated: '.now()->toIso8601String()."\n";
        $out .= '-- Engine: '.$driver."\n\n";

        if ($driver === 'mysql') {
            $out .= "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";
        }

        foreach ($tables as $table) {
            $out .= $this->tableSchema($connection, $table).";\n\n";
            $out .= $this->tableData($connection, $pdo, $table);
        }

        if ($driver === 'mysql') {
            $out .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        }

        return $out;
    }

    /**
     * INSERT statements for every row of a table.
     */
    protected function tableData(ConnectionInterface $connection, \PDO $pdo, string $table): string
    {
        $grammar = $connection->getQueryGrammar();
        $wrapped = $grammar->wrapTable($table);
        $columns = Schema::getColumnListing($table);

        if ($columns === []) {
            return '';
        }

        $columnList = implode(', ', array_map(fn ($column) => $grammar->wrap($column), $columns));
        $out = '';
        $first = true;

        $query = $connection->table($table)->select($columns);

        // chunk() needs a deterministic order; prefer the surrogate key.
        $query->orderBy(in_array('id', $columns, true) ? 'id' : $columns[0]);

        $query->chunk(500, function ($rows) use (&$out, &$first, $wrapped, $columnList, $pdo) {
            $values = [];

            foreach ($rows as $row) {
                $values[] = '('.implode(', ', array_map(
                    fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                    (array) $row
                )).')';
            }

            if ($values === []) {
                return;
            }

            $out .= ($first ? '' : ";\n")."INSERT INTO {$wrapped} ({$columnList}) VALUES\n"
                .implode(",\n", $values);
            $first = false;
        });

        return $first === false ? $out.";\n\n" : '';
    }

    /**
     * Full CREATE TABLE statement for the active driver.
     */
    protected function tableSchema($connection, string $table): string
    {
        if ($connection->getDriverName() === 'sqlite') {
            $row = $connection->selectOne(
                "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table]
            );

            return $row->sql ?? "CREATE TABLE {$table} ()";
        }

        $result = $connection->select("SHOW CREATE TABLE `{$table}`");
        $first = (array) reset($result);

        return (string) (array_values($first)[1] ?? "CREATE TABLE `{$table}` ()");
    }

    /**
     * Keep only the newest N dumps.
     */
    protected function prune(int $keep, string $directory): void
    {
        if ($keep <= 0) {
            return;
        }

        collect(Storage::disk('local')->files($directory))
            ->filter(fn (string $path) => str_ends_with($path, '.sql'))
            ->sortDesc()
            ->values()
            ->slice($keep)
            ->each(fn (string $path) => Storage::disk('local')->delete($path));
    }
}
