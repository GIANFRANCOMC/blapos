<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Brand, Category, Item};
use App\Models\System\General\MeasurementUnit;
use App\Models\System\Warehouses\Warehouse;
use Illuminate\Database\{Seeder};
use Illuminate\Support\Facades\{DB};
use InvalidArgumentException;
use RuntimeException;

final class CommercialCatalogSeeder extends Seeder {

    public const PRODUCT_COUNT = 3000;

    public const SERVICE_COUNT = 1000;

    public const SUBSCRIPTION_COUNT = 100;

    private const BATCH_SIZE = 40;

    private int $companyId;

    private int $userId;

    private int $currencyId;

    public function run(?int $productLimit = null, ?int $serviceLimit = null, ?int $subscriptionLimit = null): void {

        $productLimit ??= self::PRODUCT_COUNT;
        $serviceLimit ??= self::SERVICE_COUNT;
        $subscriptionLimit ??= self::SUBSCRIPTION_COUNT;

        if(min($productLimit, $serviceLimit, $subscriptionLimit) < 0) {

            throw new InvalidArgumentException("Las cantidades del catálogo no pueden ser negativas.");

        }

        DB::transaction(function() use ($productLimit, $serviceLimit, $subscriptionLimit): void {

            $this->companyId = (int) DB::table("companies")->orderBy("id")->value("id");
            $this->userId = (int) DB::table("users")->orderBy("id")->value("id");
            $this->currencyId = (int) DB::table("currencies")->where("code", "PEN")->value("id");

            if(!$this->companyId || !$this->userId || !$this->currencyId) {

                throw new RuntimeException("Aprovisiona la empresa, su moneda y el administrador antes de cargar el catálogo inicial.");

            }

            $brands = $this->seedBrands();
            $categories = $this->seedCategories();
            $measurementUnitId = MeasurementUnit::defaultId();
            $precision = Utilities::decimalPrecision();
            $warehouses = Warehouse::query()
                ->where("status", "active")
                ->whereHas("branch", fn($query) => $query->where("company_id", $this->companyId))
                ->pluck("id")
                ->all();

            foreach(array_chunk($this->items($brands, $productLimit, $serviceLimit, $subscriptionLimit), self::BATCH_SIZE) as $batch) {

                $this->seedBatch($batch, $categories, $warehouses, $measurementUnitId, $precision);

            }

        });

    }

    private function seedBrands(): array {

        $records = [
            ["internal_code" => "MAR-HOLA", "name" => "Hola", "description" => "Marca demo para bebidas y productos de consumo."],
            ["internal_code" => "MAR-BLAPOS", "name" => "Blapos", "description" => "Marca propia para productos deportivos."],
            ["internal_code" => "MAR-WELL", "name" => "Wellness", "description" => "Marca demo para bienestar y cuidado personal."],
        ];

        return collect($records)
            ->mapWithKeys(function(array $record) {

                $brand = Brand::updateOrCreate(
                    [
                        "internal_code" => $record["internal_code"],
                    ],
                    [
                        "name" => $record["name"],
                        "description" => $record["description"],
                        "status" => "active",
                        "created_by" => $this->userId,
                        "updated_by" => $this->userId,
                    ]
                );

                return [$record["name"] => $brand];

            })
            ->all();

    }

    private function seedCategories(): array {

        $records = [
            ["internal_code" => "CAT-BEB", "name" => "Bebidas", "description" => "Productos líquidos para venta rápida."],
            ["internal_code" => "CAT-SUP", "name" => "Suplementos", "description" => "Proteínas, barras y complementos deportivos."],
            ["internal_code" => "CAT-ACC", "name" => "Accesorios", "description" => "Implementos y artículos de entrenamiento."],
            ["internal_code" => "CAT-SER", "name" => "Servicios", "description" => "Servicios presenciales o personalizados."],
            ["internal_code" => "CAT-MEM", "name" => "Membresias", "description" => "Planes comerciales con vigencia."],
        ];

        return collect($records)
            ->mapWithKeys(function(array $record) {

                $category = Category::updateOrCreate(
                    [
                        "internal_code" => $record["internal_code"],
                    ],
                    [
                        "name" => $record["name"],
                        "description" => $record["description"],
                        "status" => "active",
                        "created_by" => $this->userId,
                        "updated_by" => $this->userId,
                    ]
                );

                return [$record["name"] => $category];

            })
            ->all();

    }

