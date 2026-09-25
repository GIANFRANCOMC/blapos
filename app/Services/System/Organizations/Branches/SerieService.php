<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Branches;

use App\Helpers\System\{Utilities};
use App\Models\System\General\{DocumentType};
use App\Models\System\Organizations\{Branch, Serie};
use Exception;
use Illuminate\Support\Facades\{DB};

/**
 * Service class for managing Serie operations
 * Handles business logic for creating document series for branches
 */
class SerieService {
    /**
     * Get new sequential number for branch (based on company branch count)
     *
     * @param  int  $companyId Company ID
     */
    public static function getNewSequential(int $companyId, int $branchId): int {

        $newSequential = 0;

        try {

            $maxSequential = Branch::query()
                ->where("id", "!=", $branchId)
                ->count();

            $newSequential = intval($maxSequential) + 1;

        }catch(Exception $e) {

            $newSequential = 0;

        }

        return $newSequential;

    }

    /**
     * Create series for all active document types for a given branch
     * Uses bulk insert for better performance
     *
     * @param  int  $branchId Branch ID
     * @param  int  $companyId Company ID
     * @param  int|null  $userId User ID creating the series
     * @return array Collection of created series
     */
    public static function createForBranch(int $branchId, int $companyId, ?int $userId = null): array {

        // Get new sequential number for the branch
        $newSequential = self::getNewSequential($companyId, $branchId);

        // Get all active document types for the company (only needed fields)

        $documentTypes = DocumentType::query()
            ->whereIn("status", ["active"])
            ->select("id", "code")
            ->get();

        if($documentTypes->isEmpty()) {

            return [];

        }

        // Prepare bulk insert data
        $now = now();

        $seriesData = $documentTypes->map(function($documentType) use ($branchId, $newSequential, $userId, $now) {

            return [
                "branch_id" => $branchId,
                "document_type_id" => $documentType->id,
                "code" => $documentType->code,
                "number" => $newSequential,
                "init" => 1,
                "status" => "active",
                "created_at" => $now,
                "created_by" => $userId,
            ];

        })->toArray();

        // Bulk insert for better performance
        Serie::insert($seriesData);

        // Return count instead of fetching (more efficient)
        return $seriesData;

    }

    public static function auditQuery(int $companyId, array $filters = []) {

        return DB::table("series_correlative_movements as movement")
            ->join("series", "series.id", "=", "movement.serie_id")
            ->join("branches", "branches.id", "=", "series.branch_id")
            ->leftJoin("users", "users.id", "=", "movement.user_id")
            ->when($filters["branch_id"] ?? null, fn($query, $id) => $query->where("series.branch_id", $id))
            ->when($filters["serie_id"] ?? null, fn($query, $id) => $query->where("movement.serie_id", $id))
            ->when($filters["user_id"] ?? null, fn($query, $id) => $query->where("movement.user_id", $id))
            ->when($filters["source"] ?? null, fn($query, $source) => $query->where("movement.source", $source))
            ->when($filters["action"] ?? null, fn($query, $action) => $query->where("movement.action", $action))
            ->when($filters["date_from"] ?? null, fn($query, $date) => $query->where("movement.occurred_at", ">=", Utilities::startOfDay($date)))
            ->when($filters["date_to"] ?? null, fn($query, $date) => $query->where("movement.occurred_at", "<=", Utilities::endOfDay($date)))
            ->select([
                "movement.id",
                "movement.sequential",
                "movement.action",
                "movement.source",
                "movement.note",
                "movement.occurred_at",
                "series.id as serie_id",
                "series.code as serie_code",
                "series.number as serie_number",
                "branches.id as branch_id",
                "branches.name as branch_name",
                "users.name as user_name",
            ])
            ->orderByDesc("movement.occurred_at");

    }

    public static function detectGaps(int $companyId, ?int $branchId = null): array {

        return Serie::query()
            ->when($branchId, fn($query) => $query->where("branch_id", $branchId))
            ->get()
            ->map(function(Serie $serie) {

                $issued = DB::table("series_correlative_movements")
                    ->where("serie_id", $serie->id)
                    ->where("action", "issued")
                    ->orderBy("sequential")
                    ->pluck("sequential")
                    ->map(fn($value) => (int) $value)
                    ->all();

                if(count($issued) < 2) {

                    return null;

                }

                $expected = range(min($issued), max($issued));

                $missing = array_values(array_diff($expected, $issued));

                return empty($missing) ? null : [
                    "serie_id" => $serie->id,
                    "serie" => $serie->legible_serie,
                    "first" => min($issued),
                    "last" => max($issued),
                    "missing" => $missing,
                ];

            })
            ->filter()
            ->values()
            ->all();

    }
}
