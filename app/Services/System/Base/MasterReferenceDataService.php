<?php

declare(strict_types=1);

namespace App\Services\System\Base;

use App\Models\System\General\{Currency, IdentityDocumentType};
use App\Services\System\Tenancy\{TenantContext};
use Illuminate\Database\Eloquent\{Collection};
use Illuminate\Support\Facades\{Cache};

/**
 * Provides active company-scoped master data used by module initParams.
 */
final class MasterReferenceDataService {
    private const CACHE_TTL = 21600;

    private const DEFAULT_IDENTITY_DOCUMENT_CODES = [
        "doc.trib.no.dom.sin.ruc",
        "dni",
    ];

    private const COMPANY_IDENTITY_DOCUMENT_CODES = [
        "doc.trib.no.dom.sin.ruc",
        "ruc",
    ];

    private const CUSTOMER_IDENTITY_DOCUMENT_CODES = [
        "doc.trib.no.dom.sin.ruc",
        "dni",
        "ruc",
    ];

    public static function currencies(int $companyId): Collection {

        return Cache::remember(
            self::cacheKey($companyId, "currencies"),
            self::CACHE_TTL,
            fn() => Currency::query()
                ->where("status", "active")
                ->orderBy("code")
                ->get()
        );

    }

    public static function defaultIdentityDocuments(int $companyId): Collection {

        return self::identityDocuments($companyId, self::DEFAULT_IDENTITY_DOCUMENT_CODES);

    }

    public static function companyIdentityDocuments(int $companyId): Collection {

        return self::identityDocuments($companyId, self::COMPANY_IDENTITY_DOCUMENT_CODES);

    }

    public static function customerIdentityDocuments(int $companyId): Collection {

        return self::identityDocuments($companyId, self::CUSTOMER_IDENTITY_DOCUMENT_CODES);

    }

    private static function identityDocuments(int $companyId, array $codes): Collection {

        return self::activeIdentityDocuments($companyId)
            ->whereIn("code", $codes)
            ->values();

    }

    public static function clearCache(int $companyId): void {

        Cache::forget(self::cacheKey($companyId, "currencies"));
        Cache::forget(self::cacheKey($companyId, "identity_documents"));

    }

    private static function activeIdentityDocuments(int $companyId): Collection {

        return Cache::remember(
            self::cacheKey($companyId, "identity_documents"),
            self::CACHE_TTL,
            fn() => IdentityDocumentType::query()
                ->where("status", "active")
                ->orderBy("name")
                ->get()
        );

    }

    private static function cacheKey(int $companyId, string $name): string {

        return app(TenantContext::class)->cacheNamespace().":master_reference:{$name}:active";

    }
}
