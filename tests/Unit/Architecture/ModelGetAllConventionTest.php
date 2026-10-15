<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\{File};
use Tests\{TestCase};

class ModelGetAllConventionTest extends TestCase {

    public function test_models_do_not_define_ambiguous_get_all_methods(): void {

        $violations = collect(File::allFiles(app_path("Models")))
            ->filter(function($file) {

                return preg_match(
                    "/function\s+getAll\s*\(/",
                    File::get($file->getPathname())
                ) === 1;

            })
            ->map(fn($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame(
            [],
            $violations,
            "Use an explicit reference-data service method instead of Model::getAll(type, companyId)."
        );

    }

    public function test_application_services_do_not_call_model_get_all_methods(): void {

        $directories = [
            app_path("Http/Controllers"),
            app_path("Services"),
        ];

        $violations = collect($directories)
            ->flatMap(fn(string $directory) => File::allFiles($directory))
            ->filter(function($file) {

                return str_contains(File::get($file->getPathname()), "::getAll(");

            })
            ->map(fn($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame(
            [],
            $violations,
            "Consumers must use explicit reference-data service methods."
        );

    }

    public function test_all_module_config_services_use_the_shared_cache_contract(): void {

        $violations = collect(File::allFiles(app_path("Services/System")))
            ->filter(fn($file) => str_ends_with($file->getFilename(), "ConfigService.php"))
            ->reject(fn($file) => $file->getFilename() === "BaseConfigService.php")
            ->filter(function($file) {

                return !str_contains(
                    File::get($file->getPathname()),
                    "extends BaseConfigService"
                );

            })
            ->map(fn($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame(
            [],
            $violations,
            "Every module ConfigService must extend BaseConfigService."
        );

    }

    public function test_new_company_catalog_requests_use_the_shared_request_contract(): void {

        $requestFiles = [
            app_path("Http/Requests/System/Catalogs/Brands/BrandRequest.php"),
            app_path("Http/Requests/System/Catalogs/Products/ProductRequest.php"),
        ];

        foreach($requestFiles as $requestFile) {

            $this->assertStringContainsString(
                "extends CompanyFormRequest",
                File::get($requestFile),
                "Company-owned catalog requests must extend CompanyFormRequest."
            );

        }

    }

}
