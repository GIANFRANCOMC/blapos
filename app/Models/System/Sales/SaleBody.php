<?php

declare(strict_types=1);

namespace App\Models\System\Sales;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Customers\{Customer};
use App\Models\System\General\{Currency};
use Exception;
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo};

class SaleBody extends Model {
    protected $table = "sales_body";

    protected $appends = [
        "formatted_type",
        "formatted_extras",
        "formatted_status",
    ];

    protected $fillable = [
        "sale_header_id",
        "item_id",
        "currency_id",
        "name",
        "quantity",
        "price",
        "price_includes_tax",
        "igv_exempt",
        "total",
        "commission_type",
        "commission_value",
        "commission_amount",
        "customer_id",
        "type",
        "observation",
        "extras",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "price" => "App\\Casts\\System\\ConfigurableDecimal",
        "price_includes_tax" => "boolean",
        "igv_exempt" => "boolean",
        "total" => "App\\Casts\\System\\ConfigurableDecimal",
        "commission_value" => "App\\Casts\\System\\ConfigurableDecimal",
        "commission_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "canceled_at" => "datetime",
    ];

    // Appends
    public function getFormattedTypeAttribute() {

        return self::getTypes("first", $this->attributes["type"] ?? "")["label"] ?? "";

    }

    public function getFormattedExtrasAttribute() {

        try {

            return json_decode($this->extras);

        }catch(Exception $e) {

            return "";

        }

    }

    public function getFormattedStatusAttribute() {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    // Functions
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

    public static function getTypes($type = "all", $code = "") {

        return Item::getTypes($type, $code);

    }

    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
            ["code" => "canceled", "label" => "Anulado"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    public function scopeActive(Builder $query): Builder {

        return $query->where("status", "active");

    }

    public function scopeForSale(Builder $query, int $saleHeaderId): Builder {

        return $query->where("sale_header_id", $saleHeaderId);

    }

    // Relationships
    public function saleHeader(): BelongsTo {

        return $this->belongsTo(SaleHeader::class, "sale_header_id", "id");

    }

    public function item(): BelongsTo {

        return $this->belongsTo(Item::class, "item_id", "id");

    }

    public function currency(): BelongsTo {

        return $this->belongsTo(Currency::class, "currency_id", "id");

    }

    public function customer(): BelongsTo {

        return $this->belongsTo(Customer::class, "customer_id", "id");

    }
}
