<?php

declare(strict_types=1);

namespace Tests\Unit;

use Database\Seeders\CommercialCatalogSeeder;
use Database\Seeders\Support\CsvSeedDataset;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

final class CommercialCatalogSourceTest extends TestCase {

    public function test_reference_csvs_have_unique_valid_records_for_each_segment(): void {

        $base = dirname(__DIR__, 2)."/database/seeders/data/";
        $brands = iterator_to_array(CsvSeedDataset::rows(
            $base."06_marcas_100.csv",
            ["internal_code", "name", "description", "website_url", "status"],
            CommercialCatalogSeeder::BRAND_COUNT
        ));
        $categories = iterator_to_array(CsvSeedDataset::rows(
            $base."07_categorias_100.csv",
            ["internal_code", "name", "description", "segment", "status"],
            CommercialCatalogSeeder::CATEGORY_COUNT
        ));

        $this->assertCount(100, array_unique(array_column($brands, "internal_code")));
        $this->assertCount(100, array_unique(array_column($brands, "name")));
        $this->assertCount(100, array_unique(array_column($categories, "internal_code")));
        $this->assertCount(100, array_unique(array_column($categories, "name")));
        $this->assertCount(7, array_unique(array_column($categories, "segment")));

        foreach($brands as $brand) {

            $this->assertLessThanOrEqual(100, mb_strlen($brand["name"]));
            $this->assertSame("active", $brand["status"]);

        }

        foreach($categories as $category) {

            $this->assertLessThanOrEqual(50, mb_strlen($category["name"]));
            $this->assertLessThanOrEqual(100, mb_strlen($category["description"]));
            $this->assertSame("active", $category["status"]);

        }

    }

    public function test_latest_csv_datasets_match_each_item_type_and_platform_rules(): void {

        $seeder = new CommercialCatalogSeeder();
        (new ReflectionProperty($seeder, "currencyId"))->setValue($seeder, 1);
        (new ReflectionProperty($seeder, "measurementUnitId"))->setValue($seeder, 1);
        $source = new ReflectionMethod($seeder, "sourceItems");
        $items = $source->invoke($seeder, [
            "product" => CommercialCatalogSeeder::PRODUCT_COUNT,
            "service" => CommercialCatalogSeeder::SERVICE_COUNT,
            "subscription" => CommercialCatalogSeeder::SUBSCRIPTION_COUNT,
        ]);
        $counts = ["product" => 0, "service" => 0, "subscription" => 0];
        $barcodes = [];
        $first = [];
        $last = [];
        $priceCases = ["product" => [], "service" => [], "subscription" => []];

        foreach($items as $item) {

            $type = $item["type"];
            $counts[$type]++;
            $first[$type] ??= $item;
            $last[$type] = $item;
            $case = ($item["min_price"] === null ? "without_min" : "with_min")
                ."_".($item["max_price"] === null ? "without_max" : "with_max");
            $priceCases[$type][$case] = ($priceCases[$type][$case] ?? 0) + 1;

            $this->assertLessThanOrEqual(100, mb_strlen($item["name"]));
            $this->assertGreaterThan(0, (float) $item["price"]);

            if($item["min_price"] !== null) {

                $this->assertLessThanOrEqual((float) $item["price"], (float) $item["min_price"]);

            }

            if($item["max_price"] !== null) {

                $this->assertGreaterThanOrEqual((float) $item["price"], (float) $item["max_price"]);

            }

            if($type === "product") {

                $barcodes[$item["barcode"]] = true;
                $this->assertSame(1, $item["measurement_unit_id"]);
                $this->assertFalse($item["capacity_control_enabled"]);

            } else {

                $this->assertNull($item["barcode"]);
                $this->assertNull($item["measurement_unit_id"]);

            }

        }

        $this->assertSame([
            "product" => 10000,
            "service" => 10000,
            "subscription" => 100,
        ], $counts);
        $this->assertCount(10000, $barcodes);

        foreach($priceCases as $cases) {

            $this->assertCount(4, $cases);

        }
        $this->assertSame("PROD-000001", $first["product"]["internal_code"]);
        $this->assertSame("2000000000015", $first["product"]["barcode"]);
        $this->assertSame("Casco de seguridad Andes industrial S - Ref. 00001", $first["product"]["name"]);
        $this->assertSame("952.289", $first["product"]["price"]);
        $this->assertSame("PROD-010000", $last["product"]["internal_code"]);
        $this->assertSame("SERV-000001", $first["service"]["internal_code"]);
        $this->assertSame(60, $first["service"]["estimated_duration_minutes"]);
        $this->assertSame("SERV-010000", $last["service"]["internal_code"]);
        $this->assertSame("MEMB-00001", $first["subscription"]["internal_code"]);
        $this->assertSame("day", $first["subscription"]["duration_type"]);
        $this->assertSame("MEMB-00100", $last["subscription"]["internal_code"]);
        $this->assertSame(
            '["Acceso al plan","Descuento en servicios"]',
            $first["subscription"]["benefits"]
        );

    }

    public function test_service_with_barcode_is_rejected(): void {

        $seeder = new CommercialCatalogSeeder();
        (new ReflectionProperty($seeder, "currencyId"))->setValue($seeder, 1);
        (new ReflectionProperty($seeder, "measurementUnitId"))->setValue($seeder, 1);
        $reflection = new \ReflectionClass($seeder);
        $headers = $reflection->getReflectionConstant("COLUMNS")->getValue();
        $path = dirname(__DIR__, 2)."/database/seeders/data/02_servicios_10000.csv";
        $row = CsvSeedDataset::rows($path, $headers, 10000)->current();
        $row["barcode"] = "2000000000015";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Solo los productos pueden tener código de barras");

        (new ReflectionMethod($seeder, "normalizeItem"))
            ->invoke($seeder, $row, "service", "servicios.csv", 1);

    }

}
