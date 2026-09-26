<?php

declare(strict_types=1);

namespace App\Services\System\Assets;

use App\Helpers\System\{Utilities};
use App\Models\System\Assets\{AssetAssignment, AssetAssignmentLog, BranchAsset};
use App\Models\System\Organizations\{Branch};
use Illuminate\Pagination\{LengthAwarePaginator};
use Illuminate\Support\Facades\{DB};

/**
 * Service class for managing Asset Management operations
 * Handles business logic for asset assignments to branches and users
 */
class AssetManagementService {
    /**
     * Get paginated list of branch assets
     *
     * @param  int  $branchId Branch ID
     * @param  int  $perPage Items per page
     */
    public static function getBranchAssetsList(int $branchId, int $perPage): LengthAwarePaginator {

        $branchAssets = BranchAsset::where("branch_id", $branchId)
            ->with(["asset"])
            ->paginate($perPage);

        return $branchAssets;

    }

    /**
     * Assign assets to a branch
     *
     * @param  int  $branchId Branch ID
     * @param  array  $branchAssets Array of branch assets data
     * @param  int|null  $userId User ID performing the action
     * @return array Information about success and error counters
     */
    public static function assignAssetsToBranch(int $branchId, array $branchAssets, ?int $userId = null): array {

        $information = [
            "success" => [
                "counter" => 0,
                "data" => [],
            ],
            "error" => [
                "counter" => 0,
                "data" => [],
            ],
        ];

        $branch = Branch::where("id", $branchId)
            ->with("company")
            ->first();

        if(!$branch) {

            return $information;

        }

        $company = $branch->company;

        DB::transaction(function() use ($branchId, $branchAssets, $company, $userId, &$information) {

            foreach($branchAssets as $record) {

                $branchAsset = BranchAsset::where("branch_id", $branchId)
                    ->where("asset_id", $record["asset_id"])
                    ->first();

                if(!Utilities::isDefined($branchAsset)) {

                    $branchAsset = new BranchAsset();
                    $branchAsset->branch_id = $branchId;
                    $branchAsset->asset_id = $record["asset_id"];
                    $branchAsset->currency_id = $company->currency_id;
                    $branchAsset->quantity = $record["quantity"];
                    $branchAsset->acquisition_value = 0;
                    $branchAsset->acquisition_date = null;
                    $branchAsset->note = null;
                    $branchAsset->status = "active";
                    $branchAsset->created_at = now();
                    $branchAsset->created_by = $userId;
                    $branchAsset->save();

                    self::recordLog([
                        "action_by" => $userId,
                        "branch_id" => $branchId,
                        "to_branch_id" => $branchId,
                        "asset_id" => $record["asset_id"],
                        "action_type" => "assigned",
                        "quantity" => $record["quantity"],
                        "note" => "Activo asignado a la sucursal.",
                    ]);

                    $information["success"]["counter"]++;
                    $information["success"]["data"][] = ["asset_id" => $record["asset_id"]];

                }else {

                    if(Utilities::isDefined($branchAsset) && in_array($branchAsset->status, ["retired"])) {

                        $branchAsset->currency_id = $company->currency_id;
                        $branchAsset->quantity = $record["quantity"];
                        $branchAsset->acquisition_value = 0;
                        $branchAsset->acquisition_date = null;
                        $branchAsset->note = null;
                        $branchAsset->status = "active";
                        $branchAsset->updated_at = now();
                        $branchAsset->updated_by = $userId;
                        $branchAsset->save();

                        self::recordLog([
                            "action_by" => $userId,
                            "branch_id" => $branchId,
                            "to_branch_id" => $branchId,
                            "asset_id" => $record["asset_id"],
                            "action_type" => "assigned",
                            "quantity" => $record["quantity"],
                            "note" => "Activo reactivado en la sucursal.",
                        ]);

                        $information["success"]["counter"]++;
                        $information["success"]["data"][] = ["asset_id" => $record["asset_id"]];

                    }else {

                        $information["error"]["counter"]++;
                        $information["error"]["data"][] = ["asset_id" => $record["asset_id"]];

                    }

                }

            }

        });

        return $information;

    }

