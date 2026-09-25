<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\{TestCase};

final class TenantCompanyBoundaryTest extends TestCase {
    private const STRUCTURAL_COMPANY_TABLES = [
        "branches",
        "companies_sub_sections",
        "company_settings",
        "company_socials_media",
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
}
