<?php

declare(strict_types=1);

namespace App\Services\System\General;

use App\Models\System\Finance\{PaymentMethod, PaymentMethodVariant, Tax};
use App\Models\System\General\{Currency, DocumentType, IdentityDocumentType};
use App\Models\System\Organizations\{CompanySetting};
use App\Services\System\Base\{InitParamsCacheInvalidationService, MasterReferenceDataService};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Services\System\Tenancy\{TenantStoragePath};
use DomainException;
use Illuminate\Database\Eloquent\{Model};
use Illuminate\Http\{UploadedFile};
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\{Str};

final class MasterDataService {
    private const DEFINITIONS = [
        "identity-documents" => ["model" => IdentityDocumentType::class],
        "document-types" => ["model" => DocumentType::class],
        "currencies" => ["model" => Currency::class],
        "taxes" => ["model" => Tax::class],
        "payment-methods" => ["model" => PaymentMethod::class],
        "payment-method-variants" => ["model" => PaymentMethodVariant::class],
        "company-settings" => ["model" => CompanySetting::class],
    ];

    public static function list(int $companyId, string $resource) {

        $definition = self::definition($resource);

        return $definition["model"]::query()
            ->orderBy(self::orderColumn($resource))
            ->get();

    }

    public static function save(
        int $companyId,
        int $userId,
        string $resource,
        array $data,
        ?int $id = null
    ): Model {

        $definition = self::definition($resource);
        $newImagePath = null;
        $obsoleteImagePath = null;

        try {

            $record = DB::transaction(function() use (
                $companyId,
                $userId,
                $resource,
                $definition,
                $data,
                $id,
                &$newImagePath,
                &$obsoleteImagePath
            ) {

                $record = $id
                    ? $definition["model"]::query()->findOrFail($id)
                    : new $definition["model"]();

                $duplicateQuery = $definition["model"]::query();

                foreach(self::uniqueKey($resource, $data) as $column => $value) {

                    $duplicateQuery->where($column, $value);

                }

                $duplicate = $duplicateQuery
                    ->when($id, fn($query) => $query->where("id", "!=", $id))
                    ->exists();

                if($duplicate) {

                    throw new DomainException("Ya existe un registro equivalente en la empresa.");

                }

                if(($data["status"] ?? $record->status) === "inactive" && $id) {

                    self::assertCanDeactivate($companyId, $resource, $id);

                }

                $allowed = match ($resource) {
                    "identity-documents" => ["code", "name", "is_searchable", "min_length", "max_length", "status"],
                    "currencies" => ["code", "sign", "singular_name", "plural_name", "status"],
                    "taxes" => [
                        "code", "name", "description", "rate", "calculation_type", "operation_type",
                        "min_apply_quantity", "max_apply_quantity", "scope", "is_required", "is_default", "status",
                    ],
                    "payment-methods" => [
                        "code", "name", "category", "sunat_code", "description", "image_path", "scope",
                        "requires_reference", "supports_variants", "allows_partial_payment", "is_default", "status",
                    ],
                    "payment-method-variants" => [
                        "payment_method_id", "code", "name", "sunat_code", "image_path", "description",
                        "requires_reference", "is_default", "status",
                    ],
                    "company-settings" => ["group", "key", "value", "description", "value_type", "status"],
                    default => ["code", "name", "status"]
                };

                if($resource === "taxes" && ($data["calculation_type"] ?? null) === "percentage") {

                    $data["min_apply_quantity"] = null;
                    $data["max_apply_quantity"] = null;

                }

                if($resource === "company-settings") {

                    if(preg_match(
                        "/(?:secret|password|token|credential|api[_-]?key|private[_-]?key)/",
                        strtolower((string) $data["key"])
                    )) {

                        throw new DomainException("Los secretos y credenciales deben configurarse por entorno, no en company_settings.");

                    }

                    $data["value"] = self::normalizeSettingValue(
                        $data["value"] ?? null,
                        (string) $data["value_type"]
                    );

                }

                if(in_array($resource, ["payment-methods", "payment-method-variants"], true) && ($data["image"] ?? null) instanceof UploadedFile) {

                    $obsoleteImagePath = $record->image_path;
                    $newImagePath = $data["image"]->storeAs(
                        TenantStoragePath::for("finance/{$resource}"),
                        Str::uuid()->toString().".".$data["image"]->guessExtension(),
                        "public"
                    );

                    $data["image_path"] = $newImagePath;

                }

                unset($data["image"]);

                $record->fill(collect($data)->only($allowed)->all());
                $record->{$id ? "updated_by" : "created_by"} = $userId;
                $record->save();

                if($resource === "company-settings") {

                    CompanySettingService::clearCache($companyId);

                }

                MasterReferenceDataService::clearCache($companyId);
                InitParamsCacheInvalidationService::invalidate(
                    self::invalidationResource($resource),
                    $companyId
                );

                return $record->fresh();

            });

        }catch(\Throwable $exception) {

            if($newImagePath) {

                Storage::disk("public")->delete($newImagePath);

            }

            throw $exception;

        }

        if($obsoleteImagePath && $obsoleteImagePath !== $newImagePath) {

            Storage::disk("public")->delete($obsoleteImagePath);

        }

        return $record;

    }

