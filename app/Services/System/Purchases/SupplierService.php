<?php

declare(strict_types=1);

namespace App\Services\System\Purchases;

use App\Helpers\System\{Numbers};
use App\Models\System\Purchases\{PurchaseHeader, Supplier, SupplierBankAccount, SupplierContact};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Arr};

final class SupplierService {

    public static function paginate(string $word, int $perPage): LengthAwarePaginator {

        $query = Supplier::query()
            ->with(["contacts", "bankAccounts"])
            ->withCount(["purchases"]);

        $word = trim($word);

        if($word !== "") {

            $query->where(function($query) use ($word) {

                $query->where("name", "like", "%{$word}%")
                    ->orWhere("document_number", "like", "%{$word}%")
                    ->orWhere("contact_name", "like", "%{$word}%");

            });

        }

        $page = $query->orderBy("name")->paginate($perPage);
        $totals = PurchaseHeader::query()
            ->join("currencies", "currencies.id", "=", "purchase_headers.currency_id")
            ->whereIn("purchase_headers.supplier_id", $page->getCollection()->modelKeys())
            ->select("purchase_headers.supplier_id", "currencies.id as currency_id", "currencies.code", "currencies.sign")
            ->selectRaw("SUM(purchase_headers.total) AS purchased_total")
            ->groupBy("purchase_headers.supplier_id", "currencies.id", "currencies.code", "currencies.sign")
            ->orderBy("currencies.code")
            ->get()
            ->groupBy("supplier_id");

        return $page->through(function(Supplier $supplier) use ($totals) {

            $supplier->setAttribute("purchase_amounts", $totals->get($supplier->id, collect())->map(fn($row) => [
                "currency_id" => (int) $row->currency_id,
                "code" => $row->code,
                "sign" => $row->sign,
                "total" => Numbers::storage($row->purchased_total),
            ])->values());

            return $supplier;

        });

    }

    public static function create(int $userId, array $data): Supplier {

        return DB::transaction(function() use ($userId, $data) {

            $supplier = Supplier::create([
                ...Arr::except($data, ["contacts", "bank_accounts"]),
                "created_at" => now(),
                "created_by" => $userId,
            ]);
            self::syncRelated($supplier, $data);

            return $supplier->load(["contacts", "bankAccounts"]);

        });

    }

    public static function update(
        int $supplierId,
        int $userId,
        array $data
    ): Supplier {

        return DB::transaction(function() use ($supplierId, $userId, $data) {

            $supplier = Supplier::query()
                ->lockForUpdate()
                ->findOrFail($supplierId);

            $supplier->update([
                ...Arr::except($data, ["contacts", "bank_accounts"]),
                "updated_at" => now(),
                "updated_by" => $userId,
            ]);
            self::syncRelated($supplier, $data);

            return $supplier->fresh(["contacts", "bankAccounts"]);

        });

    }

    private static function syncRelated(Supplier $supplier, array $data): void {

        if(array_key_exists("contacts", $data)) {

            SupplierContact::query()->where("supplier_id", $supplier->id)->delete();
            $primaryAssigned = false;

            foreach($data["contacts"] ?? [] as $index => $contact) {

                $isPrimary = !$primaryAssigned && (bool) ($contact["is_primary"] ?? $index === 0);
                $primaryAssigned = $primaryAssigned || $isPrimary;
                SupplierContact::create([
                    ...$contact,
                    "supplier_id" => $supplier->id,
                    "is_primary" => $isPrimary,
                    "status" => "active",
                ]);

            }

        }

        if(array_key_exists("bank_accounts", $data)) {

            SupplierBankAccount::query()->where("supplier_id", $supplier->id)->delete();
            $primaryAssigned = false;

            foreach($data["bank_accounts"] ?? [] as $index => $account) {

                $isPrimary = !$primaryAssigned && (bool) ($account["is_primary"] ?? $index === 0);
                $primaryAssigned = $primaryAssigned || $isPrimary;
                SupplierBankAccount::create([
                    ...$account,
                    "supplier_id" => $supplier->id,
                    "is_primary" => $isPrimary,
                    "status" => "active",
                ]);

            }

        }

    }

}
