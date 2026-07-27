<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Realiza un backup (mysqldump) de las bases de datos master y tenant.
 * Diseñado para ejecutarse automáticamente a las 00:00 vía schedule.
 *
 * Uso:
 *   php artisan db:backup
 *   php artisan db:backup --retention=7   (días a conservar; default 7)
 */
class DatabaseBackup extends Command
{
    protected $signature = 'db:backup
                            {--retention=7 : Días de retención de backups antiguos}
                            {--path= : Directorio base de backups (default: storage/app/backups)}';

    protected $description = 'Crea un backup mysqldump de las BD master y tenant y limpia los antiguos.';

    public function handle(): int
    {
        $retention = (int) $this->option('retention');
        $basePath = $this->option('path') ?: storage_path('app/backups');
        $timestamp = now()->format('Ymd_His');

        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0775, true);
        }

        $connections = [
            'master' => config('database.default'),
            'tenant' => 'tenant',
        ];

        $results = [];

        foreach ($connections as $label => $connName) {
            $config = config("database.connections.{$connName}");
            if (!$config || $config['driver'] !== 'mysql') {
                $this->warn("Conexión {$label} ({$connName}) no es MySQL; se omite.");
                $results[$label] = 'skip';
                continue;
            }

            $dbName = $config['database'];
            $host = $config['host'];
            $port = $config['port'] ?? 3306;
            $user = $config['username'];
            $pass = $config['password'];
            $socket = $config['unix_socket'] ?? '';

            $fileName = "{$label}_{$dbName}_{$timestamp}.sql.gz";
            $filePath = "{$basePath}/{$fileName}";

            // Construir comando mysqldump
            $escPass = escapeshellarg($pass);
            $escUser = escapeshellarg($user);
            $escHost = escapeshellarg($host);
            $escPort = escapeshellarg((string)$port);
            $escDb = escapeshellarg($dbName);
            $escPath = escapeshellarg($filePath);

            $socketOpt = $socket ? ' --socket=' . escapeshellarg($socket) : '';

            // Usar MYSQL_PWD para no exponer la contraseña en el proceso list
            $cmd = "MYSQL_PWD={$escPass} mysqldump -u {$escUser} -h {$escHost} -P {$escPort}{$socketOpt} --single-transaction --routines --triggers {$escDb} 2>&1 | gzip > {$escPath}";

            $output = [];
            $exitCode = 0;
            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0 || !File::exists($filePath) || File::size($filePath) === 0) {
                $this->error("Backup de {$label} ({$dbName}) falló: " . implode("\n", $output));
                Log::error('DatabaseBackup failed', [
                    'label' => $label,
                    'db' => $dbName,
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]);
                $results[$label] = 'fail';
            } else {
                $size = File::size($filePath);
                $this->info("Backup de {$label} ({$dbName}) OK: {$fileName} (" . round($size / 1024, 1) . " KB)");
                Log::info('DatabaseBackup success', [
                    'label' => $label,
                    'db' => $dbName,
                    'file' => $fileName,
                    'size' => $size,
                ]);
                $results[$label] = 'ok';
            }
        }

        // Limpieza de backups antiguos
        $this->cleanupOldBackups($basePath, $retention);

        $allOk = !in_array('fail', $results, true);
        return $allOk ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Elimina archivos .sql.gz más antiguos que el número de días de retención.
     */
    protected function cleanupOldBackups(string $path, int $retention): void
    {
        if ($retention <= 0) {
            return;
        }

        $cutoff = now()->subDays($retention)->getTimestamp();
        $files = File::glob("{$path}/*.sql.gz");

        foreach ($files as $file) {
            if (File::lastModified($file) < $cutoff) {
                File::delete($file);
                $this->line("Backup antiguo eliminado: " . basename($file));
            }
        }
    }
}