    private function items(
        array $brands,
        ?int $productLimit,
        ?int $serviceLimit,
        ?int $subscriptionLimit
    ): array {

        $products = [
            ["code" => "AGUA625", "name" => "Agua mineral 625 ml", "description" => "Bebida individual para venta en mostrador.", "price" => 2.50, "min" => 2.00, "max" => 3.50, "brand" => "Hola", "categories" => ["Bebidas"], "quantity" => 36, "minimum" => 8, "cost" => 1.20],
            ["code" => "AGUA1L", "name" => "Agua mineral 1 L", "description" => "Botella familiar para entrenamientos prolongados.", "price" => 4.00, "min" => 3.50, "max" => 5.50, "brand" => "Hola", "categories" => ["Bebidas"], "quantity" => 28, "minimum" => 8, "cost" => 2.10],
            ["code" => "ISO500", "name" => "Bebida isotonica 500 ml", "description" => "Reposicion rapida para entrenamientos intensos.", "price" => 5.00, "min" => 4.50, "max" => 6.50, "brand" => "Hola", "categories" => ["Bebidas"], "quantity" => 24, "minimum" => 6, "cost" => 2.70],
            ["code" => "ENERGY", "name" => "Bebida energetica sin azucar", "description" => "Bebida funcional para venta POS.", "price" => 7.50, "min" => 6.50, "max" => 9.00, "brand" => "Hola", "categories" => ["Bebidas"], "quantity" => 18, "minimum" => 5, "cost" => 4.00],
            ["code" => "JUGONAT", "name" => "Jugo natural botella", "description" => "Bebida lista para consumo despues del entrenamiento.", "price" => 6.00, "min" => 5.00, "max" => 8.00, "brand" => "Wellness", "categories" => ["Bebidas"], "quantity" => 20, "minimum" => 6, "cost" => 3.20],
            ["code" => "WHEY1KG", "name" => "Proteina whey 1 kg", "description" => "Suplemento demo de alta rotacion.", "price" => 145.00, "min" => 130.00, "max" => 170.00, "brand" => "Blapos", "categories" => ["Suplementos"], "quantity" => 10, "minimum" => 3, "cost" => 95.00],
            ["code" => "WHEYCH2", "name" => "Proteina whey chocolate 2 lb", "description" => "Proteina sabor chocolate para venta recurrente.", "price" => 118.00, "min" => 105.00, "max" => 145.00, "brand" => "Blapos", "categories" => ["Suplementos"], "quantity" => 12, "minimum" => 4, "cost" => 78.00],
            ["code" => "CREA300", "name" => "Creatina monohidratada 300 g", "description" => "Suplemento basico para fuerza y rendimiento.", "price" => 95.00, "min" => 84.00, "max" => 120.00, "brand" => "Blapos", "categories" => ["Suplementos"], "quantity" => 14, "minimum" => 4, "cost" => 62.00],
            ["code" => "BCAA250", "name" => "BCAA 250 g", "description" => "Aminoacidos para recuperacion deportiva.", "price" => 72.00, "min" => 65.00, "max" => 90.00, "brand" => "Wellness", "categories" => ["Suplementos"], "quantity" => 9, "minimum" => 3, "cost" => 48.00],
            ["code" => "GLUTA300", "name" => "Glutamina 300 g", "description" => "Complemento para recuperacion muscular.", "price" => 68.00, "min" => 60.00, "max" => 86.00, "brand" => "Wellness", "categories" => ["Suplementos"], "quantity" => 8, "minimum" => 3, "cost" => 43.00],
            ["code" => "BARVAI", "name" => "Barra proteica vainilla", "description" => "Snack practico para mostrador.", "price" => 8.00, "min" => 7.00, "max" => 10.00, "brand" => "Blapos", "categories" => ["Suplementos"], "quantity" => 30, "minimum" => 10, "cost" => 4.80],
            ["code" => "BARCHO", "name" => "Barra proteica chocolate", "description" => "Snack proteico sabor chocolate.", "price" => 8.50, "min" => 7.50, "max" => 10.50, "brand" => "Blapos", "categories" => ["Suplementos"], "quantity" => 26, "minimum" => 10, "cost" => 5.10],
            ["code" => "FRUTSEC", "name" => "Snack frutos secos", "description" => "Mezcla ligera para consumo rapido.", "price" => 6.50, "min" => 5.50, "max" => 8.50, "brand" => "Wellness", "categories" => ["Suplementos"], "quantity" => 22, "minimum" => 7, "cost" => 3.60],
            ["code" => "SHAKER", "name" => "Shaker 700 ml", "description" => "Vaso mezclador para suplementos.", "price" => 22.00, "min" => 18.00, "max" => 30.00, "brand" => "Blapos", "categories" => ["Accesorios"], "quantity" => 18, "minimum" => 5, "cost" => 12.00],
            ["code" => "TOALLA", "name" => "Toalla deportiva", "description" => "Accesorio basico para entrenamiento.", "price" => 25.00, "min" => 22.00, "max" => 35.00, "brand" => "Wellness", "categories" => ["Accesorios"], "quantity" => 15, "minimum" => 4, "cost" => 14.00],
            ["code" => "GUANTE", "name" => "Guantes de entrenamiento", "description" => "Guantes para pesas y barras.", "price" => 39.00, "min" => 34.00, "max" => 55.00, "brand" => "Blapos", "categories" => ["Accesorios"], "quantity" => 16, "minimum" => 4, "cost" => 23.00],
            ["code" => "VENMUN", "name" => "Vendas de muneca", "description" => "Soporte para levantamiento y entrenamiento.", "price" => 28.00, "min" => 24.00, "max" => 38.00, "brand" => "Blapos", "categories" => ["Accesorios"], "quantity" => 13, "minimum" => 4, "cost" => 16.00],
            ["code" => "CUERDA", "name" => "Cuerda de salto", "description" => "Accesorio para cardio y calentamiento.", "price" => 32.00, "min" => 28.00, "max" => 45.00, "brand" => "Blapos", "categories" => ["Accesorios"], "quantity" => 11, "minimum" => 3, "cost" => 18.00],
            ["code" => "BANDA-L", "name" => "Banda elastica ligera", "description" => "Banda de resistencia para movilidad.", "price" => 18.00, "min" => 15.00, "max" => 26.00, "brand" => "Wellness", "categories" => ["Accesorios"], "quantity" => 20, "minimum" => 5, "cost" => 9.50],
            ["code" => "BANDA-F", "name" => "Banda elastica fuerte", "description" => "Banda de alta resistencia para entrenamiento.", "price" => 24.00, "min" => 20.00, "max" => 34.00, "brand" => "Wellness", "categories" => ["Accesorios"], "quantity" => 16, "minimum" => 5, "cost" => 13.00],
            ["code" => "BOTDEP", "name" => "Botella deportiva", "description" => "Botella reutilizable para hidratacion.", "price" => 21.00, "min" => 18.00, "max" => 30.00, "brand" => "Hola", "categories" => ["Accesorios", "Bebidas"], "quantity" => 17, "minimum" => 5, "cost" => 11.50],
            ["code" => "CANDADO", "name" => "Candado para locker", "description" => "Candado pequeno para casilleros.", "price" => 15.00, "min" => 12.00, "max" => 22.00, "brand" => "Blapos", "categories" => ["Accesorios"], "quantity" => 12, "minimum" => 4, "cost" => 7.20],
            ["code" => "GRIPBAR", "name" => "Grip para barra", "description" => "Agarre de soporte para entrenamiento con barra.", "price" => 34.00, "min" => 30.00, "max" => 48.00, "brand" => "Blapos", "categories" => ["Accesorios"], "quantity" => 10, "minimum" => 3, "cost" => 20.00],
            ["code" => "RODILLO", "name" => "Rodillo de movilidad", "description" => "Rodillo para descarga y movilidad muscular.", "price" => 46.00, "min" => 40.00, "max" => 65.00, "brand" => "Wellness", "categories" => ["Accesorios"], "quantity" => 8, "minimum" => 3, "cost" => 27.00],
            ["code" => "MATYOGA", "name" => "Colchoneta yoga", "description" => "Colchoneta para estiramiento y clases guiadas.", "price" => 58.00, "min" => 50.00, "max" => 78.00, "brand" => "Wellness", "categories" => ["Accesorios"], "quantity" => 9, "minimum" => 3, "cost" => 34.00],
            ["code" => "MAGLIQ", "name" => "Magnesio liquido", "description" => "Magnesio para agarre en ejercicios de fuerza.", "price" => 31.00, "min" => 27.00, "max" => 42.00, "brand" => "Blapos", "categories" => ["Accesorios"], "quantity" => 14, "minimum" => 4, "cost" => 18.00],
            ["code" => "PRE300", "name" => "Pre entreno 300 g", "description" => "Suplemento previo al entrenamiento.", "price" => 110.00, "min" => 98.00, "max" => 140.00, "brand" => "Blapos", "categories" => ["Suplementos"], "quantity" => 7, "minimum" => 3, "cost" => 72.00],
            ["code" => "OMEGA", "name" => "Omega 3 100 caps", "description" => "Complemento de bienestar general.", "price" => 65.00, "min" => 58.00, "max" => 85.00, "brand" => "Wellness", "categories" => ["Suplementos"], "quantity" => 11, "minimum" => 4, "cost" => 39.00],
            ["code" => "MULTIV", "name" => "Multivitaminico", "description" => "Complemento diario de vitaminas y minerales.", "price" => 59.00, "min" => 52.00, "max" => 78.00, "brand" => "Wellness", "categories" => ["Suplementos"], "quantity" => 10, "minimum" => 4, "cost" => 35.00],
            ["code" => "CAFPRO", "name" => "Cafe proteico", "description" => "Bebida proteica lista para consumo.", "price" => 9.50, "min" => 8.00, "max" => 12.50, "brand" => "Hola", "categories" => ["Bebidas", "Suplementos"], "quantity" => 21, "minimum" => 6, "cost" => 5.40],
        ];

        $services = [
            ["code" => "EVAL", "name" => "Evaluacion fisica", "description" => "Servicio inicial para medir condicion y objetivos.", "price" => 35.00, "min" => 30.00, "max" => 50.00],
            ["code" => "PT60", "name" => "Entrenamiento personal 60 min", "description" => "Sesion personalizada por entrenador.", "price" => 70.00, "min" => 60.00, "max" => 90.00],
            ["code" => "PTDUO", "name" => "Entrenamiento duo", "description" => "Sesion para dos personas con entrenador.", "price" => 110.00, "min" => 95.00, "max" => 140.00],
            ["code" => "RUTINA", "name" => "Rutina personalizada", "description" => "Plan de entrenamiento adaptado al objetivo del cliente.", "price" => 45.00, "min" => 38.00, "max" => 65.00],
            ["code" => "NUTRI", "name" => "Asesoria nutricional", "description" => "Orientacion nutricional basica para clientes.", "price" => 80.00, "min" => 70.00, "max" => 120.00],
            ["code" => "MEDCORP", "name" => "Medicion corporal", "description" => "Control de medidas y seguimiento corporal.", "price" => 25.00, "min" => 20.00, "max" => 40.00],
            ["code" => "FUNC", "name" => "Clase funcional", "description" => "Clase grupal de entrenamiento funcional.", "price" => 18.00, "min" => 15.00, "max" => 28.00],
            ["code" => "SPIN", "name" => "Clase spinning", "description" => "Clase grupal de bicicleta indoor.", "price" => 20.00, "min" => 16.00, "max" => 30.00],
            ["code" => "MOVIL", "name" => "Recuperacion movilidad", "description" => "Sesion guiada de movilidad y descarga.", "price" => 55.00, "min" => 48.00, "max" => 75.00],
            ["code" => "PRUEBA", "name" => "Sesion prueba guiada", "description" => "Sesion de bienvenida para nuevos clientes.", "price" => 15.00, "min" => 10.00, "max" => 25.00],
        ];

        $subscriptions = [
            ["code" => "DIARIA", "name" => "Membresia diaria", "description" => "Acceso por un dia al gimnasio.", "price" => 15.00, "min" => 12.00, "max" => 20.00, "duration_type" => "day", "duration_value" => 1],
            ["code" => "SEMANAL", "name" => "Membresia semanal", "description" => "Acceso por siete dias.", "price" => 45.00, "min" => 38.00, "max" => 60.00, "duration_type" => "day", "duration_value" => 7],
            ["code" => "QUINC", "name" => "Membresia quincenal", "description" => "Acceso por quince dias.", "price" => 75.00, "min" => 65.00, "max" => 95.00, "duration_type" => "day", "duration_value" => 15],
            ["code" => "MENSUAL", "name" => "Membresia mensual", "description" => "Acceso mensual al gimnasio.", "price" => 120.00, "min" => 100.00, "max" => 150.00, "duration_type" => "month", "duration_value" => 1],
            ["code" => "BIMEST", "name" => "Membresia bimestral", "description" => "Acceso por dos meses.", "price" => 225.00, "min" => 200.00, "max" => 280.00, "duration_type" => "month", "duration_value" => 2],
            ["code" => "TRIMEST", "name" => "Membresia trimestral", "description" => "Acceso por tres meses con tarifa preferencial.", "price" => 320.00, "min" => 290.00, "max" => 390.00, "duration_type" => "month", "duration_value" => 3],
            ["code" => "SEMEST", "name" => "Membresia semestral", "description" => "Acceso por seis meses.", "price" => 600.00, "min" => 540.00, "max" => 720.00, "duration_type" => "month", "duration_value" => 6],
            ["code" => "ANUAL", "name" => "Membresia anual", "description" => "Acceso por doce meses.", "price" => 1050.00, "min" => 950.00, "max" => 1250.00, "duration_type" => "year", "duration_value" => 1],
            ["code" => "PREMM", "name" => "Membresia premium mensual", "description" => "Plan mensual con beneficios adicionales.", "price" => 180.00, "min" => 160.00, "max" => 230.00, "duration_type" => "month", "duration_value" => 1],
            ["code" => "FAMIL", "name" => "Membresia familiar mensual", "description" => "Plan mensual para grupo familiar.", "price" => 300.00, "min" => 270.00, "max" => 380.00, "duration_type" => "month", "duration_value" => 1],
        ];

        $records = [];

        foreach($this->expandTemplates($products, $productLimit) as $index => $product) {

            $records[] = [
                "internal_code" => "PRO-".$product["code"],
                "barcode" => $this->barcode($index + 1),
                "name" => $product["name"],
                "description" => $product["description"],
                "price" => $product["price"],
                "min_price" => $product["min"],
                "max_price" => $product["max"],
                "brand_id" => $brands[$product["brand"]]->id ?? null,
                "type" => "product",
                "categories" => $product["categories"],
                "inventory" => ["quantity" => $product["quantity"], "minimum_stock" => $product["minimum"], "average_cost" => $product["cost"]],
            ];

        }

        foreach($this->expandTemplates($services, $serviceLimit) as $service) {

            $records[] = [
                "internal_code" => "SER-".$service["code"],
                "barcode" => null,
                "name" => $service["name"],
                "description" => $service["description"],
                "price" => $service["price"],
                "min_price" => $service["min"],
                "max_price" => $service["max"],
                "brand_id" => null,
                "type" => "service",
                "categories" => ["Servicios"],
                "inventory" => [],
            ];

        }

        foreach($this->expandTemplates($subscriptions, $subscriptionLimit) as $subscription) {

            $records[] = [
                "internal_code" => "MEM-".$subscription["code"],
                "barcode" => null,
                "name" => $subscription["name"],
                "description" => $subscription["description"],
                "price" => $subscription["price"],
                "min_price" => $subscription["min"],
                "max_price" => $subscription["max"],
                "brand_id" => null,
                "type" => "subscription",
                "duration_type" => $subscription["duration_type"],
                "duration_value" => $subscription["duration_value"],
                "categories" => ["Membresias"],
                "inventory" => [],
            ];

        }

        return $records;

    }

