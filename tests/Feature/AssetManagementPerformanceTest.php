<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\System\Assets\{AssetManagementService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class AssetManagementPerformanceTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

    }

    public function test_assigning_and_retiring_assets_reads_each_branch_batch_once(): void {

        $branchId = (int) DB::table("branches")->value("id");
        $assetIds = [];

        foreach(range(1, 3) as $number) {

            $assetIds[] = (int) DB::table("assets")->insertGetId([
                "internal_code" => "ASSET-BATCH-{$number}",
                "name" => "Activo {$number}",
                "status" => "active",
            ]);

        }

        $assignments = collect($assetIds)
            ->map(fn(int $assetId): array => ["asset_id" => $assetId, "quantity" => 1])
            ->all();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $created = AssetManagementService::assignAssetsToBranch($branchId, $assignments, 1);
        $assignmentQueries = DB::getQueryLog();

        DB::flushQueryLog();

        $branchAssets = DB::table("branch_assets")
            ->where("branch_id", $branchId)
            ->get(["id", "asset_id"]);

        DB::flushQueryLog();

        $retired = AssetManagementService::unassignAssetsFromBranch(
            $branchId,
            $branchAssets->map(fn(object $asset): array => [
                "id" => $asset->id,
                "asset_id" => $asset->asset_id,
            ])->all(),
            1
        );

        $retirementQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(3, $created["success"]["counter"]);
        $this->assertSame(3, $retired["success"]["counter"]);
        $this->assertSame(6, DB::table("asset_assignment_logs")->count());

        foreach([$assignmentQueries, $retirementQueries] as $queries) {

            $assetReads = collect($queries)
                ->filter(fn(array $query): bool => str_starts_with($query["query"], "select ")
                    && str_contains($query["query"], "branch_assets"));

            $this->assertCount(1, $assetReads);

        }

    }
}
