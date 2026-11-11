<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\Controller;
use App\Services\System\Operations\{ServiceOperationService};
use App\Services\System\Sales\{CommercialSelectionService};
use Illuminate\Http\{JsonResponse, Request};

final class OptionController extends Controller {

    public const ENTITIES_BY_ORIGIN = [
        "sales-create" => ["customers", "items"],
        "sales-list" => ["customers"],
        "sales-deliveries" => ["customers"],
        "quotations-create" => ["customers", "items"],
        "restaurant" => ["customers", "items"],
        "service-sessions" => ["customers", "items"],
    ];

    public function index(Request $request): JsonResponse {

        $origin = $request->route("origin");
        $entity = $request->route("entity");
        $data = $request->validate([
            "search" => ["nullable", "string", "max:100"],
            "page" => ["nullable", "integer", "min:1", "max:10000"],
        ]);

        $search = trim($data["search"] ?? "");
        $page = (int) ($data["page"] ?? 1);

        $options = match($origin) {
            "sales-create", "quotations-create" => CommercialSelectionService::search($entity, $search, $page),
            "sales-list", "sales-deliveries" => CommercialSelectionService::searchCustomerFilters($search, $page),
            "restaurant", "service-sessions" => [
                "records" => $page === 1
                    ? ServiceOperationService::options($entity, $search, $origin === "service-sessions" ? "service" : null)
                    : [],
                "page" => $page,
                "has_more" => false,
            ],
        };

        return response()->json(["bool" => true, "data" => $options]);

    }

}
