<?php

declare(strict_types=1);

namespace App\Services\System\Finance;

use App\Helpers\System\{Utilities};
use App\Models\System\Finance\{PaymentMethod, PaymentMethodVariant, Tax};
use DomainException;
use Illuminate\Support\{Collection};

final class CommercialDocumentSettlementService {
    public static function taxes(
        int $companyId,
        string $scope,
        float $baseAmount,
        int $userId,
        array $selectedTaxIds = [],
        array $selectedTaxQuantities = []
    ): Collection {

        return self::activeTaxCatalog($companyId, $scope, $selectedTaxIds)
            ->map(fn(Tax $tax) => self::taxLine($tax, $baseAmount, $userId, self::taxQuantity($tax, $selectedTaxQuantities)))
            ->values();

    }

    public static function saleTaxes(
        int $companyId,
        array $details,
        int $userId,
        array $selectedTaxIds = [],
        array $selectedTaxQuantities = []
    ): Collection {

        return self::activeTaxCatalog($companyId, "sale", $selectedTaxIds)
            ->map(fn(Tax $tax) => self::saleTaxLine($tax, $details, $userId, self::taxQuantity($tax, $selectedTaxQuantities)))
            ->values();

    }

    public static function payments(
        int $companyId,
        string $scope,
        float $total,
        array $selectedPayments,
        int $userId,
        bool $requireExactTotal = true
    ): Collection {

        if(empty($selectedPayments)) {

            if(!$requireExactTotal) {

                return collect();

            }

            $defaultMethod = PaymentMethod::query()
                ->whereIn("scope", [$scope, "both"])
                ->where("status", "active")
                ->orderByDesc("is_default")
                ->orderBy("name")
                ->first();

            if(!$defaultMethod) {

                return collect();

            }

            $selectedPayments = [[
                "payment_method_id" => $defaultMethod->id,
                "payment_method_variant_id" => null,
                "amount" => $total,
                "reference" => null,
                "note" => null,
            ]];

        }

        $methods = self::paymentCatalog($companyId, $scope, $selectedPayments)
            ->keyBy("id");

        $variants = self::paymentVariantCatalog($companyId, $selectedPayments)
            ->keyBy("id");

        $payments = collect($selectedPayments)
            ->map(fn($paymentData) => self::paymentLine($methods, $variants, $paymentData, $userId, $companyId))
            ->filter()
            ->values();

        $paid = Utilities::round((float) $payments->sum("amount"), null, $companyId);
        $documentTotal = Utilities::round($total, null, $companyId);
        $tolerance = self::decimalTolerance($companyId);

        if($requireExactTotal && abs($paid - $documentTotal) > $tolerance) {

            throw new DomainException("El total de los métodos de pago debe coincidir con el total del documento.");

        }

        if(!$requireExactTotal && $paid - $documentTotal > $tolerance) {

            throw new DomainException("El total pagado no puede superar el total del documento.");

        }

        return $payments;

    }

