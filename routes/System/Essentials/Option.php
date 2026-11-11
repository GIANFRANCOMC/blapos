<?php

declare(strict_types=1);

use App\Http\Controllers\System\Essentials\{OptionController};
use Illuminate\Support\Facades\{Route};

foreach(OptionController::ENTITIES_BY_ORIGIN as $origin => $entities) {
    Route::get("/{$origin}/{entity}", [OptionController::class, "index"])
        ->whereIn("entity", $entities)
        ->defaults("origin", $origin)
        ->name("options.{$origin}");
}