    /**
     * Unassign assets from a branch
     *
     * @param  int  $branchId Branch ID
     * @param  array  $branchAssets Array of branch assets data
     * @param  int|null  $userId User ID performing the action
     * @return array Information about success and error counters
     */
    public static function unassignAssetsFromBranch(int $branchId, array $branchAssets, ?int $userId = null): array {

        $information = [
            "success" => [
                "counter" => 0,
                "data" => [],
            ],
            "error" => [
                "counter" => 0,
                "data" => [],
            ],
        ];

        DB::transaction(function() use ($branchId, $branchAssets, $userId, &$information) {

            foreach($branchAssets as $record) {

                $branchAsset = BranchAsset::where("id", $record["id"])
                    ->where("branch_id", $branchId)
                    ->where("asset_id", $record["asset_id"])
                    ->whereIn("status", ["active", "maintenance"])
                    ->first();

                if(Utilities::isDefined($branchAsset)) {

                    $branchAsset->status = "retired";
                    $branchAsset->updated_at = now();
                    $branchAsset->updated_by = $userId;
                    $branchAsset->save();

                    self::recordLog([
                        "action_by" => $userId,
                        "branch_id" => $branchId,
                        "from_branch_id" => $branchId,
                        "asset_id" => $record["asset_id"],
                        "action_type" => "retired",
                        "quantity" => $branchAsset->quantity,
                        "note" => "Activo retirado de la sucursal.",
                    ]);

                    $information["success"]["counter"]++;
                    $information["success"]["data"][] = ["asset_id" => $record["asset_id"]];

                }else {

                    $information["error"]["counter"]++;
                    $information["error"]["data"][] = ["asset_id" => $record["asset_id"]];

                }

            }

        });

        return $information;

    }

    /**
     * Update asset in branch
     *
     * @param  int  $branchId Branch ID
     * @param  int  $branchAssetId Branch Asset ID
     * @param  int  $assetId Asset ID
     * @param  array  $data Asset data
     * @param  int|null  $userId User ID performing the action
     */
    public static function updateAssetInBranch(int $branchId, int $branchAssetId, int $assetId, array $data, ?int $userId = null): ?BranchAsset {

        $branchAsset = BranchAsset::where("id", $branchAssetId)
            ->where("branch_id", $branchId)
            ->where("asset_id", $assetId)
            ->whereIn("status", ["active", "maintenance"])
            ->first();

        if(!Utilities::isDefined($branchAsset)) {

            return null;

        }

        DB::transaction(function() use ($branchAsset, $data, $userId) {

            $branchAsset->quantity = $data["quantity"] ?? 0;
            $branchAsset->acquisition_value = $data["acquisition_value"] ?? 0;
            $branchAsset->acquisition_date = $data["acquisition_date"] ?? null;
            $branchAsset->note = $data["note"] ?? null;
            $branchAsset->updated_at = now();
            $branchAsset->updated_by = $userId;
            $branchAsset->save();

        });

        return $branchAsset->fresh();

    }

    /**
     * Get asset assignments for a branch asset
     *
     * @param  int  $branchId Branch ID
     * @param  int  $assetId Asset ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAssetAssignments(int $branchId, int $assetId) {

        return AssetAssignment::where("branch_id", $branchId)
            ->where("asset_id", $assetId)
            ->whereIn("status", ["active", "maintenance"])
            ->with(["user"])
            ->get();

    }

    /**
     * Assign asset to users
     *
     * @param  int  $branchId Branch ID
     * @param  int  $branchAssetId Branch Asset ID
     * @param  int  $assetId Asset ID
     * @param  array  $assignments Array of assignments data
     * @param  int|null  $userId User ID performing the action
     * @return array Information about success and error counters
     */
    public static function assignAssetToUsers(int $branchId, int $branchAssetId, int $assetId, array $assignments, ?int $userId = null): array {

        $information = [
            "success" => [
                "counter" => 0,
                "data" => [],
            ],
            "error" => [
                "counter" => 0,
                "data" => [],
            ],
        ];

        $branchAsset = BranchAsset::where("id", $branchAssetId)
            ->where("branch_id", $branchId)
            ->where("asset_id", $assetId)
            ->whereIn("status", ["active", "maintenance"])
            ->first();

        if(!Utilities::isDefined($branchAsset)) {

            return $information;

        }

        $assetQuantity = floatval($branchAsset->quantity);

        $totalQuantity = array_reduce($assignments, function($accumulator, $currentValue) {

            return $accumulator + floatval($currentValue["quantity"] ?? 0);

        }, 0);

        if($totalQuantity > $assetQuantity) {

            return $information;

        }

        DB::transaction(function() use ($branchAsset, $assignments, $userId, &$information) {

            foreach($assignments as $record) {

                $data = [
                    "user_id" => $record["user_id"],
                    "branch_id" => $branchAsset->branch_id,
                    "asset_id" => $branchAsset->asset_id,
                    "currency_id" => $branchAsset->currency_id,
                    "quantity" => $record["quantity"] ?? 0,
                    "acquisition_value" => 0,
                    "acquisition_date" => null,
                    "note" => null,
                    "status" => "active",
                    "updated_at" => now(),
                    "updated_by" => $userId,
                ];

                if(is_numeric($record["id"] ?? null)) {

                    $assetAssignment = AssetAssignment::query()
                        ->where("branch_id", $branchAsset->branch_id)
                        ->where("asset_id", $branchAsset->asset_id)
                        ->find((int) $record["id"]);

                    if($assetAssignment) {

                        $assetAssignment->fill($data)->save();

                    }

                }else {

                    $data["created_at"] = now();
                    $data["created_by"] = $userId;

                    $assetAssignment = AssetAssignment::create($data);

                }

                if(Utilities::isDefined($assetAssignment)) {

                    if($assetAssignment instanceof AssetAssignment) {

                        self::recordLog([
                            "action_by" => $userId,
                            "user_id" => $record["user_id"],
                            "branch_id" => $branchAsset->branch_id,
                            "to_user_id" => $record["user_id"],
                            "to_branch_id" => $branchAsset->branch_id,
                            "asset_id" => $branchAsset->asset_id,
                            "action_type" => "assigned",
                            "quantity" => $record["quantity"] ?? 0,
                            "note" => "Activo asignado al colaborador.",
                        ]);

                    }

                    $information["success"]["counter"]++;

                    $information["success"]["data"][] = ["asset_id" => $branchAsset->asset_id];

                }else {

                    $information["error"]["counter"]++;
                    $information["error"]["data"][] = ["asset_id" => $branchAsset->asset_id];

                }

            }

        });

        return $information;

    }

