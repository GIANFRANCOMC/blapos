<?php

declare(strict_types=1);

namespace App\Services\System\Base;

use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Services\System\Tenancy\{TenantContext};
use Illuminate\Support\Facades\{Cache};
use InvalidArgumentException;
use stdClass;

/**
 * Shared cache contract for System module initialization parameters.
 */
abstract class BaseConfigService {
    protected const CACHE_TTL = 3600;

    protected const USER_SCOPED_CACHE = false;

    abstract protected static function getCachePrefix(): string;

    abstract protected static function buildConfig(string $page, ?int $userId = null): stdClass;

    protected static function usesUserScopedCache(): bool {

        return static::USER_SCOPED_CACHE;

    }

    /**
     * Pages whose configuration can be cached by the module.
     *
     * @return array<int, string>
     */
    protected static function cachePages(): array {

        return ["main"];

    }

    public static function getInitParams(string $page, int $userId): stdClass {

        $page = self::normalizePage($page);
        if(static::usesUserScopedCache()) {

            static::registerUserCacheScope($userId);

        }

        return Cache::remember(
            static::cacheKey($page, $userId),
            static::CACHE_TTL,
            fn() => static::createInitParams(static::buildConfig($page, $userId))
        );

    }

    public static function clearCache(?string $page = null): void {

        $pages = $page === null
            ? static::cachePages()
            : [self::normalizePage($page)];

        if(static::usesUserScopedCache()) {

            foreach(static::registeredUserIds() as $userId) {

                foreach(array_unique($pages) as $cachePage) {

                    Cache::forget(static::cacheKey($cachePage, $userId));

                }

            }

            return;

        }

        foreach(array_unique($pages) as $cachePage) {

            Cache::forget(static::cacheKey($cachePage));

        }

    }

    public static function clearAllCache(): void {

        static::clearCache();

    }

    public static function clearUserCache(int $userId, ?string $page = null): void {

        if(!static::usesUserScopedCache() || $userId <= 0) {

            return;

        }

        $pages = $page === null
            ? static::cachePages()
            : [self::normalizePage($page)];

        foreach(array_unique($pages) as $cachePage) {

            Cache::forget(static::cacheKey($cachePage, $userId));

        }

    }

    public static function cacheKey(string $page = "main", ?int $userId = null): string {

        $page = self::normalizePage($page);

        $cacheKey = sprintf(
            "%s:init_params:%s:page:%s",
            app(TenantContext::class)->cacheNamespace(),
            static::getCachePrefix(),
            $page
        );

        if(static::usesUserScopedCache()) {

            if(!$userId || $userId <= 0) {

                throw new InvalidArgumentException("User ID is required for user-scoped configuration cache.");

            }

            $cacheKey .= sprintf(":user:%d", $userId);

        }

        return $cacheKey;

    }

    public static function registerUserCacheScope(int $userId): void {

        if($userId <= 0) {

            throw new InvalidArgumentException("User ID must be greater than zero.");

        }

        $key = self::userIndexKey();

        $userIds = collect(Cache::get($key, []))
            ->push($userId)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        Cache::put($key, $userIds, static::CACHE_TTL);

    }

    public static function userIndexKey(): string {

        return app(TenantContext::class)->cacheNamespace().":init_params:user_index";

    }

    protected static function data(array $attributes = []): stdClass {

        return (object) $attributes;

    }

    private static function registeredUserIds(): array {

        return collect(Cache::get(self::userIndexKey(), []))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

    }

    protected static function internalCodePrefixes(): array {

        return CompanySettingService::group(
            CompanySettingService::INTERNAL_CODE_PREFIXES
        );

    }

    private static function createInitParams(stdClass $config): stdClass {

        $config->generalConfig = self::frontendGeneralConfig();

        return self::data([
            "config" => $config,
            "bool" => true,
        ]);

    }

    private static function frontendGeneralConfig(): stdClass {

        $numeric = CompanySettingService::group(
            CompanySettingService::NUMERIC_VALIDATION
        );

        return self::data([
            "forms" => [
                "inputs" => [
                    "round" => max(0, min(8, (int) ($numeric["decimal_precision"] ?? 3))),
                    "minValue" => (float) ($numeric["default_min_value"] ?? 0),
                    "maxValue" => (float) ($numeric["default_max_value"] ?? 999999999999.999),
                    "maxSize" => max(1, (int) ($numeric["max_file_size_kb"] ?? 4096)),
                ],
            ],
        ]);

    }

    private static function normalizePage(string $page): string {

        $page = strtolower(trim($page));
        $pages = static::cachePages();

        if($page === "") {

            return $pages[0] ?? "main";

        }

        return in_array($page, $pages, true)
            ? $page
            : ($pages[0] ?? "main");

    }

}