    private function expandTemplates(array $templates, int $limit): array {

        $records = [];
        $templateCount = count($templates);

        for($index = 0; $index < $limit; $index++) {

            $record = $templates[$index % $templateCount];

            if($index >= $templateCount) {

                $sequence = $index + 1;
                $record["code"] .= "-".$sequence;
                $record["name"] .= " #".$sequence;

            }

            $records[] = $record;

        }

        return $records;

    }

    private function barcode(int $sequence): string {

        $base = "200".str_pad((string) $sequence, 9, "0", STR_PAD_LEFT);
        $sum = 0;

        for($index = 0; $index < 12; $index++) {

            $sum += (int) $base[$index] * ($index % 2 === 0 ? 1 : 3);

        }

        return $base.((10 - ($sum % 10)) % 10);

    }

    private function seedBatch(
        array $batch,
        array $categories,
        array $warehouses,
        int $measurementUnitId,
        int $precision
    ): void {

        $now = now()->toDateTimeString();
        $rows = [];

        foreach($batch as $payload) {

            $rows[] = [
                "internal_code" => $payload["internal_code"],
                "barcode" => $payload["barcode"],
                "name" => $payload["name"],
                "description" => $payload["description"],
                "price" => $payload["price"],
                "price_includes_tax" => true,
                "min_price" => $payload["min_price"],
                "max_price" => $payload["max_price"],
                "brand_id" => $payload["brand_id"],
                "currency_id" => $this->currencyId,
                "measurement_unit_id" => $payload["type"] === Item::TYPE_PRODUCT ? $measurementUnitId : null,
                "type" => $payload["type"],
                "duration_type" => $payload["duration_type"] ?? null,
                "duration_value" => $payload["duration_value"] ?? null,
                "see_my_web" => true,
                "see_my_web_price" => true,
                "status" => "active",
                "created_at" => $now,
                "created_by" => $this->userId,
                "updated_at" => $now,
                "updated_by" => $this->userId,
            ];

        }

        DB::table("items")->upsert(
            $rows,
            ["type", "internal_code"],
            [
                "barcode", "name", "description", "price", "price_includes_tax",
                "min_price", "max_price", "brand_id", "currency_id",
                "measurement_unit_id", "duration_type", "duration_value",
                "see_my_web", "see_my_web_price", "status", "updated_at", "updated_by",
            ]
        );

        $itemIds = DB::table("items")
            ->whereIn("internal_code", array_column($batch, "internal_code"))
            ->pluck("id", "internal_code")
            ->all();

        $this->seedCategoryLinks($batch, $itemIds, $categories, $now);
        $this->seedInitialStock($batch, $itemIds, $warehouses, $precision, $now);

    }

