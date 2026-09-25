<?php

declare(strict_types=1);

namespace App\Services\System\Base;

use App\Helpers\System\{TranslationHelper};
use Illuminate\Database\Eloquent\{Model};
use Illuminate\Support\Facades\{DB};

/**
 * Base Service Class
 * Provides common functionality for all service classes
 */
abstract class BaseService {
    /**
     * Translation namespace for the service
     * Must be defined in child classes
     */
    abstract protected static function getTranslationNamespace(): string;

    /**
     * Get translation with fallback
     *
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     */
    protected static function trans(string $key, array $replace = []): string {

        return TranslationHelper::getWithFallback(static::getTranslationNamespace(), $key, $replace);

    }

    /**
     * Execute database transaction
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected static function transaction(callable $callback) {

        return DB::transaction($callback);

    }

    /**
     * Prepare data for create/update operations
     * Filters only allowed fields
     *
     * @param  array  $data Input data
     * @param  array  $allowedFields Allowed fields
     */
    protected static function prepareData(array $data, array $allowedFields): array {

        $prepared = [];

        foreach($allowedFields as $field) {

            if(array_key_exists($field, $data)) {

                $prepared[$field] = $data[$field];

            }

        }

        return $prepared;

    }

    /**
     * Prepare data for create operation
     *
     * @param  array  $data Input data
     * @param  int  $userId User ID
     * @param  array  $allowedFields Allowed fields
     */
    protected static function prepareDataForCreate(array $data, int $userId, array $allowedFields): array {

        $prepared = static::prepareData($data, $allowedFields);

        $prepared["created_at"] = now();
        $prepared["created_by"] = $userId;

        return $prepared;

    }

    /**
     * Prepare data for update operation (only changed fields)
     *
     * @param  Model  $model Model instance
     * @param  array  $data Input data
     * @param  array  $allowedFields Allowed fields
     * @param  int  $userId User ID
     */
    protected static function prepareDataForUpdate(Model $model, array $data, array $allowedFields, int $userId): array {

        $updateData = [];

        foreach($allowedFields as $field) {

            if(isset($data[$field]) && $data[$field] !== $model->$field) {

                $updateData[$field] = $data[$field];

            }

        }

        if(!empty($updateData)) {

            $updateData["updated_at"] = now();
            $updateData["updated_by"] = $userId;

        }

        return $updateData;

    }

    /**
     * Validate that a model exists in the current tenant database.
     *
     * @param  Model|null  $model Model instance
     */
    protected static function validateModel(?Model $model): bool {

        if($model === null) {

            return false;

        }

        return true;

    }
}