    private static function activeTaxCatalog(int $companyId, string $scope, array $selectedTaxIds = []): Collection {

        $selectedTaxIds = collect($selectedTaxIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return Tax::query()
            ->whereIn("scope", [$scope, "both"])
            ->where("status", "active")
            ->where(function($query) use ($selectedTaxIds) {

                $query->where("is_required", true);

                if(!empty($selectedTaxIds)) {

                    $query->orWhereIn("id", $selectedTaxIds);

                }

            })
            ->orderByDesc("is_default")
            ->orderBy("name")
            ->get();

    }

    private static function paymentCatalog(int $companyId, string $scope, array $selectedPayments): Collection {

        $ids = collect($selectedPayments)
            ->pluck("payment_method_id")
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if($ids->isEmpty()) {

            return collect();

        }

        $methods = PaymentMethod::query()
            ->whereIn("scope", [$scope, "both"])
            ->where("status", "active")
            ->whereIn("id", $ids)
            ->get();

        if($methods->count() !== $ids->count()) {

            throw new DomainException("Uno de los métodos de pago no está disponible para este documento.");

        }

        return $methods;

    }

    private static function paymentVariantCatalog(int $companyId, array $selectedPayments): Collection {

        $ids = collect($selectedPayments)
            ->pluck("payment_method_variant_id")
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if($ids->isEmpty()) {

            return collect();

        }

        $variants = PaymentMethodVariant::query()
            ->with("paymentMethod")
            ->where("status", "active")
            ->whereIn("id", $ids)
            ->get();

        if($variants->count() !== $ids->count()) {

            throw new DomainException("Una variante del método de pago no está disponible para este documento.");

        }

        return $variants;

    }

    private static function taxLine(Tax $tax, float $baseAmount, int $userId, int $quantity = 1): array {

        $companyId = app(\App\Services\System\Tenancy\TenantCompanyContext::class)->id();
        $rate = Utilities::round((float) $tax->rate, null, $companyId);
        $base = Utilities::round($baseAmount, null, $companyId);
        $calculationType = in_array($tax->calculation_type, ["percentage", "fixed"], true)
            ? $tax->calculation_type
            : "percentage";

        $operationType = in_array($tax->operation_type, ["addition", "subtraction"], true)
            ? $tax->operation_type
            : "addition";

        $amount = self::taxAmount($base, $rate, $calculationType, $operationType, $quantity, $companyId);

        return [
            "tax_id" => $tax->id,
            "name" => (string) $tax->name,
            "description" => $tax->description,
            "rate" => $rate,
            "calculation_type" => $calculationType,
            "operation_type" => $operationType,
            "is_required" => (bool) $tax->is_required,
            "quantity" => $calculationType === "fixed" ? $quantity : 1,
            "base_amount" => $base,
            "amount" => $amount,
            "status" => "active",
            "created_at" => now(),
            "created_by" => $userId,
        ];

    }

    private static function saleTaxLine(Tax $tax, array $details, int $userId, int $quantity = 1): array {

        $companyId = app(\App\Services\System\Tenancy\TenantCompanyContext::class)->id();
        $rate = Utilities::round((float) $tax->rate, null, $companyId);
        $calculationType = in_array($tax->calculation_type, ["percentage", "fixed"], true)
            ? $tax->calculation_type
            : "percentage";

        $operationType = in_array($tax->operation_type, ["addition", "subtraction"], true)
            ? $tax->operation_type
            : "addition";

        $isIgvTax = self::isIgvTax($tax);
        $base = 0.0;
        $amount = 0.0;
        $totalImpact = 0.0;

        if($calculationType === "fixed") {

            $amount = self::taxAmount(0, $rate, $calculationType, $operationType, $quantity, $companyId);
            $totalImpact = $amount;

        }else {

            foreach($details as $detail) {

                $lineTotal = Utilities::round((float) ($detail["quantity"] ?? 0) * (float) ($detail["price"] ?? 0), null, $companyId);

                if($lineTotal <= 0) {

                    continue;

                }

                if($isIgvTax && filter_var($detail["igv_exempt"] ?? false, FILTER_VALIDATE_BOOL)) {

                    continue;

                }

                $priceIncludesTax = filter_var($detail["price_includes_tax"] ?? true, FILTER_VALIDATE_BOOL);

                $taxIsIncluded = $priceIncludesTax && $operationType === "addition" && $rate > 0;

                if($taxIsIncluded) {

                    $lineBase = Utilities::round($lineTotal / (1 + ($rate / 100)), null, $companyId);
                    $lineAmount = Utilities::round($lineTotal - $lineBase, null, $companyId);
                    $base += $lineBase;
                    $amount += $lineAmount;

                    continue;

                }

                $lineAmount = self::taxAmount($lineTotal, $rate, $calculationType, $operationType, 1, $companyId);

                $base += $lineTotal;
                $amount += $lineAmount;
                $totalImpact += $lineAmount;

            }

        }

        return [
            "tax_id" => $tax->id,
            "name" => (string) $tax->name,
            "description" => $tax->description,
            "rate" => $rate,
            "calculation_type" => $calculationType,
            "operation_type" => $operationType,
            "is_required" => (bool) $tax->is_required,
            "quantity" => $calculationType === "fixed" ? $quantity : 1,
            "base_amount" => Utilities::round($base, null, $companyId),
            "amount" => Utilities::round($amount, null, $companyId),
            "_total_impact" => Utilities::round($totalImpact, null, $companyId),
            "status" => "active",
            "created_at" => now(),
            "created_by" => $userId,
        ];

    }

    private static function taxAmount(float $base, float $rate, string $calculationType, string $operationType, int $quantity = 1, ?int $companyId = null): float {

        $quantity = max(1, $quantity);

        $amount = match ($calculationType) {
            "fixed" => Utilities::round($rate * $quantity, null, $companyId),
            default => Utilities::round($base * ($rate / 100), null, $companyId)
        };

        if($operationType === "subtraction") {

            $amount *= -1;

        }

        return Utilities::round($amount, null, $companyId);

    }

    private static function isIgvTax(Tax $tax): bool {

        $code = strtoupper((string) ($tax->code ?? ""));
        $name = strtoupper((string) ($tax->name ?? ""));

        return str_contains($code, "IGV") || $name === "IGV";

    }

    private static function taxQuantity(Tax $tax, array $selectedTaxQuantities): int {

        if($tax->calculation_type !== "fixed") {

            return 1;

        }

        $minimum = max(0, (int) ($tax->min_apply_quantity ?? 0));

        $maximum = $tax->max_apply_quantity !== null ? max($minimum, (int) $tax->max_apply_quantity) : null;
        $quantity = max(1, $minimum, (int) ($selectedTaxQuantities[$tax->id] ?? 1));

        return $maximum !== null ? min($quantity, $maximum) : $quantity;

    }

    private static function paymentLine(Collection $methods, Collection $variants, array $paymentData, int $userId, int $companyId): ?array {

        $methodId = (int) ($paymentData["payment_method_id"] ?? 0);

        if($methodId <= 0) {

            return null;

        }

        $method = $methods->get($methodId);

        $variantId = (int) ($paymentData["payment_method_variant_id"] ?? 0);
        $variant = $variantId > 0 ? $variants->get($variantId) : null;
        $amount = Utilities::round((float) ($paymentData["amount"] ?? 0), null, $companyId);

        if($amount <= 0) {

            throw new DomainException("Cada método de pago debe tener un importe mayor que cero.");

        }

        if($variant && (int) $variant->payment_method_id !== (int) $method?->id) {

            throw new DomainException("La variante seleccionada no pertenece al método de pago indicado.");

        }

        $requiresReference = (bool) ($variant?->requires_reference ?? $method?->requires_reference);

        if($requiresReference && trim((string) ($paymentData["reference"] ?? "")) === "") {

            throw new DomainException("El método de pago {$method->name} requiere referencia.");

        }

        return [
            "payment_method_id" => $method?->id,
            "payment_method_variant_id" => $variant?->id,
            "name" => (string) ($paymentData["name"] ?? $variant?->name ?? $method?->name),
            "amount" => $amount,
            "reference" => $paymentData["reference"] ?? null,
            "note" => $paymentData["note"] ?? null,
            "status" => "active",
            "created_at" => now(),
            "created_by" => $userId,
        ];

    }

    private static function decimalTolerance(int $companyId): float {

        return 1 / (10 ** max(1, Utilities::decimalPrecision($companyId)));

    }
}
