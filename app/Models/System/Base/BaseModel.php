<?php

declare(strict_types=1);

namespace App\Models\System\Base;

use App\Helpers\System\{Utilities};
use Illuminate\Database\Eloquent\{Model};

/**
 * Base Model for System Models
 * Provides common functionality for all system models
 */
abstract class BaseModel extends Model {
    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    /**
     * Get statuses array
     * Override in child classes if needed
     *
     * @param  string  $type Type: "all" or "first"
     * @param  string  $code Status code
     * @return array|array|null
     */
    public static function getStatuses(string $type = "all", string $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    /**
     * Get formatted status attribute
     */
    public function getFormattedStatusAttribute(): string {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    /**
     * Scope: Active records
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query) {

        return $query->where("status", "active");

    }

    /**
     * Scope: Inactive records
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query) {

        return $query->where("status", "inactive");

    }
}