    private function seedCategoryLinks(array $batch, array $itemIds, array $categories, string $now): void {

        $rows = [];

        foreach($batch as $payload) {

            foreach($payload["categories"] as $categoryName) {

                $categoryId = $categories[$categoryName]->id ?? null;

                if(!$categoryId) continue;

                $rows[] = [
                    "category_id" => $categoryId,
                    "item_id" => $itemIds[$payload["internal_code"]],
                    "status" => "active",
                    "created_at" => $now,
                    "created_by" => $this->userId,
                    "updated_at" => $now,
                    "updated_by" => $this->userId,
                ];

            }

        }

        foreach(array_chunk($rows, self::BATCH_SIZE) as $chunk) {

            DB::table("category_items")->upsert(
                $chunk,
                ["category_id", "item_id"],
                ["status", "updated_at", "updated_by"]
            );

        }

    }

    private function seedInitialStock(
        array $batch,
        array $itemIds,
        array $warehouses,
        int $precision,
        string $now
    ): void {

        if(!$warehouses) return;

        $products = array_values(array_filter($batch, fn(array $payload) => $payload["type"] === Item::TYPE_PRODUCT));

        if(!$products) return;

        $productIds = array_map(fn(array $payload) => $itemIds[$payload["internal_code"]], $products);
        $existingStock = DB::table("warehouse_items")
            ->whereIn("item_id", $productIds)
            ->whereIn("warehouse_id", $warehouses)
            ->get(["warehouse_id", "item_id"])
            ->mapWithKeys(fn($row) => [$row->warehouse_id.":".$row->item_id => true])
            ->all();
        $existingMovements = DB::table("inventory_movements")
            ->where("origin_type", "commercial_catalog_seeder")
            ->whereIn("origin_id", $productIds)
            ->whereIn("warehouse_id", $warehouses)
            ->get(["warehouse_id", "origin_id"])
            ->mapWithKeys(fn($row) => [$row->warehouse_id.":".$row->origin_id => true])
            ->all();
        $stockRows = [];
        $movementRows = [];

        foreach($products as $payload) {

            $itemId = $itemIds[$payload["internal_code"]];
            $inventory = $payload["inventory"];
            $quantity = (float) $inventory["quantity"];
            $minimumStock = (float) $inventory["minimum_stock"];
            $averageCost = (float) $inventory["average_cost"];
            $inventoryValue = round($quantity * $averageCost, $precision);

            foreach($warehouses as $warehouseId) {

                $key = $warehouseId.":".$itemId;

                if(isset($existingStock[$key])) continue;

                $stockRows[] = [
                    "warehouse_id" => $warehouseId,
                    "item_id" => $itemId,
                    "quantity" => $quantity,
                    "minimum_stock" => $minimumStock,
                    "average_cost" => $averageCost,
                    "inventory_value" => $inventoryValue,
                    "status" => "active",
                    "created_at" => $now,
                    "created_by" => $this->userId,
                    "updated_at" => $now,
                    "updated_by" => $this->userId,
                ];

                if(isset($existingMovements[$key])) continue;

                $movementRows[] = [
                    "warehouse_id" => $warehouseId,
                    "item_id" => $itemId,
                    "user_id" => $this->userId,
                    "movement_type" => "initial_stock",
                    "origin_type" => "commercial_catalog_seeder",
                    "origin_id" => $itemId,
                    "quantity_before" => 0,
                    "quantity_change" => $quantity,
                    "quantity_after" => $quantity,
                    "unit_cost" => $averageCost,
                    "value_before" => 0,
                    "value_change" => $inventoryValue,
                    "value_after" => $inventoryValue,
                    "reason" => "Stock inicial generado por seeder comercial.",
                    "metadata" => json_encode([
                        "reference" => "SEED-CATALOG-{$payload["internal_code"]}",
                        "source" => "CommercialCatalogSeeder",
                    ], JSON_THROW_ON_ERROR),
                    "created_at" => $now,
                ];

            }

        }

        foreach(array_chunk($stockRows, self::BATCH_SIZE) as $chunk) {

            DB::table("warehouse_items")->insertOrIgnore($chunk);

        }

        foreach(array_chunk($movementRows, self::BATCH_SIZE) as $chunk) {

            DB::table("inventory_movements")->insert($chunk);

        }

    }

}
