<?php

declare(strict_types=1);

namespace App\Services\System\Sales;

use App\Helpers\System\{Numbers};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Sales\{QuotationHeader, QuotationItem, QuotationTax};
use App\Services\System\Finance\{CommercialDocumentSettlementService};
use DomainException;
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Str};

final class QuotationService {

    public static function query(array $filters = []): Builder {

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

    public static function create(int $userId, array $data): QuotationHeader {

        return DB::transaction(function() use ($userId, $data) {

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
                $quantity = Numbers::round($detail["quantity"] ?? 1);
                $price = Numbers::round($detail["price"] ?? $item->price);

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
                    "total" => Numbers::lineTotal($quantity, $price),
                    "observation" => $detail["observation"] ?? null,
                ];

            })->values();

            $grossSubtotal = Numbers::sum($normalizedDetails->pluck("total"));
            $taxLines = CommercialDocumentSettlementService::saleTaxes(
                $normalizedDetails->all(),
                $userId,
                collect($data["taxes"] ?? [])->pluck("tax_id")->filter()->map(fn($id) => (int) $id)->all(),
                collect($data["taxes"] ?? [])->filter(fn($tax) => !empty($tax["tax_id"]))->mapWithKeys(fn($tax) => [(int) $tax["tax_id"] => (int) ($tax["quantity"] ?? 1)])->all()
            );

            $taxTotal = Numbers::sum($taxLines->pluck("amount"));
            $taxImpactTotal = Numbers::sum($taxLines->pluck("_total_impact"));
            $includedTaxTotal = Numbers::subtract($taxTotal, $taxImpactTotal);
            $subtotal = Numbers::subtract($grossSubtotal, $includedTaxTotal);
            $total = Numbers::sum([$grossSubtotal, $taxImpactTotal]);

            $quotation = QuotationHeader::create([
                "branch_id" => $data["branch_id"] ?? null,
                "holder_id" => (int) $data["holder_id"],
                "seller_id" => $userId,
                "currency_id" => (int) $data["currency_id"],
                "reference" => self::generateReference(),
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
            ] + Numbers::storageRow($detail, ["quantity", "price", "total"]))->all());

            if($taxLines->isNotEmpty()) {

                QuotationTax::insert($taxLines->map(function($tax) use ($userId, $quotation) {

                    unset($tax["_total_impact"]);

                    return [
                        "quotation_header_id" => $quotation->id,
                        "created_at" => now(),
                        "created_by" => $userId,
                    ] + Numbers::storageRow($tax, ["rate", "base_amount", "amount"]);

                })->all());

            }

            SaleConfigService::clearCache("main");

            return self::find($quotation->id);

        });

    }

    public static function find(int $quotationId): QuotationHeader {

        return QuotationHeader::query()
            ->with(["items.item", "taxes", "holder", "currency", "branch"])
            ->findOrFail($quotationId);

    }

    public static function saleDraft(int $quotationId): array {

        $quotation = self::find($quotationId);

        if(!in_array($quotation->status, ["draft", "sent", "accepted"], true)) {

            throw new DomainException("La cotización no puede convertirse en venta.");

        }

        $items = Item::query()
            ->whereIn("id", $quotation->items->pluck("item_id"))
            ->with(["currency", "brand", "categoryItems.category", "warehouseItems.warehouse"])
            ->get()
            ->keyBy("id");

        $quotation->holder?->load("identityDocumentType");

        return [
            "quotation_header_id" => $quotation->id,
            "branch_id" => $quotation->branch_id,
            "holder_id" => $quotation->holder_id,
            "customer" => $quotation->holder,
            "currency_id" => $quotation->currency_id,
            "observation" => "Venta generada desde {$quotation->reference}",
            "details" => $quotation->items->map(function(QuotationItem $detail) use ($items) {

                $item = $items->get((int) $detail->item_id);
                $price = Numbers::round(($item?->price ?? $detail->price));

                return [
                    "item_id" => $detail->item_id,
                    "item" => $item,
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
                    "recalculated_from_quote" => Numbers::round($detail->price) !== $price,
                ];

            })->all(),
        ];

    }

    public static function cancel(int $quotationId, int $userId): QuotationHeader {

        $quotation = self::find($quotationId);

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

        SaleConfigService::clearCache("main");

        return $quotation->refresh();

    }

    private static function generateReference(): string {

        do {

            $reference = "COT-".strtoupper(Str::random(10));

        } while(QuotationHeader::query()->where("reference", $reference)->exists());

        return $reference;

    }

}
