<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Companies;

use App\Models\System\Organizations\{CompanySetting};
use App\Services\System\Tenancy\{TenantContext};
use Illuminate\Support\Facades\{Schema};

final class CompanySettingService {
    public const INTERNAL_CODE_PREFIXES = "internal_code_prefixes";

    public const INVENTORY_POLICIES = "inventory";

    public const CUSTOMER_ATTENDANCE = "customer_attendance";

    public const SUBSCRIPTIONS = "subscriptions";

    public const CASH = "cash";

    public const SALES = "sales";

    public const PURCHASES = "purchases";

    public const EXTERNAL_API = "external_api";

    public const LOYALTY = "loyalty";

    public const REPORTS = "reports";

    public const NUMERIC_VALIDATION = "numeric_validation";

    private const DEFAULT_INTERNAL_CODE_PREFIXES = [
        "product" => "PRO",
        "service" => "SER",
        "subscription" => "MEM",
        "brand" => "MAR",
        "category" => "CAT",
        "branch" => "SUC",
        "asset" => "ACT",
    ];

    private const DEFAULT_INVENTORY_POLICIES = [
        "allow_negative_stock_on_sale" => false,
        "restore_stock_on_sale_cancellation" => false,
        "restore_stock_on_purchase_cancellation" => false,
        "valuation_method" => "weighted_average",
        "stock_alert_email_enabled" => false,
        "stock_alert_email_to" => null,
    ];

    private const DEFAULT_CUSTOMER_ATTENDANCE = [
        "daily_limit_scope" => "branch",
        "biometric_duplicate_tolerance_seconds" => 10,
        "allow_automatic_checkout" => false,
        "max_active_hours" => 20,
        "auto_close_stale_enabled" => true,
        "auto_close_after_time" => "01:00",
        "auto_close_end_time" => "23:50",
        "retention_months" => 5,
    ];

    private const DEFAULT_SUBSCRIPTIONS = [
        "overlap_policy" => "block",
        "send_welcome_email_on_sale" => true,
    ];

    private const DEFAULT_CASH = [
        "require_open_session_on_sale" => false,
    ];

    private const DEFAULT_SALES = [
        "default_payment_modality" => "paid_now",
        "installment_extra_percentage" => 0,
    ];

    private const DEFAULT_PURCHASES = [
        "default_payment_modality" => "paid_now",
        "installment_extra_percentage" => 0,
    ];

    private const DEFAULT_EXTERNAL_API = [
        "document_lookup_monthly_warning_threshold" => 80,
    ];

    private const DEFAULT_LOYALTY = [
        "enabled" => false,
        "reverse_points_on_sale_cancellation" => true,
    ];

    private const DEFAULT_REPORTS = [
        "sale_share_ttl_minutes" => 4320,
    ];

    private const DEFAULT_NUMERIC_VALIDATION = [
        "decimal_precision" => 3,
        "default_min_value" => 0,
        "default_max_value" => 999999999999.999,
        "max_file_size_kb" => 4096,
    ];

    private static array $groupCache = [];

    public static function group(int $companyId, string $group): array {

        $cacheKey = self::cachePrefix($companyId).$group;

        if(array_key_exists($cacheKey, self::$groupCache)) {

            return self::$groupCache[$cacheKey];

        }

        $values = match ($group) {
            self::INTERNAL_CODE_PREFIXES => self::DEFAULT_INTERNAL_CODE_PREFIXES,
            self::INVENTORY_POLICIES => self::DEFAULT_INVENTORY_POLICIES,
            self::CUSTOMER_ATTENDANCE => self::DEFAULT_CUSTOMER_ATTENDANCE,
            self::SUBSCRIPTIONS => self::DEFAULT_SUBSCRIPTIONS,
            self::CASH => self::DEFAULT_CASH,
            self::SALES => self::DEFAULT_SALES,
            self::PURCHASES => self::DEFAULT_PURCHASES,
            self::EXTERNAL_API => self::DEFAULT_EXTERNAL_API,
            self::LOYALTY => self::DEFAULT_LOYALTY,
            self::REPORTS => self::DEFAULT_REPORTS,
            self::NUMERIC_VALIDATION => self::DEFAULT_NUMERIC_VALIDATION,
            default => []
        };

        if(!Schema::hasTable("company_settings")) {

            return self::$groupCache[$cacheKey] = $values;

        }

        $settings = CompanySetting::query()
            ->where("company_id", $companyId)
            ->where("group", $group)
            ->where("status", "active")
            ->orderBy("id")
            ->get(["key", "value", "value_type"]);

        foreach($settings as $setting) {

            $values[$setting->key] = self::castValue($setting->value, $setting->value_type);

        }

        return self::$groupCache[$cacheKey] = $values;

    }

    public static function value(int $companyId, string $group, string $key, mixed $default = null): mixed {

        $values = self::group($companyId, $group);

        return array_key_exists($key, $values) ? $values[$key] : $default;

    }

    public static function clearCache(?int $companyId = null): void {

        if($companyId === null) {

            self::$groupCache = [];

            return;

        }

        $cachePrefix = self::cachePrefix($companyId);

        foreach(array_keys(self::$groupCache) as $cacheKey) {

            if(str_starts_with($cacheKey, $cachePrefix)) {

                unset(self::$groupCache[$cacheKey]);

            }

        }

    }

    private static function cachePrefix(int $companyId): string {

        return app(TenantContext::class)->cacheNamespace().":{$companyId}:";

    }

    private static function castValue(?string $value, string $type): mixed {

        return match ($type) {
            "boolean" => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            "integer" => $value === null ? null : (int) $value,
            "decimal" => $value === null ? null : (float) $value,
            "json" => $value === null ? null : json_decode($value, true),
            default => $value
        };

    }
}
