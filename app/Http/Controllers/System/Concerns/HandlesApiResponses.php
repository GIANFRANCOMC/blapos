<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Concerns;

use App\Helpers\System\{ApiResponse, TranslationHelper};
use Illuminate\Http\{JsonResponse};

/**
 * Trait for handling API responses with translations
 * Provides reusable methods for controllers
 */
trait HandlesApiResponses {

    /**
     * Get translation namespace for the module
     * Must be defined in the controller using this trait
     */
    abstract protected function getTranslationNamespace(): string;

    /**
     * Get translation for the module
     *
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     */
    protected function trans(string $key, array $replace = []): string {

        return TranslationHelper::getWithFallback($this->getTranslationNamespace(), $key, $replace);

    }

    /**
     * Return error response with translation
     *
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     * @param  int  $statusCode HTTP status code
     */
    protected function errorResponse(string $key, array $replace = [], int $statusCode = 500): JsonResponse {

        return ApiResponse::error($this->trans($key, $replace), $statusCode);

    }

    /**
     * Return not found response with translation
     */
    protected function notFoundResponse(): JsonResponse {

        return ApiResponse::notFound($this->trans("not_found"));

    }

    /**
     * Return success response with translation
     *
     * @param  mixed  $data Response data
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     * @param  int  $statusCode HTTP status code
     */
    protected function successResponse($data = null, string $key = "", array $replace = [], int $statusCode = 200): JsonResponse {

        $message = $key ? $this->trans($key, $replace) : "";

        return ApiResponse::success($data, $message, $statusCode);

    }

    /**
     * Return created response with translation
     *
     * @param  mixed  $resource Created resource data
     * @param  string  $key Translation key
     * @param  string  $resourceKey Resource key name
     * @param  array  $replace Replacements
     */
    protected function createdResponse($resource = null, string $key = "created", string $resourceKey = "data", array $replace = []): JsonResponse {

        return ApiResponse::created($resource, $this->trans($key, $replace), $resourceKey);

    }

    /**
     * Return updated response with translation
     *
     * @param  mixed  $resource Updated resource data
     * @param  string  $key Translation key
     * @param  string  $resourceKey Resource key name
     * @param  array  $replace Replacements
     */
    protected function updatedResponse($resource = null, string $key = "updated", string $resourceKey = "data", array $replace = []): JsonResponse {

        return ApiResponse::updated($resource, $this->trans($key, $replace), $resourceKey);

    }

}
