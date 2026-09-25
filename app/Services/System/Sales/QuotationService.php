<?php

declare(strict_types=1);

namespace App\Services\System\Sales;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Sales\{QuotationHeader, QuotationItem, QuotationTax};
use App\Services\System\Finance\{CommercialDocumentSettlementService};
use DomainException;
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Str};

final class QuotationService {
    public static function query(int $companyId, array $filters = []): Builder {

        $query = QuotationHeader::query()
            ->with(["holder:id,name,document_number", "seller:id,name", "currency:id,code,sign", "branch:id,name"]);

        $word = trim((string) ($filters["word"] ?? ""));

        if($word !== "") {

            $query->where(function($query) use ($word) {

                $query->where("reference", "like", "%{$word}%")
                    ->orWhereHas("holder", fn($query) => $query->where("name", "like", "%{$word}%"));

            });

        }

        if(!empty($filters["status"])) {

            $query->where("status", $filters["status"]);

        }

        return $query->orderByDesc("id");

    }

    public static function create(int $companyId, int $userId, array $data): QuotationHeader {

        return DB::transaction(function() use ($companyId, $userId, $data) {

            $details = collect($data["details"] ?? []);

            if($details->isEmpty()) {

                throw new DomainException("Agrega al menos un detalle a la cotización.");

            }

            $itemIds = $details->pluck("item_id")->map(fn($id) => (int) $id)->unique()->values();

            $items = Item::query()
                ->whereIn("id", $itemIds)
                ->get()
                ->keyBy("id");

            if($items->count() !== $itemIds->count()) {

                throw new DomainException("Uno de los items no pertenece a la empresa.");

            }

            $normalizedDetails = $details->map(function($detail) use ($items) {

                $item = $items->get((int) $detail["item_id"]);
                $quantity = Utilities::round($detail["quantity"] ?? 1);
                $price = Utilities::round($detail["price"] ?? $item->price);

                return [
                    "item_id" => (int) $item->id,
                    "currency_id" => (int) ($detail["currency_id"] ?? $item->currency_id),
                    "name" => (string) ($detail["name"] ?? $item->name),
                    "type" => (string) $item->type,
                    "quantity" => $quantity,
                    "price" => $price,
                    "igv_exempt" => filter_var($detail["igv_exempt"] ?? $item->igv_exempt ?? false, FILTER_VALIDATE_BOOL),
                    "price_includes_tax" => filter_var($detail["igv_exempt"] ?? $item->igv_exempt ?? false, FILTER_VALIDATE_BOOL)
                        ? false
                        : filter_var($detail["price_includes_tax"] ?? $item->price_includes_tax ?? true, FILTER_VALIDATE_BOOL),
                    "total" => Utilities::round($quantity * $price),
                    "observation" => $detail["observation"] ?? null,
                ];

            })->values();

            $grossSubtotal = Utilities::round((float) $normalizedDetails->sum("total"));
            $taxLines = CommercialDocumentSettlementService::saleTaxes(
                $companyId,
                $normalizedDetails->all(),
                $userId,
                collect($data["taxes"] ?? [])->pluck("tax_id")->filter()->map(fn($id) => (int) $id)->all(),
                collect($data["taxes"] ?? [])->filter(fn($tax) => !empty($tax["tax_id"]))->mapWithKeys(fn($tax) => [(int) $tax["tax_id"] => (int) ($tax["quantity"] ?? 1)])->all()
            );

            $taxTotal = Utilities::round((float) $taxLines->sum("amount"));
            $taxImpactTotal = Utilities::round((float) $taxLines->sum("_total_impact"));
            $includedTaxTotal = Utilities::round($taxTotal - $taxImpactTotal);
            $subtotal = Utilities::round($grossSubtotal - $includedTaxTotal);
            $total = Utilities::round($grossSubtotal + $taxImpactTotal);

            $quotation = QuotationHeader::create([
                "branch_id" => $data["branch_id"] ?? null,
                "holder_id" => (int) $data["holder_id"],
                "seller_id" => $userId,
                "currency_id" => (int) $data["currency_id"],
                "reference" => self::generateReference($companyId),
                "issue_date" => $data["issue_date"],
                "valid_until" => $data["valid_until"] ?? null,
                "subtotal" => $subtotal,
                "tax" => $taxTotal,
                "total" => $total,
                "observation" => $data["observation"] ?? null,
                "status" => $data["status"] ?? "draft",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            QuotationItem::insert($normalizedDetails->map(fn($detail) => [
                "quotation_header_id" => $quotation->id,
                "created_at" => now(),
                "created_by" => $userId,
            ] + $detail)->all());

            if($taxLines->isNotEmpty()) {

                QuotationTax::insert($taxLines->map(function($tax) use ($userId, $quotation) {

                    unset($tax["_total_impact"]);

                    return [
                        "quotation_header_id" => $quotation->id,
                        "created_at" => now(),
                        "created_by" => $userId,
                    ] + $tax;

                })->all());

            }

            SaleConfigService::clearCache($companyId, "main");

            return self::find($companyId, $quotation->id);

        });

    }

    public static function find(int $companyId, int $quotationId): QuotationHeader {

        return QuotationHeader::query()
            ->with(["items.item", "taxes", "holder", "currency", "branch"])
            ->findOrFail($quotationId);

    }

    public static function saleDraft(int $companyId, int $quotationId): array {

        $quotation = self::find($companyId, $quotationId);

        if(!in_array($quotation->status, ["draft", "sent", "accepted"], true)) {

            throw new DomainException("La cotización no puede convertirse en venta.");

        }

        $items = Item::query()
            ->whereIn("id", $quotation->items->pluck("item_id"))
            ->get()
            ->keyBy("id");

        return [
            "quotation_header_id" => $quotation->id,
            "branch_id" => $quotation->branch_id,
            "holder_id" => $quotation->holder_id,
            "currency_id" => $quotation->currency_id,
            "observation" => "Venta generada desde {$quotation->reference}",
            "details" => $quotation->items->map(function(QuotationItem $detail) use ($items) {

                $item = $items->get((int) $detail->item_id);
                $price = Utilities::round((float) ($item?->price ?? $detail->price));

                return [
                    "item_id" => $detail->item_id,
                    "type" => $detail->type,
                    "currency_id" => $detail->currency_id,
                    "name" => $item?->name ?? $detail->name,
                    "quantity" => (float) $detail->quantity,
                    "price" => $price,
                    "igv_exempt" => (bool) ($item?->igv_exempt ?? $detail->igv_exempt),
                    "price_includes_tax" => (bool) ($item?->igv_exempt ?? $detail->igv_exempt)
                        ? false
                        : (bool) ($item?->price_includes_tax ?? $detail->price_includes_tax),
                    "observation" => $detail->observation,
                    "recalculated_from_quote" => Utilities::round((float) $detail->price) !== $price,
                ];

            })->all(),
        ];

    }

    public static function cancel(int $companyId, int $quotationId, int $userId): QuotationHeader {

        $quotation = self::find($companyId, $quotationId);

        if($quotation->status === "converted") {

            throw new DomainException("No puedes anular una cotización ya convertida en venta.");

        }

        $quotation->update([
            "status" => "canceled",
            "canceled_at" => now(),
            "canceled_by" => $userId,
            "updated_at" => now(),
            "updated_by" => $userId,
        ]);

        SaleConfigService::clearCache($companyId, "main");

        return $quotation->refresh();

    }

    private static function generateReference(int $companyId): string {

        do {

            $reference = "COT-".strtoupper(Str::random(10));

        } while(QuotationHeader::query()->where("reference", $reference)->exists());

        return $reference;

    }
}
