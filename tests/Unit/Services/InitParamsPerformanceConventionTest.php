<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\{File};
use Tests\{TestCase};

final class InitParamsPerformanceConventionTest extends TestCase {
    public function test_new_unbounded_catalogs_are_not_added_to_initial_configuration(): void {

        $unboundedMethods = [
            "activeCustomers",
            "assets",
            "customers",
            "saleItems",
            "subscriptionItems",
            "users",
        ];

        $currentUsages = [];

        foreach(File::allFiles(app_path("Services/System")) as $file) {

            if(!str_ends_with($file->getFilename(), "ConfigService.php")) {

                continue;

            }

            $contents = $file->getContents();

            foreach($unboundedMethods as $method) {

                if(str_contains($contents, "->{$method}(")) {

                    $path = str_replace("\\", "/", $file->getRelativePathname());
                    $currentUsages[] = "{$path}:{$method}";

                }

            }

        }

        sort($currentUsages);

        $knownLegacyUsages = [
            "Assets/AssetManagementConfigService.php:assets",
            "Catalogs/Recipes/RecipeConfigService.php:saleItems",
            "Customers/Tracking/TrackingAttendanceConfigService.php:activeCustomers",
            "Customers/Tracking/TrackingCustomerConfigService.php:customers",
            "Customers/Tracking/TrackingSubscriptionConfigService.php:activeCustomers",
            "Customers/Tracking/TrackingSubscriptionConfigService.php:subscriptionItems",
            "Sales/SaleConfigService.php:activeCustomers",
            "Sales/SaleConfigService.php:saleItems",
            "Sales/SaleConfigService.php:users",
        ];

        $this->assertSame($knownLegacyUsages, $currentUsages);

    }
}
