<?php

declare(strict_types=1);

namespace App\Services\System\Essentials;

use App\Helpers\System\{Numbers};
use App\Models\System\Organizations\{User};
use App\Models\System\Sales\{SaleBody};
use App\Services\System\Organizations\{AccessScopeService};
use Carbon\{CarbonImmutable};
use Illuminate\Database\Query\{Builder};
use Illuminate\Pagination\{LengthAwarePaginator};
use Illuminate\Support\Facades\{DB};

final class CommercialPerformanceService {

    private const PER_PAGE = 10;

    public function filters(User $user): array {

        $today = CarbonImmutable::today(DB::table("company_settings")
            ->where("group", "localization")
            ->where("key", "timezone")
            ->where("status", "active")
            ->value("value") ?: "America/Lima");

        $allowedIds = AccessScopeService::allowedIds($user, AccessScopeService::BRANCH);
        $branches = DB::table("branches")
            ->when($allowedIds !== null, fn(Builder $query) => $query->whereIn("id", $allowedIds))
            ->orderBy("name")->orderBy("id")
            ->get(["id", "name", "status"]);

        return [
            "defaults" => ["from" => $today->subDays(29)->toDateString(), "to" => $today->toDateString()],
            "branches" => $branches,
            "types" => SaleBody::getTypes(),
        ];

    }

    public function report(User $user, array $filters, bool $includeEvolution): array {

        $allowedIds = AccessScopeService::allowedIds($user, AccessScopeService::BRANCH);
        $branchId = isset($filters["branch_id"]) ? (int) $filters["branch_id"] : null;

        $lines = DB::table("sales_header as sale")
            ->join("series", "series.id", "=", "sale.serie_id")
            ->join("sales_body as line", "line.sale_header_id", "=", "sale.id")
            ->where("sale.status", "active")
            ->where("line.status", "active")
            ->whereBetween("sale.issue_date", [$filters["from"], $filters["to"]])
            ->when($allowedIds !== null, fn(Builder $query) => $query->whereIn("series.branch_id", $allowedIds))
            ->when($branchId !== null, fn(Builder $query) => $query->where("series.branch_id", $branchId))
            ->when($filters["type"] ?? null, fn(Builder $query, string $type) => $query->where("line.type", $type));

        return DB::transaction(fn(): array => [
            "items" => $this->items($lines, (int) ($filters["page"] ?? 1)),
            "evolution" => $includeEvolution ? $this->evolution($lines, $filters["from"], $filters["to"]) : null,
        ]);

    }

    private function items(Builder $lines, int $page): LengthAwarePaginator {

        $grouped = (clone $lines)
            ->select("line.item_id", "line.type", "line.currency_id")
            ->selectRaw("MAX(line.id) AS snapshot_id, SUM(line.quantity) AS quantity, SUM(line.total) AS total")
            ->groupBy("line.item_id", "line.type", "line.currency_id");

        return DB::query()->fromSub($grouped, "performance")
            ->join("sales_body as snapshot", "snapshot.id", "=", "performance.snapshot_id")
            ->join("currencies", "currencies.id", "=", "performance.currency_id")
            ->orderBy("snapshot.name")->orderBy("performance.item_id")
            ->orderBy("performance.type")->orderBy("currencies.code")->orderBy("currencies.id")
            ->paginate(self::PER_PAGE, [
                "performance.item_id",
                "performance.type",
                "performance.quantity",
                "performance.total",
                "snapshot.name",
                "currencies.id as currency_id",
                "currencies.code",
                "currencies.sign",
            ], "page", $page)
            ->through(fn(object $item): array => [
                "item_id" => (int) $item->item_id,
                "name" => $item->name,
                "type" => $item->type,
                "currency_id" => (int) $item->currency_id,
                "code" => $item->code,
                "sign" => $item->sign,
                "quantity" => Numbers::storage($item->quantity),
                "total" => Numbers::storage($item->total),
            ]);

    }

    private function evolution(Builder $lines, string $from, string $to): array {

        $days = [];
        $end = CarbonImmutable::parse($to, "UTC");

        for($day = CarbonImmutable::parse($from, "UTC"); $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $days[] = $day->toDateString();
        }

        $series = $lines
            ->join("currencies", "currencies.id", "=", "line.currency_id")
            ->select("sale.issue_date", "currencies.id", "currencies.code", "currencies.sign")
            ->selectRaw("SUM(line.total) AS total")
            ->groupBy("sale.issue_date", "currencies.id", "currencies.code", "currencies.sign")
            ->orderBy("currencies.code")->orderBy("currencies.id")
            ->get()
            ->groupBy("id")
            ->map(function($entries) use ($days): array {
                $currency = $entries->first();
                $totals = $entries->pluck("total", "issue_date");

                return [
                    "currency_id" => (int) $currency->id,
                    "code" => $currency->code,
                    "sign" => $currency->sign,
                    "totals" => array_map(fn(string $day): string => Numbers::storage($totals->get($day, "0")), $days),
                ];
            })->values()->all();

        return ["days" => $days, "series" => $series];

    }

}
