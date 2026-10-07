<?php

declare(strict_types=1);

namespace App\Services\System\Sales;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Customers\{Customer};
use Illuminate\Database\Eloquent\{Builder};
use InvalidArgumentException;

final class CommercialSelectionService {
    private const PAGE_SIZE = 25;

    public static function search(string $resource, string $search = "", int $page = 1): array {

        $search = trim($search);
        $page = max(1, $page);

        if($resource === "customers") {

            $query = self::customersQuery($search)
                ->where("status", "active")
                ->with("identityDocumentType");

        }elseif($resource === "items") {

            $query = Item::query()
                ->availableForSale()
                ->with(["currency", "brand", "categoryItems.category", "warehouseItems.warehouse"])
                ->when($search !== "", fn($query) => $query->where(function($query) use ($search) {

                    $query->where("name", "like", "%{$search}%")
                        ->orWhere("internal_code", "like", "%{$search}%")
                        ->orWhere("barcode", "like", "%{$search}%");

                }))
                ->orderBy("type")
                ->orderBy("name")
                ->orderBy("id");

        }else {

            throw new InvalidArgumentException("El recurso de búsqueda no está disponible.");

        }

        return self::paginate($query, ["*"], $page);

    }

    public static function searchCustomerFilters(string $search = "", int $page = 1): array {

        $result = self::paginate(
            self::customersQuery(trim($search)),
            ["id", "name", "document_number"],
            max(1, $page)
        );

        $result["records"] = array_map(static fn(Customer $customer) => [
            "id" => $customer->id,
            "name" => $customer->name,
            "document_number" => $customer->document_number,
        ], $result["records"]);

        return $result;

    }

    private static function customersQuery(string $search): Builder {

        return Customer::query()
            ->when($search !== "", fn($query) => $query->where(function($query) use ($search) {

                $query->where("name", "like", "%{$search}%")
                    ->orWhere("document_number", "like", "%{$search}%");

            }))
            ->orderBy("name")
            ->orderBy("id");

    }

    private static function paginate(Builder $query, array $columns, int $page): array {

        $results = $query->simplePaginate(self::PAGE_SIZE, $columns, "page", $page);

        return [
            "records" => $results->items(),
            "page" => $results->currentPage(),
            "has_more" => $results->hasMorePages(),
        ];

    }
}
