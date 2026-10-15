<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Concerns;

use Exception;
use Illuminate\Http\{JsonResponse};
use Illuminate\Support\Facades\{Log};

/**
 * Trait for handling exceptions in controllers
 * Provides centralized exception handling
 */
trait HandlesExceptions {

    /**
     * Handle exception and return error response
     *
     * @param  Exception  $e Exception instance
     * @param  string  $operation Operation name (create, update, delete, etc.)
     */
    protected function handleException(Exception $e, string $operation = "operation"): JsonResponse {

        // Log exception for debugging
        Log::error("Exception in {$operation}", [
            "message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "trace" => $e->getTraceAsString(),
        ]);

        // Return user-friendly error response
        return $this->errorResponse("exception_{$operation}", ["message" => $e->getMessage()]);

    }

}
