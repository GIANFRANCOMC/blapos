<?php

declare(strict_types=1);

namespace App\Casts\System;

use App\Helpers\System\{Numbers};
use Illuminate\Contracts\Database\Eloquent\{CastsAttributes};
use Illuminate\Database\Eloquent\{Model};

final class ConfigurableDecimal implements CastsAttributes {

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string {

        if($value === null) {

            return null;

        }

        return Numbers::storage($value);

    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed {

        return Numbers::normalizeDecimalInput($value);

    }

}
