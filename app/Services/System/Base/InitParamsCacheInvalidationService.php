<?php

declare(strict_types=1);

namespace App\Services\System\Base;

use App\Services\System\Assets\Assets\{AssetConfigService};
use App\Services\System\Assets\{AssetManagementConfigService};
use App\Services\System\Catalogs\Brands\{BrandConfigService};
use App\Services\System\Catalogs\Categories\{CategoryConfigService};
use App\Services\System\Catalogs\Products\{ProductConfigService};
use App\Services\System\Catalogs\Recipes\{RecipeConfigService};
use App\Services\System\Catalogs\Services\{ServiceConfigService};
use App\Services\System\Catalogs\Subscriptions\{SubscriptionConfigService};
use App\Services\System\Customers\Customers\{CustomerConfigService};
use App\Services\System\Customers\Tracking\{TrackingAttendanceConfigService, TrackingCustomerConfigService, TrackingSubscriptionConfigService};
use App\Services\System\Devices\BiometricDevices\{BiometricDeviceConfigService};
use App\Services\System\Essentials\{DashboardConfigService, ReportConfigService};
use App\Services\System\Finance\{CashRegisterConfigService};
use App\Services\System\Operations\{ServiceOperationConfigService};
use App\Services\System\Organizations\BookComplaints\{BookComplaintConfigService};
use App\Services\System\Organizations\Branches\{BranchConfigService};
use App\Services\System\Organizations\Companies\{CompanyConfigService};
use App\Services\System\Organizations\Roles\{RoleConfigService};
use App\Services\System\Organizations\Users\{UserAttendanceConfigService, UserConfigService};
use App\Services\System\Purchases\{PurchaseConfigService};
use App\Services\System\Sales\{SaleConfigService};
use App\Services\System\Warehouses\StockManagement\{StockManagementConfigService};
use InvalidArgumentException;

/**
 * Invalidates initParams caches according to shared domain dependencies.
 */
final class InitParamsCacheInvalidationService {
    public const ASSETS = "assets";

    public const BIOMETRIC_DEVICES = "biometric_devices";

    public const BRANCHES = "branches";

    public const BRANDS = "brands";

    public const CATEGORIES = "categories";

    public const CUSTOMERS = "customers";

    public const COMPANY_SETTINGS = "company_settings";

    public const CURRENCIES = "currencies";

    public const DOCUMENT_TYPES = "document_types";

    public const IDENTITY_DOCUMENTS = "identity_documents";

    public const ITEMS = "items";

    public const PAYMENT_METHODS = "payment_methods";

    public const TAXES = "taxes";

    public const USERS = "users";

    public const SUPPLIERS = "suppliers";

    public const ROLES = "roles";

    /**
     * Config services affected when a shared resource changes.
     *
     * @var array<string, array<class-string>>
     */
    private const DEPENDENCIES = [
        self::BRANDS => [
            BrandConfigService::class,
            ProductConfigService::class,
            ServiceOperationConfigService::class,
        ],
        self::CATEGORIES => [
            CategoryConfigService::class,
            ProductConfigService::class,
            ServiceConfigService::class,
            SubscriptionConfigService::class,
            ServiceOperationConfigService::class,
        ],
        self::ITEMS => [
            ProductConfigService::class,
            RecipeConfigService::class,
            ServiceConfigService::class,
            SubscriptionConfigService::class,
            SaleConfigService::class,
            StockManagementConfigService::class,
            PurchaseConfigService::class,
            ServiceOperationConfigService::class,
        ],
        self::CUSTOMERS => [
            CustomerConfigService::class,
            SaleConfigService::class,
            TrackingAttendanceConfigService::class,
            TrackingCustomerConfigService::class,
            TrackingSubscriptionConfigService::class,
            ServiceOperationConfigService::class,
        ],
        self::BRANCHES => [
            BranchConfigService::class,
            UserConfigService::class,
            CashRegisterConfigService::class,
            ProductConfigService::class,
            SaleConfigService::class,
            TrackingAttendanceConfigService::class,
            TrackingSubscriptionConfigService::class,
            BiometricDeviceConfigService::class,
            AssetManagementConfigService::class,
            StockManagementConfigService::class,
            PurchaseConfigService::class,
            ServiceOperationConfigService::class,
            UserAttendanceConfigService::class,
        ],
        self::ASSETS => [
            AssetConfigService::class,
            AssetManagementConfigService::class,
        ],
        self::USERS => [
            UserConfigService::class,
            AssetManagementConfigService::class,
            ServiceOperationConfigService::class,
            UserAttendanceConfigService::class,
        ],
        self::ROLES => [
            RoleConfigService::class,
            UserConfigService::class,
        ],
        self::BIOMETRIC_DEVICES => [
            BiometricDeviceConfigService::class,
            CustomerConfigService::class,
        ],
        self::SUPPLIERS => [
            PurchaseConfigService::class,
        ],
        self::IDENTITY_DOCUMENTS => [
            CompanyConfigService::class,
            CustomerConfigService::class,
            UserConfigService::class,
        ],
        self::DOCUMENT_TYPES => [
            BranchConfigService::class,
            SaleConfigService::class,
        ],
        self::CURRENCIES => [
            CompanyConfigService::class,
            ProductConfigService::class,
            RecipeConfigService::class,
            ServiceConfigService::class,
            SubscriptionConfigService::class,
            SaleConfigService::class,
            PurchaseConfigService::class,
        ],
        self::TAXES => [
            SaleConfigService::class,
            PurchaseConfigService::class,
        ],
        self::PAYMENT_METHODS => [
            CashRegisterConfigService::class,
            SaleConfigService::class,
            PurchaseConfigService::class,
        ],
        self::COMPANY_SETTINGS => [
            AssetConfigService::class,
            AssetManagementConfigService::class,
            BiometricDeviceConfigService::class,
            BookComplaintConfigService::class,
            BranchConfigService::class,
            BrandConfigService::class,
            CashRegisterConfigService::class,
            CategoryConfigService::class,
            CompanyConfigService::class,
            CustomerConfigService::class,
            DashboardConfigService::class,
            ProductConfigService::class,
            PurchaseConfigService::class,
            RecipeConfigService::class,
            ReportConfigService::class,
            RoleConfigService::class,
            SaleConfigService::class,
            ServiceConfigService::class,
            ServiceOperationConfigService::class,
            StockManagementConfigService::class,
            SubscriptionConfigService::class,
            TrackingAttendanceConfigService::class,
            TrackingCustomerConfigService::class,
            TrackingSubscriptionConfigService::class,
            UserAttendanceConfigService::class,
            UserConfigService::class,
        ],
    ];

    public static function invalidate(string $resource): void {

        $services = self::DEPENDENCIES[$resource] ?? null;

        if($services === null) {

            throw new InvalidArgumentException("Unknown initParams cache resource: {$resource}");

        }

        foreach(array_unique($services) as $service) {

            $service::clearAllCache();

        }

    }
}
