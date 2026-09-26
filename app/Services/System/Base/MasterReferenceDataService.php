<?php

declare(strict_types=1);

namespace App\Services\System\Base;

use App\Models\System\General\{Currency, IdentityDocumentType};
use App\Services\System\Tenancy\{TenantContext};
use Illuminate\Database\Eloquent\{Collection};
use Illuminate\Support\Facades\{Cache};

/**
 * Provides active tenant master data used by module initParams.
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

    public static function currencies(): Collection {

        return Cache::remember(
            self::cacheKey("currencies"),
            self::CACHE_TTL,
            fn() => Currency::query()
                ->where("status", "active")
                ->orderBy("code")
                ->get()
        );

    }

    public static function defaultIdentityDocuments(): Collection {

        return self::identityDocuments(self::DEFAULT_IDENTITY_DOCUMENT_CODES);

    }

    public static function companyIdentityDocuments(): Collection {

        return self::identityDocuments(self::COMPANY_IDENTITY_DOCUMENT_CODES);

    }

    public static function customerIdentityDocuments(): Collection {

        return self::identityDocuments(self::CUSTOMER_IDENTITY_DOCUMENT_CODES);

    }

    private static function identityDocuments(array $codes): Collection {

        return self::activeIdentityDocuments()
            ->whereIn("code", $codes)
            ->values();

    }

    public static function clearCache(): void {

        Cache::forget(self::cacheKey("currencies"));
        Cache::forget(self::cacheKey("identity_documents"));

    }

    private static function activeIdentityDocuments(): Collection {

        return Cache::remember(
            self::cacheKey("identity_documents"),
            self::CACHE_TTL,
            fn() => IdentityDocumentType::query()
                ->where("status", "active")
                ->orderBy("name")
                ->get()
        );

    }

    private static function cacheKey(string $name): string {

        return app(TenantContext::class)->cacheNamespace().":master_reference:{$name}:active";

    }
}
