<?php

declare(strict_types=1);

namespace App\Helpers\System;

use Illuminate\Http\{JsonResponse, Response};

/**
 * API Response Helper
 * Provides consistent API response formatting
 */
class ApiResponse {

    /**
     * Success response
     *
     * @param  mixed  $data Response data
     * @param  string  $message Success message
     * @param  int  $statusCode HTTP status code
     */
    public static function success($data = null, string $message = "", int $statusCode = Response::HTTP_OK): JsonResponse {

        $response = [
            "bool" => true,
            "msg" => $message,
        ];

        if($data !== null) {

            $response["data"] = $data;

        }

        return response()->json($response, $statusCode);

    }

    /**
     * Error response
     *
     * @param  string  $message Error message
     * @param  int  $statusCode HTTP status code
     * @param  array  $errors Validation errors (optional)
     */
    public static function error(string $message, int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR, array $errors = []): JsonResponse {

        $response = [
            "bool" => false,
            "msg" => $message,
        ];

        if(!empty($errors)) {

            $response["errors"] = $errors;

        }

        return response()->json($response, $statusCode);

    }

    /**
     * Not found response
     *
     * @param  string  $message Error message
     */
    public static function notFound(string $message = "Resource not found"): JsonResponse {

        return self::error($message, Response::HTTP_NOT_FOUND);

    }

    /**
     * Validation error response
     *
     * @param  array  $errors Validation errors
     * @param  string  $message Error message
     */
    public static function validationError(array $errors, string $message = "Validation failed"): JsonResponse {

        return self::error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);

    }

    /**
     * Created response (legacy format for frontend compatibility)
     *
     * @param  mixed  $resource Created resource data
     * @param  string  $message Success message
     * @param  string  $resourceKey Resource key name (e.g., "branch", "item")
     */
    public static function created($resource = null, string $message = "Resource created successfully", string $resourceKey = "data"): JsonResponse {

        $response = [
            "bool" => true,
            "msg" => $message,
        ];

        if($resource !== null) {

            $response[$resourceKey] = $resource;

        }

        return response()->json($response, Response::HTTP_OK);

    }

    /**
     * Updated response (legacy format for frontend compatibility)
     *
     * @param  mixed  $resource Updated resource data
     * @param  string  $message Success message
     * @param  string  $resourceKey Resource key name (e.g., "branch", "item")
     */
    public static function updated($resource = null, string $message = "Resource updated successfully", string $resourceKey = "data"): JsonResponse {

        $response = [
            "bool" => true,
            "msg" => $message,
        ];

        if($resource !== null) {

            $response[$resourceKey] = $resource;

        }

        return response()->json($response, Response::HTTP_OK);

    }

}
