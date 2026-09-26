<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Brands;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Brand};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

final class BrandService {
    private const ALLOWED_FIELDS = [
        "internal_code",
        "name",
        "description",
        "logo_path",
        "origin_country_code",
        "website_url",
        "status",
    ];

    private const SEARCHABLE_FIELDS = [
        "internal_code",
        "name",
        "description",
        "origin_country_code",
        "website_url",
    ];

    public static function create(array $data, int $userId): Brand {

        self::validateUser($userId);

        return DB::transaction(function() use ($data, $userId) {

            return Brand::create(self::prepareData($data, [
                "status" => $data["status"] ?? "active",
                "created_by" => $userId,
            ]));

        });

    }

    public static function update(Brand $brand, array $data, int $userId): Brand {

        self::validateUser($userId);

        DB::transaction(function() use ($brand, $data, $userId) {

            $changes = self::prepareChangedData($brand, $data);

            if($changes !== []) {

                $changes["updated_by"] = $userId;
                $brand->update($changes);

            }

        });

        return $brand->fresh();

    }

    public static function findByIdInTenant(
        int $id,
        ?array $statuses = ["active"]
    ): ?Brand {

        $query = Brand::query()
            ->whereKey($id);

        if($statuses !== null && $statuses !== []) {

            $query->whereIn("status", $statuses);

        }

        return $query->first();

    }

    public static function getPaginatedList(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {

        $query = Brand::query()
            ->withCount([
                "products as products_count" => fn(Builder $builder) => $builder->where("status", "active"),
            ]);

        $filterBy = $filters["filter_by"] ?? null;
        $word = $filters["word"] ?? null;

        if(Utilities::isDefined($word) && Utilities::isDefined($filterBy)) {

            $searchTerm = Utilities::getWordSearch($word);

            if($filterBy === "all") {

                $query->where(function(Builder $builder) use ($searchTerm) {

                    foreach(self::SEARCHABLE_FIELDS as $index => $field) {

                        $method = $index === 0 ? "where" : "orWhere";
                        $builder->{$method}($field, "like", $searchTerm);

                    }

                });

            }elseif(in_array($filterBy, self::SEARCHABLE_FIELDS, true)) {

                $query->where($filterBy, "like", $searchTerm);

            }

        }

        return $query->orderBy("name")
            ->paginate($perPage);

    }

    private static function prepareData(array $data, array $defaults = []): array {

        $prepared = $defaults;

        foreach(self::ALLOWED_FIELDS as $field) {

            if(array_key_exists($field, $data)) {

                $prepared[$field] = $data[$field];

            }

        }

        return $prepared;

    }

    private static function prepareChangedData(Brand $brand, array $data): array {

        $prepared = self::prepareData($data);

        return array_filter(
            $prepared,
            fn($value, $field) => $value !== $brand->{$field},
            ARRAY_FILTER_USE_BOTH
        );

    }

    private static function validateUser(int $userId): void {

        if($userId <= 0) {

            throw new \InvalidArgumentException("El usuario autenticado es obligatorio.");

        }

    }
}