    /**
     * Unassign asset from users
     *
     * @param  int  $branchId Branch ID
     * @param  int  $branchAssetId Branch Asset ID
     * @param  int  $assetId Asset ID
     * @param  array  $assignments Array of assignments data
     * @param  int|null  $userId User ID performing the action
     * @return array Information about success and error counters
     */
    public static function unassignAssetFromUsers(int $branchId, int $branchAssetId, int $assetId, array $assignments, ?int $userId = null): array {

        $information = [
            "success" => [
                "counter" => 0,
                "data" => [],
            ],
            "error" => [
                "counter" => 0,
                "data" => [],
            ],
        ];

        $branchAsset = BranchAsset::where("id", $branchAssetId)
            ->where("branch_id", $branchId)
            ->where("asset_id", $assetId)
            ->whereIn("status", ["active", "maintenance"])
            ->first();

        if(!Utilities::isDefined($branchAsset)) {

            return $information;

        }

        DB::transaction(function() use ($branchAsset, $assignments, $userId, &$information) {

            foreach($assignments as $record) {

                $assetAssignment = AssetAssignment::query()
                    ->where("id", $record["id"])
                    ->where("user_id", $record["user_id"])
                    ->where("branch_id", $branchAsset->branch_id)
                    ->where("asset_id", $branchAsset->asset_id)
                    ->whereIn("status", ["active", "maintenance"])
                    ->first();

                if(Utilities::isDefined($assetAssignment)) {

                    $assetAssignment->status = "retired";
                    $assetAssignment->updated_at = now();
                    $assetAssignment->updated_by = $userId;
                    $assetAssignment->save();

                    self::recordLog([
                        "action_by" => $userId,
                        "user_id" => $assetAssignment->user_id,
                        "branch_id" => $assetAssignment->branch_id,
                        "from_user_id" => $assetAssignment->user_id,
                        "from_branch_id" => $assetAssignment->branch_id,
                        "asset_id" => $assetAssignment->asset_id,
                        "action_type" => "returned",
                        "quantity" => $assetAssignment->quantity,
                        "note" => "Activo devuelto por el colaborador.",
                    ]);

                    $information["success"]["counter"]++;
                    $information["success"]["data"][] = ["asset_id" => $branchAsset->asset_id];

                }else {

                    $information["error"]["counter"]++;
                    $information["error"]["data"][] = ["asset_id" => $branchAsset->asset_id];

                }

            }

        });

        return $information;

    }

    /**
     * Validate branch belongs to company
     *
     * @param  int  $branchId Branch ID
     */
    public static function validateBranch(int $branchId): ?Branch {

        return Branch::where("id", $branchId)
            ->first();

    }

    /**
     * Validate branch asset exists
     *
     * @param  int  $branchId Branch ID
     * @param  int  $branchAssetId Branch Asset ID
     * @param  int  $assetId Asset ID
     */
    public static function validateBranchAsset(int $branchId, int $branchAssetId, int $assetId): ?BranchAsset {

        return BranchAsset::where("id", $branchAssetId)
            ->where("branch_id", $branchId)
            ->where("asset_id", $assetId)
            ->whereIn("status", ["active", "maintenance"])
            ->first();

    }

    private static function recordLog(array $data): void {

        AssetAssignmentLog::create([
            ...$data,
            "user_id" => $data["user_id"] ?? null,
            "branch_id" => $data["branch_id"] ?? null,
            "from_user_id" => $data["from_user_id"] ?? null,
            "to_user_id" => $data["to_user_id"] ?? null,
            "from_branch_id" => $data["from_branch_id"] ?? null,
            "to_branch_id" => $data["to_branch_id"] ?? null,
            "action_at" => now(),
            "created_at" => now(),
            "created_by" => $data["action_by"] ?? null,
        ]);

    }
}
