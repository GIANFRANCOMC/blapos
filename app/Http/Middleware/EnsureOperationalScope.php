<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\System\Organizations\{AccessScopeService};
use Closure;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB};

final class EnsureOperationalScope {
    private const INPUTS = [
        AccessScopeService::BRANCH => ["branch_id", "branch_ids", "filter.branch_id"],
        AccessScopeService::CASH_REGISTER => ["cash_register_id", "cash_register_ids", "filter.cash_register_id"],
        AccessScopeService::WAREHOUSE => [
            "warehouse_id",
            "warehouse_ids",
            "source_warehouse_id",
            "destination_warehouse_id",
            "filter.warehouse_id",
            "inventory_counts.*.warehouse_id",
        ],
    ];

    public function handle(Request $request, Closure $next) {

        $user = $request->user();

        if(!$user) {

            return $next($request);

        }

        foreach(self::INPUTS as $type => $paths) {

            foreach($paths as $path) {

                foreach($this->values($request->all(), $path) as $resourceId) {

                    if(!AccessScopeService::canAccess($user, $type, $resourceId)) {

                        return $this->denied($request);

                    }

                }

            }

        }

        if($request->filled("cash_session_id")) {

            $cashRegisterId = DB::table("cash_sessions")
                ->where("id", (int) $request->input("cash_session_id"))
                ->value("cash_register_id");

            if(!$cashRegisterId || !AccessScopeService::canAccess($user, AccessScopeService::CASH_REGISTER, (int) $cashRegisterId)) {

                return $this->denied($request);

            }

        }

        if($request->filled("serie_id")) {

            $branchId = DB::table("series")
                ->where("id", (int) $request->input("serie_id"))
                ->value("branch_id");

            if(!$branchId || !AccessScopeService::canAccess($user, AccessScopeService::BRANCH, (int) $branchId)) {

                return $this->denied($request);

            }

        }

        if($this->routeResourceDenied($request)) {

            return $this->denied($request);

        }

        return $next($request);

    }

    private function routeResourceDenied(Request $request): bool {

        $id = (int) ($request->route("id") ?? 0);
        $prefix = explode(".", (string) $request->route()?->getName())[0] ?? "";

        if($id <= 0) {

            return false;

        }

        if($prefix === "purchases") {

            $warehouseId = DB::table("purchase_headers")
                ->where("id", $id)
                ->value("warehouse_id");

            return !$warehouseId
                || !AccessScopeService::canAccess($request->user(), AccessScopeService::WAREHOUSE, (int) $warehouseId);

        }

        if($request->route()?->getName() === "sales.deliveries.deliver") {

            return $this->saleDeliveryResourceDenied($request, $id);

        }

        if($prefix === "sales") {

            $sale = DB::table("sales_header")
                ->join("series", "series.id", "=", "sales_header.serie_id")
                ->where("sales_header.id", $id)
                ->select(["series.branch_id", "sales_header.warehouse_id"])
                ->first();

            return !$sale
                || !AccessScopeService::canAccess($request->user(), AccessScopeService::BRANCH, (int) $sale->branch_id)
                || ($sale->warehouse_id && !AccessScopeService::canAccess(
                    $request->user(),
                    AccessScopeService::WAREHOUSE,
                    (int) $sale->warehouse_id
                ));

        }

        return false;

    }

    private function saleDeliveryResourceDenied(Request $request, int $deliveryId): bool {

        $delivery = DB::table("sale_deliveries")
            ->join("sales_header", "sales_header.id", "=", "sale_deliveries.sale_header_id")
            ->join("series", "series.id", "=", "sales_header.serie_id")
            ->where("sale_deliveries.id", $deliveryId)
            ->select([
                "series.branch_id",
                "sale_deliveries.warehouse_id",
            ])
            ->first();

        return !$delivery
            || !AccessScopeService::canAccess($request->user(), AccessScopeService::BRANCH, (int) $delivery->branch_id)
            || ($delivery->warehouse_id && !AccessScopeService::canAccess(
                $request->user(),
                AccessScopeService::WAREHOUSE,
                (int) $delivery->warehouse_id
            ));

    }

    private function values(array $payload, string $path): array {

        if(str_contains($path, ".*.")) {

            [$collectionPath, $field] = explode(".*.", $path, 2);

            return collect(data_get($payload, $collectionPath, []))
                ->pluck($field)
                ->filter(fn($value) => $value !== null && $value !== "")
                ->map(fn($value) => (int) $value)
                ->values()
                ->all();

        }

        $value = data_get($payload, $path);

        return collect(is_array($value) ? $value : [$value])
            ->filter(fn($item) => $item !== null && $item !== "")
            ->map(fn($item) => (int) (is_array($item) ? ($item["id"] ?? $item["code"] ?? 0) : $item))
            ->filter()
            ->values()
            ->all();

    }

    private function denied(Request $request) {

        $message = "No tienes permiso para operar con la sucursal, caja o almacén seleccionado.";

        if($request->expectsJson()) {

            return new JsonResponse(["bool" => false, "msg" => $message], 403);

        }

        abort(403, $message);

    }
}
