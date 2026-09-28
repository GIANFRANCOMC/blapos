<?php

declare(strict_types=1);

namespace App\Services\System\Sales;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Customers\{Customer};
use InvalidArgumentException;

final class CommercialSelectionService {
    private const PAGE_SIZE = 25;

    public static function search(string $resource, string $search = "", int $page = 1): array {

        $search = trim($search);
        $page = max(1, $page);

        if($resource === "customers") {

            $query = Customer::query()
                ->where("status", "active")
                ->with("identityDocumentType")
                ->when($search !== "", fn($query) => $query->where(function($query) use ($search) {

                    $query->where("name", "like", "%{$search}%")
                        ->orWhere("document_number", "like", "%{$search}%");

                }))
                ->orderBy("name")
                ->orderBy("id");

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

        $results = $query->simplePaginate(self::PAGE_SIZE, ["*"], "page", $page);

        return [
            "records" => $results->items(),
            "page" => $results->currentPage(),
            "has_more" => $results->hasMorePages(),
        ];

    }
}
