<?php

declare(strict_types=1);

namespace App\Models\System\General;

use App\Models\System\Catalogs\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class MeasurementUnit extends Model {

    protected $table = "measurement_units";

    protected $fillable = [
        "code",
        "singular_name",
        "plural_name",
        "status",
    ];

    public static function defaultId(): int {

        $id = static::query()->orderBy("id")->value("id");

        if($id === null) {

            throw new RuntimeException("No hay una unidad de medida configurada para los productos.");

        }

        return (int) $id;

    }

    public function items(): HasMany {

        return $this->hasMany(Item::class, "measurement_unit_id");

    }

}
