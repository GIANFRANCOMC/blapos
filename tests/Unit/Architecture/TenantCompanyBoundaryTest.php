<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\{TestCase};

final class TenantCompanyBoundaryTest extends TestCase {
    private const BACKEND_COMPANY_ID_USAGE = [
        "app/Models/Guest/Branch.php" => 2,
        "app/Models/Guest/Company.php" => 1,
        "app/Models/Guest/CompanySocialMedia.php" => 2,
        "app/Models/System/Organizations/Branch.php" => 2,
        "app/Models/System/Organizations/Company.php" => 4,
        "app/Models/System/Organizations/CompanySetting.php" => 2,
        "app/Models/System/Organizations/CompanySocialMedia.php" => 2,
        "app/Models/System/Organizations/CompanySubSection.php" => 2,
        "app/Observers/System/Organizations/CompanySubSectionObserver.php" => 3,
        "app/Services/System/Database/SystemCatalogSyncService.php" => 1,
        "app/Services/System/Essentials/UserNavigationService.php" => 1,
        "app/Services/System/Organizations/Branches/BranchService.php" => 1,
        "app/Services/System/Organizations/BusinessProfileService.php" => 5,
        "app/Services/System/Organizations/Companies/CompanyProvisioningService.php" => 6,
        "app/Services/System/Organizations/Companies/CompanySectionService.php" => 4,
        "app/Services/System/Organizations/Companies/CompanyService.php" => 2,
        "app/Services/System/Organizations/Companies/CompanySettingService.php" => 1,
        "app/Services/System/Organizations/Roles/RolePermissionService.php" => 2,
        "app/Services/System/Tenancy/PlatformTenantService.php" => 3,
    ];

    private const STRUCTURAL_COMPANY_TABLES = [
        "branches",
        "companies_sub_sections",
        "company_settings",
        "company_socials_media",
    ];

    private const STRUCTURAL_COMPANY_SERVICES = [
        "app/Services/System/Organizations/Companies/CompanyProvisioningService.php",
        "app/Services/System/Organizations/Companies/CompanySectionService.php",
        "app/Services/System/Organizations/Roles/RolePermissionService.php",
    ];

    public function test_only_structural_tenant_tables_define_company_id(): void {

        $tables = [];

        foreach($this->phpFiles(database_path("migrations")) as $file) {

            if(str_contains($file->getPathname(), DIRECTORY_SEPARATOR."landlord".DIRECTORY_SEPARATOR)) {

                continue;

            }

            $content = file_get_contents($file->getPathname());

            $blocks = preg_split("/(?=Schema::create\\(\")/", $content) ?: [];

            foreach($blocks as $block) {

                if(!str_contains($block, "\$table->unsignedBigInteger(\"company_id\")")) {

                    continue;

                }

                if(preg_match("/Schema::create\\(\"([^\"]+)\"/", $block, $matches) === 1) {

                    $tables[] = $matches[1];

                }

            }

        }

        sort($tables);

        $this->assertSame(self::STRUCTURAL_COMPANY_TABLES, array_values(array_unique($tables)));

    }

    public function test_landlord_registry_does_not_duplicate_the_tenant_company_id(): void {

        foreach($this->phpFiles(database_path("migrations/landlord")) as $file) {

            $this->assertStringNotContainsString(
                "\"company_id\"",
                file_get_contents($file->getPathname()),
                $file->getPathname()
            );

        }

    }

    public function test_legacy_company_scoping_primitives_do_not_return(): void {

        $legacyPatterns = [
            "BelongsToCompany",
            "UniqueInCompany",
            "->forCompany(",
            "findByIdAndCompany",
            "findByIpAndCompany",
        ];

        foreach($this->phpFiles(app_path()) as $file) {

            $content = file_get_contents($file->getPathname());

            foreach($legacyPatterns as $pattern) {

                $this->assertStringNotContainsString($pattern, $content, $file->getPathname());

            }

        }

    }

    public function test_tenant_commands_do_not_accept_a_company_selector(): void {

        foreach($this->phpFiles(app_path("Console/Commands")) as $file) {

            $this->assertStringNotContainsString(
                "{--company=",
                file_get_contents($file->getPathname()),
                $file->getPathname()
            );

        }

    }

    public function test_operational_service_apis_do_not_accept_a_company_selector(): void {

        foreach($this->phpFiles(app_path("Services")) as $file) {

            $relativePath = $this->relativePath($file->getPathname());

            if(in_array($relativePath, self::STRUCTURAL_COMPANY_SERVICES, true)) {

                continue;

            }

            $content = file_get_contents($file->getPathname());
            preg_match_all(
                "/public\\s+(?:static\\s+)?function\\s+\\w+\\s*\\((.*?)\\)\\s*(?::[^\\{]+)?\\{/s",
                $content,
                $matches
            );

            foreach($matches[1] as $parameters) {

                $this->assertStringNotContainsString(
                    "\$companyId",
                    $parameters,
                    "La API operativa {$relativePath} no debe recibir el selector redundante de empresa."
                );

            }

        }

    }

    public function test_backend_company_id_usage_is_limited_to_structural_files(): void {

        $usage = [];

        foreach($this->phpFiles(app_path()) as $file) {

            $content = file_get_contents($file->getPathname());

            if(str_contains($content, "company_id")) {

                $usage[$this->relativePath($file->getPathname())] = substr_count($content, "company_id");

            }

        }

        ksort($usage);

        $this->assertSame(self::BACKEND_COMPANY_ID_USAGE, $usage);

    }

    public function test_frontend_does_not_receive_a_company_selector(): void {

        foreach($this->filesWithExtensions(resource_path(), ["js", "php", "ts", "vue"]) as $file) {

            $content = file_get_contents($file->getPathname());

            $this->assertStringNotContainsString("company_id", $content, $file->getPathname());
            $this->assertStringNotContainsString("companyId", $content, $file->getPathname());

        }

    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFiles(string $path): iterable {

        yield from $this->filesWithExtensions($path, ["php"]);

    }

    /**
     * @param  array<string>  $extensions
     * @return iterable<SplFileInfo>
     */
    private function filesWithExtensions(string $path, array $extensions): iterable {

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach($files as $file) {

            if($file instanceof SplFileInfo
                && $file->isFile()
                && in_array(strtolower($file->getExtension()), $extensions, true)) {

                yield $file;

            }

        }

    }

    private function relativePath(string $path): string {

        $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, "", $path);

        return str_replace(DIRECTORY_SEPARATOR, "/", $relativePath);

    }
}
