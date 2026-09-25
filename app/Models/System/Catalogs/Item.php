<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use App\Helpers\System\{Utilities};
use App\Models\System\General\{Currency};
use App\Models\System\Sales\{SaleBody};
use App\Models\System\Warehouses\{InventoryMovement, WarehouseItem};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo, Relations\HasMany};

class Item extends Model {

    public const STATUS_ACTIVE = "active";

    public const TYPE_PRODUCT = "product";

    public const TYPE_SERVICE = "service";

    public const TYPE_SUBSCRIPTION = "subscription";

    protected $table = "items";

    protected $appends = [
        "formatted_type",
        "formatted_duration",
        "available_capacity",
        "is_expired",
        "formatted_status",
    ];

    protected $fillable = [
        "brand_id",
        "internal_code",
        "barcode",
        "name",
        "description",
        "price",
        "price_includes_tax",
        "igv_exempt",
        "min_price",
        "max_price",
        "currency_id",
        "type",
        "duration_type",
        "duration_value",
        "estimated_duration_minutes",
        "capacity_control_enabled",
        "capacity_limit",
        "capacity_used",
        "expires_at",
        "commission_rate",
        "commission_type",
        "commission_value",
        "attendance_limit_per_day",
        "benefits",
        "restrictions",
        "see_my_web",
        "see_my_web_price",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "price_includes_tax" => "boolean",
        "igv_exempt" => "boolean",
        "price" => "App\\Casts\\System\\ConfigurableDecimal",
        "min_price" => "App\\Casts\\System\\ConfigurableDecimal",
        "max_price" => "App\\Casts\\System\\ConfigurableDecimal",
        "estimated_duration_minutes" => "integer",
        "capacity_control_enabled" => "boolean",
        "capacity_limit" => "integer",
        "capacity_used" => "integer",
        "expires_at" => "datetime",
        "commission_rate" => "App\\Casts\\System\\ConfigurableDecimal",
        "commission_value" => "App\\Casts\\System\\ConfigurableDecimal",
        "attendance_limit_per_day" => "integer",
        "benefits" => "array",
        "restrictions" => "array",
    ];

    // Appends
    public function getFormattedTypeAttribute() {

        $type = $this->attributes["type"] ?? null;

        return $type ? (self::getTypes("first", $type)["label"] ?? "") : "";

    }

    public function getFormattedDurationAttribute() {

        $durationType = $this->attributes["duration_type"] ?? null;
        $durationValue = $this->attributes["duration_value"] ?? null;

        if(Utilities::isDefined($durationType) && Utilities::isDefined($durationValue)) {

            $prop = $durationValue > 1 ? "plural" : "label";
            $durationLabel = self::getDurationTypes("first", $durationType)[$prop] ?? "";

            return "{$durationValue} {$durationLabel}";

        }

        return "";

    }

    public function getFormattedStatusAttribute() {

        $status = $this->attributes["status"] ?? null;

        return $status ? (self::getStatuses("first", $status)["label"] ?? "") : "";

    }

    public function getAvailableCapacityAttribute(): ?int {

        if(!$this->hasCapacityControl()) {

            return null;

        }

        return $this->availableCapacity();

    }

    public function getIsExpiredAttribute(): bool {

        return $this->isExpired();

    }

    public function getStockQuantityAttribute($value) {

        return $value ?? 0;

    }

    // Functions
    public static function getTypes($type = "all", $code = "") {

        $types = [
            ["code" => "product", "label" => "Producto"],
            ["code" => "service", "label" => "Servicio"],
            ["code" => "subscription", "label" => "Membresía"],
        ];

        return Utilities::getValues($types, $type, $code);

    }

    public static function getDurationTypes($type = "all", $code = "") {

        $types = [
            ["code" => "hour", "label" => "Hora", "plural" => "Horas"],
            ["code" => "day", "label" => "Día", "plural" => "Días"],
            ["code" => "today", "label" => "Rutina", "plural" => "Rutinas"],
            ["code" => "month", "label" => "Mes", "plural" => "Meses"],
            ["code" => "year", "label" => "Año", "plural" => "Años"],
        ];

        return Utilities::getValues($types, $type, $code);

    }

    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    public function hasCapacityControl(): bool {

        return (bool) ($this->attributes["capacity_control_enabled"] ?? false);

    }

    public function availableCapacity(): int {

        if(!$this->hasCapacityControl()) {

            return 0;

        }

        $limit = max(0, (int) ($this->attributes["capacity_limit"] ?? 0));

        $used = max(0, (int) ($this->attributes["capacity_used"] ?? 0));

        return max(0, $limit - $used);

    }

    public function isExpired(): bool {

        $expiresAt = $this->getAttribute("expires_at");

        return $expiresAt !== null && $expiresAt->lte(now());

    }

    public function isAvailableForSale(): bool {

        if(($this->attributes["status"] ?? null) !== "active" || $this->isExpired()) {

            return false;

        }

        return !$this->hasCapacityControl() || $this->availableCapacity() > 0;

    }

    public static function expireActiveItems(int $companyId): int {

        return self::query()
            ->where("status", "active")
            ->whereNotNull("expires_at")
            ->where("expires_at", "<=", now())
            ->update([
                "status" => "inactive",
                "updated_at" => now(),
            ]);

    }

    public function scopeActive(Builder $query): Builder {

        return $query->where("status", self::STATUS_ACTIVE);

    }

    public function scopeOfType(Builder $query, string $type): Builder {

        return $query->where("type", $type);

    }

    public function scopeAvailableForSale(Builder $query): Builder {

        return $query->where("status", "active")
            ->where(function($subQuery) {

                $subQuery->whereNull("expires_at")
                    ->orWhere("expires_at", ">", now());

            })
            ->where(function($subQuery) {

                $subQuery->where("capacity_control_enabled", false)
                    ->orWhere(function($capacityQuery) {

                        $capacityQuery->where("capacity_control_enabled", true)
                            ->whereNotNull("capacity_limit")
                            ->whereColumn("capacity_used", "<", "capacity_limit");

                    });

            });

    }

    // Relationships
    public function currency(): BelongsTo {

        return $this->belongsTo(Currency::class, "currency_id", "id");

    }

    public function brand(): BelongsTo {

        return $this->belongsTo(Brand::class, "brand_id", "id");

    }

    public function categoryItems(): HasMany {

        return $this->hasMany(CategoryItem::class, "item_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function salesBody(): HasMany {

        return $this->hasMany(SaleBody::class, "item_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function warehouseItems(): HasMany {

        return $this->hasMany(WarehouseItem::class, "item_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function inventoryMovements(): HasMany {

        return $this->hasMany(InventoryMovement::class, "item_id", "id");

    }
}