    private static function assertCanDeactivate(int $companyId, string $resource, int $id): void {

        $references = match ($resource) {
            "identity-documents" => [
                ["companies", "identity_document_type_id"],
                ["customers", "identity_document_type_id"],
                ["users", "identity_document_type_id"],
            ],
            "document-types" => [["series", "document_type_id"]],
            "currencies" => [
                ["companies", "currency_id"],
                ["items", "currency_id"],
                ["sales_header", "currency_id"],
                ["purchase_headers", "currency_id"],
            ],
            "payment-methods" => [
                ["sale_payments", "payment_method_id"],
                ["purchase_payments", "payment_method_id"],
                ["cash_movements", "payment_method_id"],
            ],
            "payment-method-variants" => [
                ["sale_payments", "payment_method_variant_id"],
                ["purchase_payments", "payment_method_variant_id"],
                ["sale_receivable_payments", "payment_method_variant_id"],
                ["purchase_payable_payments", "payment_method_variant_id"],
            ],
            default => []
        };

        foreach($references as [$table, $column]) {

            if(DB::table($table)
                ->where($column, $id)
                ->when($table === "companies", fn($query) => $query->where("id", $companyId))
                ->exists()) {

                throw new DomainException("No se puede inactivar el registro porque está siendo utilizado.");

            }

        }

    }

    private static function definition(string $resource): array {

        return self::DEFINITIONS[$resource]
            ?? throw new DomainException("El maestro solicitado no está disponible.");

    }

    private static function orderColumn(string $resource): string {

        return match ($resource) {
            "currencies" => "code",
            "company-settings" => "group",
            default => "name"
        };

    }

    private static function uniqueKey(string $resource, array $data): array {

        return match ($resource) {
            "taxes", "payment-methods" => [
                "code" => $data["code"],
                "scope" => $data["scope"],
            ],
            "payment-method-variants" => [
                "payment_method_id" => $data["payment_method_id"],
                "code" => $data["code"],
            ],
            "company-settings" => [
                "group" => $data["group"],
                "key" => $data["key"],
            ],
            default => ["code" => $data["code"]]
        };

    }

    private static function invalidationResource(string $resource): string {

        return match ($resource) {
            "identity-documents" => InitParamsCacheInvalidationService::IDENTITY_DOCUMENTS,
            "document-types" => InitParamsCacheInvalidationService::DOCUMENT_TYPES,
            "currencies" => InitParamsCacheInvalidationService::CURRENCIES,
            "taxes" => InitParamsCacheInvalidationService::TAXES,
            "payment-methods" => InitParamsCacheInvalidationService::PAYMENT_METHODS,
            "payment-method-variants" => InitParamsCacheInvalidationService::PAYMENT_METHODS,
            "company-settings" => InitParamsCacheInvalidationService::COMPANY_SETTINGS,
            default => throw new DomainException("El maestro solicitado no tiene política de caché.")
        };

    }

    private static function normalizeSettingValue(mixed $value, string $type): ?string {

        if($value === null || $value === "") {

            return null;

        }

        return match ($type) {
            "string" => is_scalar($value)
                ? (string) $value
                : throw new DomainException("La configuración debe contener texto."),
            "boolean" => ($boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) !== null
                ? ($boolean ? "true" : "false")
                : throw new DomainException("La configuración debe ser verdadera o falsa."),
            "integer" => filter_var($value, FILTER_VALIDATE_INT) !== false
                ? (string) (int) $value
                : throw new DomainException("La configuración debe ser un número entero."),
            "decimal" => is_numeric($value)
                ? (string) $value
                : throw new DomainException("La configuración debe ser un número decimal."),
            "json" => self::normalizeJsonValue($value),
            default => throw new DomainException("El tipo de configuración no es válido.")
        };

    }

    private static function normalizeJsonValue(mixed $value): string {

        try {

            if(is_string($value)) {

                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            }

            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        }catch(\JsonException) {

            throw new DomainException("La configuración debe contener JSON válido.");

        }

    }
}
