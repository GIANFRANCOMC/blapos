<?php

declare(strict_types=1);

namespace App\Services\System\Purchases;

use App\Models\System\Purchases\{Supplier, SupplierBankAccount, SupplierContact};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Arr};

final class SupplierService {
    public static function query(string $word = "") {

        $query = Supplier::query()
            ->with(["contacts", "bankAccounts"])
            ->withCount(["purchases"])
            ->withSum("purchases as purchased_total", "total");

        $word = trim($word);

        if($word !== "") {

            $query->where(function($query) use ($word) {

                $query->where("name", "like", "%{$word}%")
                    ->orWhere("document_number", "like", "%{$word}%")
                    ->orWhere("contact_name", "like", "%{$word}%");

            });

        }

        return $query->orderBy("name");

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
