<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{Branch};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo, Relations\HasMany};

class Warehouse extends Model {
    protected $table = "warehouses";

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "branch_id",
        "name",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public static function plainName(?string $name): string {

        $name = trim((string) $name);

        if($name === "") {

            return "";

        }

        $normalized = preg_replace("/^.+\\s-\\s((?:Almacén|Almacen)\\s+\\d+)\$/u", "\$1", $name);

        return trim($normalized ?: $name);

    }

    public function getNameAttribute($value): string {

        return self::plainName($value);

    }

    public function setNameAttribute($value): void {

        $this->attributes["name"] = self::plainName($value);

    }

    // Appends
    public function getFormattedStatusAttribute() {

        $status = $this->attributes["status"] ?? null;

        return $status ? (self::getStatuses("first", $status)["label"] ?? "") : "";

    }

    // Functions
    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    // Relationships
    public function scopeActive(Builder $query): Builder {

        return $query->where("status", "active");

    }

    public function scopeForBranch(Builder $query, int $branchId): Builder {

        return $query->where("branch_id", $branchId);

    }

    public function branch(): BelongsTo {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function warehouseItems(): HasMany {

        return $this->hasMany(WarehouseItem::class, "warehouse_id", "id");

    }

    public function inventoryMovements(): HasMany {

        return $this->hasMany(InventoryMovement::class, "warehouse_id", "id");

    }
}
