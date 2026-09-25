<?php

declare(strict_types=1);

namespace App\Helpers\System;

use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};

/**
 * Query Helper
 * Provides common query operations
 */
class QueryHelper {
    /**
     * Apply pagination to query
     *
     * @param  int  $perPage Items per page
     * @param  int  $maxPerPage Maximum items per page
     */
    public static function paginate(Builder $query, int $perPage = 15, int $maxPerPage = 1000): LengthAwarePaginator {

        // Ensure perPage doesn't exceed maximum
        $perPage = min($perPage, $maxPerPage);

        // Ensure perPage is at least 1

        $perPage = max(1, $perPage);

        return $query->paginate($perPage);

    }

    /**
     * Apply search filter to query
     *
     * @param  string  $field Field name
     * @param  string|null  $value Search value
     */
    public static function applySearch(Builder $query, string $field, ?string $value): Builder {

        if(!Utilities::isDefined($value)) {

            return $query;

        }

        $searchTerm = Utilities::getWordSearch($value);

        return $query->where($field, "like", $searchTerm);

    }

    /**
     * Apply multiple field search (OR condition)
     *
     * @param  array  $fields Field names
     * @param  string|null  $value Search value
     */
    public static function applyMultiFieldSearch(Builder $query, array $fields, ?string $value): Builder {

        if(!Utilities::isDefined($value) || empty($fields)) {

            return $query;

        }

        $searchTerm = Utilities::getWordSearch($value);

        return $query->where(function($q) use ($fields, $searchTerm) {

            foreach($fields as $field) {

                $q->orWhere($field, "like", $searchTerm);

            }

        });

    }

    /**
     * Apply status filter
     *
     * @param  string|array|null  $status Status value(s)
     */
    public static function applyStatusFilter(Builder $query, $status): Builder {

        if(!Utilities::isDefined($status)) {

            return $query;

        }

        if(is_array($status)) {

            return $query->whereIn("status", $status);

        }

        return $query->where("status", $status);

    }

    /**
     * Apply date range filter
     *
     * @param  string  $field Date field name
     * @param  string|null  $startDate Start date
     * @param  string|null  $endDate End date
     */
    public static function applyDateRangeFilter(Builder $query, string $field, ?string $startDate, ?string $endDate): Builder {

        if(Utilities::isDefined($startDate)) {

            $query->where($field, ">=", $startDate);

        }

        if(Utilities::isDefined($endDate)) {

            $query->where($field, "<=", $endDate);

        }

        return $query;

    }

    /**
     * Apply ordering
     *
     * @param  string  $orderBy Order by field
     * @param  string  $orderDirection Order direction (ASC/DESC)
     */
    public static function applyOrdering(Builder $query, string $orderBy = "id", string $orderDirection = "DESC"): Builder {

        return $query->orderBy($orderBy, $orderDirection);

    }

}
