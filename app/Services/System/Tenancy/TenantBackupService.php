<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use App\Enums\System\Tenancy\{TenantStatus};
use App\Models\System\Tenancy\{TenantDatabase};
use Illuminate\Support\Facades\{DB, Storage};
use RuntimeException;
use Symfony\Component\Process\{Process};
use Throwable;

final class TenantBackupService {
    public function __construct(
        private readonly TenantConnectionManager $connections,
        private readonly TenantAdministrationService $administration
    ) {
    }

    public function backup(TenantDatabase $tenant, ?string $actor = "system"): string {

        $config = $this->connectionConfig($tenant);
        $disk = (string) config("tenancy.backups.disk", "local");
        $relativeDirectory = "tenants/{$tenant->public_id}/backups";
        $filename = sprintf("%s-%s.sql", $tenant->slug, now()->format("Ymd-His"));
        $relativePath = "{$relativeDirectory}/{$filename}";

        Storage::disk($disk)->makeDirectory($relativeDirectory);
        $absolutePath = Storage::disk($disk)->path($relativePath);

        $process = new Process(
            $this->dumpCommand($config, $absolutePath),
            base_path(),
            $this->processEnvironment($config),
            null,
            (float) config("tenancy.backups.timeout_seconds", 900)
        );

        $process->run();

        if(!$process->isSuccessful() || !is_file($absolutePath) || filesize($absolutePath) === 0) {

            @unlink($absolutePath);

            $message = trim($process->getErrorOutput()) ?: "El respaldo no produjo un archivo válido.";
            $this->administration->audit($tenant, "tenant_backup_failed", "failure", ["reason" => $message], $actor);

            throw new RuntimeException($message);

        }

        $this->prune($tenant);

        $this->administration->audit($tenant, "tenant_backup_created", "success", [
            "path" => $relativePath,
            "bytes" => filesize($absolutePath),
        ], $actor);

        return $relativePath;

    }

    public function restore(TenantDatabase $tenant, string $filename, ?string $actor = "system"): void {

        $disk = (string) config("tenancy.backups.disk", "local");
        $relativePath = "tenants/{$tenant->public_id}/backups/".basename($filename);

        if(!Storage::disk($disk)->exists($relativePath)) {

            throw new RuntimeException("El respaldo solicitado no existe dentro del tenant.");

        }

        $absolutePath = Storage::disk($disk)->path($relativePath);

        $config = $this->connectionConfig($tenant);
        $previousStatus = (string) $tenant->status;

        $this->administration->setSystemStatus(
            $tenant,
            TenantStatus::MAINTENANCE,
            "Restauración de base de datos en curso.",
            $actor
        );

        $stream = fopen($absolutePath, "rb");

        if($stream === false) {

            throw new RuntimeException("No fue posible abrir el respaldo seleccionado.");

        }

        try {

            $process = new Process(
                $this->clientCommand($config),
                base_path(),
                $this->processEnvironment($config),
                null,
                (float) config("tenancy.backups.timeout_seconds", 900)
            );

            $process->setInput($stream);
            $process->run();

            if(!$process->isSuccessful()) {

                throw new RuntimeException(trim($process->getErrorOutput()) ?: "MySQL rechazó el respaldo.");

            }

            $targetStatus = TenantStatus::tryFrom($previousStatus) ?? TenantStatus::INACTIVE;

            $this->administration->setSystemStatus($tenant, $targetStatus, null, $actor);
            $this->administration->audit($tenant, "tenant_backup_restored", "success", ["path" => $relativePath], $actor);

        }catch(Throwable $exception) {

            $this->administration->setSystemStatus(
                $tenant,
                TenantStatus::MAINTENANCE,
                "La restauración falló: ".mb_substr($exception->getMessage(), 0, 1500),
                $actor
            );

            $this->administration->audit($tenant, "tenant_restore_failed", "failure", [
                "path" => $relativePath,
                "reason" => $exception->getMessage(),
            ], $actor);

            throw $exception;

        }finally {

            fclose($stream);

        }

    }

    public function files(TenantDatabase $tenant): array {

        $disk = (string) config("tenancy.backups.disk", "local");
        $directory = "tenants/{$tenant->public_id}/backups";

        return collect(Storage::disk($disk)->files($directory))
            ->filter(fn(string $path): bool => str_ends_with(strtolower($path), ".sql"))
            ->sortDesc()
            ->values()
            ->all();

    }

    private function connectionConfig(TenantDatabase $tenant): array {

        try {

            $this->connections->connect($tenant);

            return DB::connection((string) config("tenancy.tenant_connection", "tenant"))->getConfig();

        }finally {

            $this->connections->disconnect();

        }

    }

    private function dumpCommand(array $config, string $absolutePath): array {

        return array_values(array_filter([
            (string) config("tenancy.backups.dump_binary", "mysqldump"),
            "--host=".(string) ($config["host"] ?? "127.0.0.1"),
            "--port=".(string) ($config["port"] ?? "3306"),
            "--user=".(string) ($config["username"] ?? ""),
            "--single-transaction",
            "--quick",
            "--routines",
            "--triggers",
            "--events",
            "--default-character-set=utf8mb4",
            "--result-file={$absolutePath}",
            (string) ($config["database"] ?? ""),
        ], fn(string $argument): bool => $argument !== ""));

    }

    private function clientCommand(array $config): array {

        return array_values(array_filter([
            (string) config("tenancy.backups.client_binary", "mysql"),
            "--host=".(string) ($config["host"] ?? "127.0.0.1"),
            "--port=".(string) ($config["port"] ?? "3306"),
            "--user=".(string) ($config["username"] ?? ""),
            "--default-character-set=utf8mb4",
            (string) ($config["database"] ?? ""),
        ], fn(string $argument): bool => $argument !== ""));

    }

    private function processEnvironment(array $config): array {

        $password = (string) ($config["password"] ?? "");

        return $password === "" ? [] : ["MYSQL_PWD" => $password];

    }

    private function prune(TenantDatabase $tenant): void {

        $keep = max(1, (int) config("tenancy.backups.retention", 10));
        $disk = (string) config("tenancy.backups.disk", "local");

        collect($this->files($tenant))
            ->slice($keep)
            ->each(fn(string $path) => Storage::disk($disk)->delete($path));

    }
}
