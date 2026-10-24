<?php

declare(strict_types=1);

namespace App\Services\System\Essentials;

use App\Models\System\General\{SubSection};
use App\Models\System\Organizations\{User, UserNavigationMetric};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Support\Facades\{DB, Route, Schema};
use Illuminate\Support\{Collection};

final class UserNavigationService {

    private static array $metricsAvailability = [];

    private const POPULAR_LIMIT = 10;

    private const WORKSPACE_ROUTE = "workspace.index";

    public function record(User $user, string $routeName, ?string $requestedPath = null): void {

        if(
            $routeName === self::WORKSPACE_ROUTE
            || !$this->routeMatchesRequest($routeName, $requestedPath)
            || !$this->metricsAvailable()
        ) {

            return;

        }

        $companyId = app(TenantCompanyContext::class)->id();

        $subSectionId = SubSection::query()
            ->where("dom_route", $routeName)
            ->where("status", "active")
            ->whereHas("companiesSubSections", function($query) use ($companyId) {

                $query->where("company_id", $companyId)
                    ->where("status", "active");

            })
            ->value("id");

        if(!$subSectionId) {

            return;

        }

        DB::transaction(function() use ($user, $subSectionId): void {

            DB::table("users")
                ->where("id", $user->id)
                ->lockForUpdate()
                ->value("id");

            DB::table("user_navigation_metrics")->insertOrIgnore([
                "user_id" => $user->id,
                "sub_section_id" => $subSectionId,
                "visit_count" => 0,
            ]);

            DB::table("user_navigation_metrics")
                ->where("user_id", $user->id)
                ->where("sub_section_id", $subSectionId)
                ->increment("visit_count");

        }, 3);

    }

    private function routeMatchesRequest(string $routeName, ?string $requestedPath): bool {

        if(!$requestedPath || !Route::has($routeName)) {

            return true;

        }

        $routePath = parse_url(route($routeName), PHP_URL_PATH);

        return is_string($routePath)
            && rtrim($routePath, "/") === rtrim($requestedPath, "/");

    }

    public function getWorkspace(User $user): array {

        if(!$this->metricsAvailable()) {

            return ["popular" => collect(), "suggested" => $this->allowedCatalog($user)->take(6)->values()];

        }

        $catalog = $this->allowedCatalog($user);

        $allowedIds = $catalog->keys()->all();

        if($allowedIds === []) {

            return ["popular" => collect(), "suggested" => collect()];

        }

        $metrics = UserNavigationMetric::query()
            ->where("user_id", $user->id)
            ->whereIn("sub_section_id", $allowedIds)
            ->get();

        $decorate = fn(UserNavigationMetric $metric) => $catalog->get($metric->sub_section_id) + [
            "visit_count" => $metric->visit_count,
        ];

        $popular = $metrics
            ->where("visit_count", ">", 0)
            ->sort(function(UserNavigationMetric $left, UserNavigationMetric $right) {

                return ($right->visit_count <=> $left->visit_count)
                    ?: ($left->sub_section_id <=> $right->sub_section_id);

            })
            ->take(self::POPULAR_LIMIT)
            ->map($decorate)
            ->values();

        $popularIds = $popular->pluck("id");

        $suggested = $catalog
            ->reject(fn($item, $id) => $popularIds->contains($id))
            ->take(6)
            ->values();

        return compact("popular", "suggested");

    }

    private function allowedCatalog(User $user): Collection {

        return CompanySectionService::getSections((int) $user->role_id)
            ->mapWithKeys(function($section) {

                return $section->subSections
                    ->where("dom_route", "!=", self::WORKSPACE_ROUTE)
                    ->mapWithKeys(fn($subSection) => [
                        (int) $subSection->id => [
                            "id" => (int) $subSection->id,
                            "label" => $subSection->dom_label,
                            "description" => $subSection->description,
                            "url" => $subSection->dom_route_url,
                            "world" => $section->dom_label,
                            "icon" => $subSection->dom_icon ?: $section->dom_icon,
                        ],
                    ]);

            });

    }

    private function metricsAvailable(): bool {

        $connection = DB::getDefaultConnection();
        $database = (string) DB::connection($connection)->getDatabaseName();
        $key = $connection.":".$database;

        if(self::$metricsAvailability[$key] ?? false) {

            return true;

        }

        return self::$metricsAvailability[$key] = Schema::connection($connection)
            ->hasTable("user_navigation_metrics");

    }

}
