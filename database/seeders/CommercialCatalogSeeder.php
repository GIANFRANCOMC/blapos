<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\System\Catalogs\Item;
use App\Models\System\General\MeasurementUnit;
use App\Models\System\Warehouses\Warehouse;
use Database\Seeders\Support\CsvSeedDataset;
use Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class CommercialCatalogSeeder extends Seeder {

    public const PRODUCT_COUNT = 10000;

    public const SERVICE_COUNT = 10000;

    public const SUBSCRIPTION_COUNT = 100;

    public const BRAND_COUNT = 100;

    public const CATEGORY_COUNT = 100;

    private const BATCH_SIZE = 250;

    private const BRAND_COLUMNS = ["internal_code", "name", "description", "website_url", "status"];

    private const CATEGORY_COLUMNS = ["internal_code", "name", "description", "segment", "status"];

    private const CATEGORY_SEGMENTS = [
        "minería", "botica", "farmacia", "ferretería", "panadería", "gimnasio", "abarrotes",
    ];

    private const COLUMNS = [
        "brand_id", "internal_code", "barcode", "name", "description", "price",
        "price_includes_tax", "igv_exempt", "min_price", "max_price", "currency_id",
        "measurement_unit_id", "type", "duration_type", "duration_value",
        "estimated_duration_minutes", "capacity_control_enabled", "capacity_limit",
        "capacity_used", "expires_at", "commission_rate", "commission_type",
        "commission_value", "attendance_limit_per_day", "benefits", "restrictions",
        "see_my_web", "see_my_web_price", "status", "created_at", "created_by",
        "updated_at", "updated_by",
    ];

    private int $userId;

    private int $currencyId;

    private int $measurementUnitId;

    public function run(?int $productLimit = null, ?int $serviceLimit = null, ?int $subscriptionLimit = null): void {

        $limits = [
            Item::TYPE_PRODUCT => $productLimit ?? self::PRODUCT_COUNT,
            Item::TYPE_SERVICE => $serviceLimit ?? self::SERVICE_COUNT,
            Item::TYPE_SUBSCRIPTION => $subscriptionLimit ?? self::SUBSCRIPTION_COUNT,
        ];

        if(min($limits) < 0) {

            throw new InvalidArgumentException("Las cantidades del catálogo no pueden ser negativas.");

        }

        DB::transaction(function() use ($limits): void {

            $companyId = (int) DB::table("companies")->orderBy("id")->value("id");
            $this->userId = (int) DB::table("users")->where("status", "active")->orderBy("id")->value("id");
            $this->currencyId = (int) DB::table("currencies")->where("code", "PEN")->value("id");
            $this->measurementUnitId = MeasurementUnit::defaultId();

            if(!$companyId || !$this->userId || !$this->currencyId || !$this->measurementUnitId) {

                throw new RuntimeException("Aprovisiona la empresa, PEN, UNIT y el administrador antes del catálogo inicial.");

            }

            $brandIds = $this->seedBrands();
            $categoryIdsBySegment = $this->seedCategories();

            $warehouses = Warehouse::query()
                ->where("status", "active")
                ->whereHas("branch", fn($query) => $query->where("company_id", $companyId))
                ->pluck("id")
                ->all();
            $batch = [];

            foreach($this->sourceItems($limits) as $item) {

                if($item["type"] === Item::TYPE_PRODUCT) {

                    $item["brand_id"] = $brandIds[$this->demoIndex("brand:".$item["internal_code"], count($brandIds))];

                }

                $batch[] = $item;

                if(count($batch) === self::BATCH_SIZE) {

                    $this->seedBatch($batch, $warehouses, $categoryIdsBySegment);
                    $batch = [];

                }

            }

            if($batch) {

                $this->seedBatch($batch, $warehouses, $categoryIdsBySegment);

            }

        });

    }

    private function seedBrands(): array {

        $rows = [];
        $codes = [];
        $names = [];
        $now = now()->toDateTimeString();

        foreach(CsvSeedDataset::rows(__DIR__."/data/06_marcas_100.csv", self::BRAND_COLUMNS, self::BRAND_COUNT) as $number => $row) {

            $code = trim($row["internal_code"]);
            $name = trim($row["name"]);
            $description = trim($row["description"]);
            $website = trim($row["website_url"]);

            if(
                !preg_match('/^[A-Za-z0-9._-]{1,50}$/', $code)
                || $name === "" || mb_strlen($name) > 100
                || mb_strlen($description) > 250
                || ($website !== "" && (mb_strlen($website) > 500 || !filter_var($website, FILTER_VALIDATE_URL)))
                || $row["status"] !== "active"
                || isset($codes[$code]) || isset($names[$name])
            ) {

                throw new RuntimeException("Marca de demostración inválida en la fila ".($number + 1).".");

            }

            $codes[$code] = true;
            $names[$name] = true;
            $rows[] = [
                "internal_code" => $code,
                "name" => $name,
                "description" => $description === "" ? null : $description,
                "website_url" => $website === "" ? null : $website,
                "status" => "active",
                "created_at" => $now,
                "created_by" => $this->userId,
                "updated_at" => $now,
                "updated_by" => $this->userId,
            ];

        }

        DB::table("brands")->insertOrIgnore($rows);

        $ids = DB::table("brands")->whereIn("internal_code", array_keys($codes))->pluck("id", "internal_code");

        if($ids->count() !== self::BRAND_COUNT) {

            throw new RuntimeException("No se pudieron recuperar las 100 marcas de demostración.");

        }

        return array_map(fn(string $code) => (int) $ids[$code], array_keys($codes));

    }

    private function seedCategories(): array {

        $rows = [];
        $codes = [];
        $names = [];
        $codesBySegment = array_fill_keys(self::CATEGORY_SEGMENTS, []);
        $now = now()->toDateTimeString();

        foreach(CsvSeedDataset::rows(__DIR__."/data/07_categorias_100.csv", self::CATEGORY_COLUMNS, self::CATEGORY_COUNT) as $number => $row) {

            $code = trim($row["internal_code"]);
            $name = trim($row["name"]);
            $description = trim($row["description"]);
            $segment = trim($row["segment"]);

            if(
                !preg_match('/^[A-Za-z0-9._-]{1,50}$/', $code)
                || $name === "" || mb_strlen($name) > 50
                || mb_strlen($description) > 100
                || !in_array($segment, self::CATEGORY_SEGMENTS, true)
                || $row["status"] !== "active"
                || isset($codes[$code]) || isset($names[$name])
            ) {

                throw new RuntimeException("Categoría de demostración inválida en la fila ".($number + 1).".");

            }

            $codes[$code] = true;
            $names[$name] = true;
            $codesBySegment[$segment][] = $code;
            $rows[] = [
                "internal_code" => $code,
                "name" => $name,
                "description" => $description === "" ? null : $description,
                "status" => "active",
                "created_at" => $now,
                "created_by" => $this->userId,
                "updated_at" => $now,
                "updated_by" => $this->userId,
            ];

        }

        if(in_array([], $codesBySegment, true)) {

            throw new RuntimeException("Cada segmento debe tener categorías de demostración.");

        }

        DB::table("categories")->insertOrIgnore($rows);

        $ids = DB::table("categories")->whereIn("internal_code", array_keys($codes))->pluck("id", "internal_code");

        if($ids->count() !== self::CATEGORY_COUNT) {

            throw new RuntimeException("No se pudieron recuperar las 100 categorías de demostración.");

        }

        return array_map(
            fn(array $segmentCodes) => array_map(fn(string $code) => (int) $ids[$code], $segmentCodes),
            $codesBySegment
        );

    }

    private function sourceItems(array $limits): Generator {

        $datasets = [
            Item::TYPE_PRODUCT => ["01_productos_10000.csv", self::PRODUCT_COUNT],
            Item::TYPE_SERVICE => ["02_servicios_10000.csv", self::SERVICE_COUNT],
            Item::TYPE_SUBSCRIPTION => ["03_membresias_100.csv", self::SUBSCRIPTION_COUNT],
        ];
        $codes = [];
        $barcodes = [];

        foreach($datasets as $type => [$filename, $expectedCount]) {

            foreach(CsvSeedDataset::rows(__DIR__."/data/".$filename, self::COLUMNS, $expectedCount) as $number => $row) {

                $item = $this->normalizeItem($row, $type, $filename, $number);
                $key = $type.":".$item["internal_code"];

                if(isset($codes[$key]) || ($item["barcode"] !== null && isset($barcodes[$item["barcode"]]))) {

                    throw new RuntimeException("Código duplicado en {$filename}, fila ".($number + 1).".");

                }

                $codes[$key] = true;

                if($item["barcode"] !== null) {

                    $barcodes[$item["barcode"]] = true;

                }

                if($number <= $limits[$type]) {

                    yield $item;

                }

            }

            if($limits[$type] > $expectedCount) {

                throw new InvalidArgumentException("El límite de {$type} supera el tamaño del dataset.");

            }

        }

    }

    private function normalizeItem(array $row, string $type, string $filename, int $number): array {

        $label = "{$filename}, fila ".($number + 1);
        $code = trim($row["internal_code"]);
        $name = trim($row["name"]);
        $description = trim($row["description"]);
        $barcode = trim($row["barcode"]);
        $price = trim($row["price"]);
        $minimum = $this->optionalDecimal($row["min_price"], $label);
        $maximum = $this->optionalDecimal($row["max_price"], $label);

        if(
            $row["type"] !== $type
            || $code === ""
            || mb_strlen($code) > 50
            || $name === ""
            || mb_strlen($name) > 100
            || mb_strlen($description) > 100
            || !preg_match('/^\d{1,12}(?:\.\d{1,3})?$/', $price)
            || (float) $price <= 0
            || ($minimum !== null && $maximum !== null && (float) $minimum > (float) $maximum)
            || !in_array($row["status"], ["active", "inactive"], true)
            || trim($row["currency_id"]) !== "1"
            || trim($row["brand_id"]) !== ""
            || trim($row["measurement_unit_id"]) !== ""
        ) {

            throw new RuntimeException("Datos comerciales inválidos en {$label}.");

        }

        if($minimum === null && $maximum === null) {

            // Los CSV conservan el precio de venta; el catálogo inicial cubre los cuatro casos opcionales.
            $minimum = $number % 4 === 2 || $number % 4 === 0
                ? number_format(max(0.001, round((float) $price * 0.85, 3)), 3, ".", "")
                : null;
            $maximum = $number % 4 === 3 || $number % 4 === 0
                ? number_format(round((float) $price * 1.15, 3), 3, ".", "")
                : null;

        }

        if($type === Item::TYPE_PRODUCT) {

            if(!preg_match('/^\d{13}$/', $barcode) || !$this->validBarcode($barcode)) {

                throw new RuntimeException("EAN-13 inválido en {$label}.");

            }

        } elseif($barcode !== "") {

            throw new RuntimeException("Solo los productos pueden tener código de barras: {$label}.");

        }

        $durationType = trim($row["duration_type"]);
        $durationValue = $this->optionalInteger($row["duration_value"], $label);
        $estimatedMinutes = $this->optionalInteger($row["estimated_duration_minutes"], $label);
        $capacityEnabled = $this->boolean($row["capacity_control_enabled"], $label);
        $capacityLimit = $this->optionalInteger($row["capacity_limit"], $label);
        $capacityUsed = $this->optionalInteger($row["capacity_used"], $label) ?? 0;
        $attendanceLimit = $this->optionalInteger($row["attendance_limit_per_day"], $label);

        if(
            ($type === Item::TYPE_SUBSCRIPTION && (!in_array($durationType, ["hour", "day", "today", "month", "year"], true) || $durationValue < 1))
            || ($type !== Item::TYPE_SUBSCRIPTION && ($durationType !== "" || $durationValue !== null || $attendanceLimit !== null))
            || ($type === Item::TYPE_SERVICE && ($estimatedMinutes === null || $estimatedMinutes < 1 || $estimatedMinutes > 10080))
            || ($type !== Item::TYPE_SERVICE && $estimatedMinutes !== null)
            || ($type === Item::TYPE_PRODUCT && ($capacityEnabled || $capacityLimit !== null || $capacityUsed !== 0))
            || ($capacityEnabled && ($capacityLimit === null || $capacityLimit < 1 || $capacityUsed > $capacityLimit))
            || (!$capacityEnabled && $capacityLimit !== null)
            || ($attendanceLimit !== null && ($attendanceLimit < 1 || $attendanceLimit > 1000))
        ) {

            throw new RuntimeException("Los campos del tipo {$type} no son coherentes en {$label}.");

        }

        $commissionType = trim($row["commission_type"]);
        $commissionValue = $this->optionalDecimal($row["commission_value"], $label) ?? "0";
        $commissionRate = $this->optionalDecimal($row["commission_rate"], $label);

        if(
            !in_array($commissionType, ["none", "percentage", "fixed"], true)
            || ($commissionType === "none" && ((float) $commissionValue !== 0.0 || $commissionRate !== null))
            || ($commissionType !== "none" && (float) $commissionValue <= 0)
        ) {

            throw new RuntimeException("Comisión inválida en {$label}.");

        }

        $expiresAt = trim($row["expires_at"]);

        if($expiresAt !== "" && strtotime($expiresAt) === false) {

            throw new RuntimeException("Fecha de disponibilidad inválida en {$label}.");

        }

        return [
            "brand_id" => null,
            "internal_code" => $code,
            "barcode" => $type === Item::TYPE_PRODUCT ? $barcode : null,
            "name" => $name,
            "description" => $description === "" ? null : $description,
            "price" => $price,
            "price_includes_tax" => $this->boolean($row["price_includes_tax"], $label),
            "igv_exempt" => $this->boolean($row["igv_exempt"], $label),
            "min_price" => $minimum,
            "max_price" => $maximum,
            "currency_id" => $this->currencyId,
            "measurement_unit_id" => $type === Item::TYPE_PRODUCT ? $this->measurementUnitId : null,
            "type" => $type,
            "duration_type" => $durationType === "" ? null : $durationType,
            "duration_value" => $durationValue,
            "estimated_duration_minutes" => $estimatedMinutes,
            "capacity_control_enabled" => $capacityEnabled,
            "capacity_limit" => $capacityLimit,
            "capacity_used" => $capacityUsed,
            "expires_at" => $expiresAt === "" ? null : $expiresAt,
            "commission_rate" => $commissionRate,
            "commission_type" => $commissionType,
            "commission_value" => $commissionValue,
            "attendance_limit_per_day" => $attendanceLimit,
            "benefits" => $this->jsonList($row["benefits"], $label),
            "restrictions" => $this->jsonList($row["restrictions"], $label),
            "see_my_web" => $this->boolean($row["see_my_web"], $label),
            "see_my_web_price" => $this->boolean($row["see_my_web_price"], $label),
            "status" => $row["status"],
        ];

    }

    private function seedBatch(array $batch, array $warehouses, array $categoryIdsBySegment): void {

        $this->assertNoBarcodeConflicts($batch);

        $now = now()->toDateTimeString();
        $rows = array_map(fn(array $item) => $item + [
            "created_at" => $now,
            "created_by" => $this->userId,
            "updated_at" => $now,
            "updated_by" => $this->userId,
        ], $batch);

        DB::table("items")->insertOrIgnore($rows);

        $this->seedCategoryLinks($batch, $categoryIdsBySegment, $now);

        if(!$warehouses) {

            return;

        }

        $productCodes = array_column(
            array_values(array_filter($batch, fn(array $item) => $item["type"] === Item::TYPE_PRODUCT)),
            "internal_code"
        );

        if(!$productCodes) {

            return;

        }

        $products = DB::table("items")
            ->where("type", Item::TYPE_PRODUCT)
            ->whereIn("internal_code", $productCodes)
            ->get(["id", "internal_code", "price"]);
        $existing = DB::table("warehouse_items")
            ->whereIn("item_id", $products->pluck("id"))
            ->whereIn("warehouse_id", $warehouses)
            ->get(["warehouse_id", "item_id"])
            ->mapWithKeys(fn($row) => [$row->warehouse_id.":".$row->item_id => true]);
        $stockRows = [];
        $movementRows = [];
        $alertKeys = [];

        foreach($products as $product) {

            foreach($warehouses as $warehouseId) {

                $key = $warehouseId.":".$product->id;

                if(isset($existing[$key])) {

                    continue;

                }

                $stock = $this->initialStock($product->internal_code, (int) $warehouseId, (float) $product->price);
                $stockRows[] = [
                    "warehouse_id" => $warehouseId,
                    "item_id" => $product->id,
                    "quantity" => $stock["quantity"],
                    "minimum_stock" => $stock["minimum_stock"],
                    "average_cost" => $stock["average_cost"],
                    "inventory_value" => $stock["inventory_value"],
                    "status" => "active",
                    "created_at" => $now,
                    "created_by" => $this->userId,
                    "updated_at" => $now,
                    "updated_by" => $this->userId,
                ];

                if($stock["quantity"] > 0) {

                    $movementRows[] = [
                        "warehouse_id" => $warehouseId,
                        "item_id" => $product->id,
                        "user_id" => $this->userId,
                        "movement_type" => "entry",
                        "origin_type" => "commercial_catalog_seeder",
                        "origin_id" => $product->id,
                        "quantity_before" => 0,
                        "quantity_change" => $stock["quantity"],
                        "quantity_after" => $stock["quantity"],
                        "unit_cost" => $stock["average_cost"],
                        "value_before" => 0,
                        "value_change" => $stock["inventory_value"],
                        "value_after" => $stock["inventory_value"],
                        "reason" => "Stock inicial de demostración.",
                        "metadata" => json_encode(["source" => "CommercialCatalogSeeder"], JSON_THROW_ON_ERROR),
                        "created_at" => $now,
                    ];

                }

                if($stock["minimum_stock"] > 0 && $stock["quantity"] <= $stock["minimum_stock"]) {

                    $alertKeys[$key] = true;

                }

            }

        }

        foreach(array_chunk($stockRows, self::BATCH_SIZE) as $chunk) {

            DB::table("warehouse_items")->insert($chunk);

        }

        foreach(array_chunk($movementRows, self::BATCH_SIZE) as $chunk) {

            DB::table("inventory_movements")->insert($chunk);

        }

        if($alertKeys) {

            $alertRows = DB::table("warehouse_items")
                ->whereIn("item_id", $products->pluck("id"))
                ->whereIn("warehouse_id", $warehouses)
                ->get(["id", "warehouse_id", "item_id", "quantity", "minimum_stock"])
                ->filter(fn($row) => isset($alertKeys[$row->warehouse_id.":".$row->item_id]))
                ->map(fn($row) => [
                    "warehouse_item_id" => $row->id,
                    "quantity" => $row->quantity,
                    "minimum_stock" => $row->minimum_stock,
                    "status" => "open",
                    "detected_at" => $now,
                    "created_at" => $now,
                    "updated_at" => $now,
                ])
                ->all();

            foreach(array_chunk($alertRows, self::BATCH_SIZE) as $chunk) {

                DB::table("inventory_stock_alerts")->insert($chunk);

            }

        }

    }

    private function seedCategoryLinks(array $batch, array $categoryIdsBySegment, string $now): void {

        $storedIds = DB::table("items")
            ->whereIn("internal_code", array_column($batch, "internal_code"))
            ->get(["id", "type", "internal_code"])
            ->mapWithKeys(fn($item) => [$item->type.":".$item->internal_code => (int) $item->id]);
        $rows = [];

        foreach($batch as $item) {

            $itemId = $storedIds[$item["type"].":".$item["internal_code"]] ?? null;
            $description = mb_strtolower($item["description"] ?? "");
            $segment = null;

            foreach(self::CATEGORY_SEGMENTS as $candidate) {

                if(str_contains($description, $candidate)) {

                    $segment = $candidate;
                    break;

                }

            }

            if($itemId === null || $segment === null) {

                throw new RuntimeException("No se pudo clasificar {$item['internal_code']} en el catálogo inicial.");

            }

            $categoryIds = $categoryIdsBySegment[$segment];
            $rows[] = [
                "category_id" => $categoryIds[$this->demoIndex("category:".$item["internal_code"], count($categoryIds))],
                "item_id" => $itemId,
                "status" => "active",
                "created_at" => $now,
                "created_by" => $this->userId,
                "updated_at" => $now,
                "updated_by" => $this->userId,
            ];

        }

        DB::table("category_items")->insertOrIgnore($rows);

    }

    private function demoIndex(string $key, int $count): int {

        return hexdec(substr(hash("sha256", $key), 0, 8)) % $count;

    }

    private function initialStock(string $code, int $warehouseId, float $price): array {

        $seed = hexdec(substr(hash("sha256", $code.":".$warehouseId), 0, 8));
        $scenario = $seed % 5;
        $quantity = match($scenario) {
            0, 1 => 0,
            2 => 1 + ($seed % 4),
            3 => 18 + ($seed % 45),
            default => 10 + ($seed % 28),
        };
        $minimum = match($scenario) {
            0, 2 => 5 + ($seed % 8),
            3 => 3 + ($seed % 10),
            default => 0,
        };
        $cost = $quantity > 0 ? round($price * (0.55 + (($seed >> 5) % 20) / 100), 3) : 0;

        return [
            "quantity" => $quantity,
            "minimum_stock" => $minimum,
            "average_cost" => $cost,
            "inventory_value" => round($quantity * $cost, 3),
        ];

    }

    private function assertNoBarcodeConflicts(array $batch): void {

        $barcodes = [];

        foreach($batch as $item) {

            if($item["barcode"] !== null) {

                $barcodes[$item["barcode"]] = $item["internal_code"];

            }

        }

        if(!$barcodes) {

            return;

        }

        $existing = DB::table("items")
            ->whereIn("barcode", array_keys($barcodes))
            ->get(["barcode", "internal_code", "type"]);

        foreach($existing as $item) {

            if($item->type !== Item::TYPE_PRODUCT || $item->internal_code !== $barcodes[$item->barcode]) {

                throw new RuntimeException("Un código de barras del CSV ya pertenece a otro producto.");

            }

        }

    }

    private function optionalDecimal(string $value, string $label): ?string {

        $value = trim($value);

        if($value === "") {

            return null;

        }

        if(!preg_match('/^\d{1,12}(?:\.\d{1,3})?$/', $value)) {

            throw new RuntimeException("Número decimal inválido en {$label}.");

        }

        return $value;

    }

    private function optionalInteger(string $value, string $label): ?int {

        $value = trim($value);

        if($value === "") {

            return null;

        }

        if(!preg_match('/^\d{1,10}$/', $value)) {

            throw new RuntimeException("Número entero inválido en {$label}.");

        }

        return (int) $value;

    }

    private function boolean(string $value, string $label): bool {

        if(!in_array(trim($value), ["0", "1"], true)) {

            throw new RuntimeException("Valor booleano inválido en {$label}.");

        }

        return trim($value) === "1";

    }

    private function jsonList(string $value, string $label): ?string {

        if(trim($value) === "") {

            return null;

        }

        $items = json_decode($value, true);

        if(
            !is_array($items)
            || !array_is_list($items)
            || count($items) > 50
            || count(array_filter($items, fn($item) => !is_string($item) || mb_strlen($item) > 100)) > 0
        ) {

            throw new RuntimeException("Lista de beneficios o restricciones inválida en {$label}.");

        }

        return json_encode($items, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    }

    private function validBarcode(string $barcode): bool {

        $sum = 0;

        for($index = 0; $index < 12; $index++) {

            $sum += (int) $barcode[$index] * ($index % 2 === 0 ? 1 : 3);

        }

        return (int) $barcode[12] === (10 - ($sum % 10)) % 10;

    }

}
