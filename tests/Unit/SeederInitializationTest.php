<?php

declare(strict_types=1);

namespace Tests\Unit;

use Database\Seeders\SystemNavigationSeeder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SeederInitializationTest extends TestCase {

    public function test_home_is_disabled_in_the_initial_navigation_catalog(): void {

        $routes = (new ReflectionClass(SystemNavigationSeeder::class))
            ->getReflectionConstant("DISABLED_BY_DEFAULT_ROUTES")
            ->getValue();

        $this->assertContains("home.index", $routes);

    }

    public function test_sales_purchases_and_cash_are_disabled_in_the_initial_navigation_catalog(): void {

        $worlds = (new ReflectionClass(SystemNavigationSeeder::class))
            ->getReflectionConstant("DISABLED_BY_DEFAULT_WORLD_SLUGS")
            ->getValue();

        $this->assertSame(["sc_sales", "sc_purchases", "sc_cash"], $worlds);

    }

    public function test_seeders_do_not_update_existing_records(): void {

        foreach(glob(dirname(__DIR__, 2)."/database/seeders/*.php") as $path) {

            $source = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression(
                '/->(?:update|upsert|updateOrInsert|firstOrCreate)\s*\(/',
                $source,
                basename($path)." debe insertar sin modificar registros existentes."
            );

        }

    }

}
