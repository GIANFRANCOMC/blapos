<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Contracts\System\Tenancy\{TenantAwareJob};
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Tests\{TestCase};

final class TenantRuntimeArchitectureTest extends TestCase {
    public function test_only_landlord_models_pin_the_landlord_connection(): void {

        foreach($this->phpFiles(app_path("Models")) as $file) {

            $content = file_get_contents($file->getPathname());

            if(!str_contains($content, 'protected $connection = "landlord"')) {

                continue;

            }

            $this->assertStringContainsString(
                DIRECTORY_SEPARATOR."Tenancy".DIRECTORY_SEPARATOR,
                $file->getPathname(),
                $file->getPathname()
            );

        }

    }

    public function test_http_layer_does_not_accept_a_company_selector(): void {

        foreach($this->phpFiles(app_path("Http")) as $file) {

            $content = file_get_contents($file->getPathname());

            $this->assertStringNotContainsString('input("company_id"', $content, $file->getPathname());
            $this->assertStringNotContainsString('input("companyId"', $content, $file->getPathname());
            $this->assertStringNotContainsString('"company_id" => [', $content, $file->getPathname());

        }

    }

    public function test_file_uploads_use_the_tenant_storage_namespace(): void {

        foreach($this->phpFiles(app_path()) as $file) {

            $content = file_get_contents($file->getPathname());

            if(!str_contains($content, "->store(") && !str_contains($content, "->storeAs(")) {

                continue;

            }

            $this->assertStringContainsString("TenantStoragePath", $content, $file->getPathname());

        }

    }

    public function test_every_queued_job_declares_the_tenant_contract(): void {

        $queuedJobs = 0;

        foreach($this->phpFiles(app_path("Jobs")) as $file) {

            $content = file_get_contents($file->getPathname());

            if(!str_contains($content, "ShouldQueue")) {

                continue;

            }

            $queuedJobs++;

            $class = $this->className($content);
            $reflection = new ReflectionClass($class);

            $this->assertTrue(
                $reflection->implementsInterface(TenantAwareJob::class),
                "{$class} debe implementar TenantAwareJob."
            );

        }

        $this->assertGreaterThanOrEqual(0, $queuedJobs);

    }

    public function test_provisioning_has_failure_recovery_and_health_gate(): void {

        $provisioner = file_get_contents(app_path("Services/System/Tenancy/PlatformTenantProvisioner.php"));
        $command = file_get_contents(app_path("Console/Commands/CreateTenantCompany.php"));
        $migration = file_get_contents(database_path(
            "migrations/landlord/2026_06_25_000001_create_tenant_registry_tables.php"
        ));

        $this->assertStringContainsString("PROVISIONING_FAILED", $provisioner);
        $this->assertStringContainsString('"--force" => $existing !== null', $provisioner);
        $this->assertStringContainsString('Artisan::call("system:doctor")', $command);
        $this->assertStringContainsString("PROVISIONING_FAILED", $command);
        $this->assertStringContainsString('"provisioning_failed"', $migration);
        $this->assertStringContainsString('"maintenance"', $migration);
        $this->assertStringContainsString('"status_reason"', $migration);

    }

    public function test_backup_commands_do_not_expose_database_passwords(): void {

        $service = file_get_contents(app_path("Services/System/Tenancy/TenantBackupService.php"));

        $this->assertStringContainsString("MYSQL_PWD", $service);
        $this->assertStringNotContainsString("--password", $service);
        $this->assertStringContainsString('"tenants/{$tenant->public_id}/backups"', $service);

    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFiles(string $path): iterable {

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach($files as $file) {

            if($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === "php") {

                yield $file;

            }

        }

    }

    private function className(string $content): string {

        preg_match('/namespace\s+([^;]+);/', $content, $namespace);
        preg_match('/(?:final\s+)?class\s+(\w+)/', $content, $class);

        return trim($namespace[1])."\\".$class[1];

    }
}
