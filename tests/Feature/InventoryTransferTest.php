<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Warehouses\InventoryReportExport;
use App\Http\Controllers\System\Warehouses\StockManagementController;
use App\Models\System\Organizations\User;
use App\Services\System\Organizations\Companies\CompanySettingService;
use App\Services\System\Warehouses\Inventory\{InventoryMovementService, InventoryTransferService};
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ProvisionsSystemDatabase;
use Tests\TestCase;

final class InventoryTransferTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    public function test_transfer_lists_one_header_and_prints_all_positions_while_kardex_keeps_each_movement(): void {
        $data = $this->transferData();
        $transfer = InventoryMovementService::transfer($data);
        $id = $transfer["id"];
        $source = $data["source_warehouse_id"];
        $destination = $data["destination_warehouse_id"];

        $this->assertMatchesRegularExpression('/^TRAS-[A-Z0-9]{10}$/', $transfer["reference"]);
        $this->assertDatabaseHas("inventory_transfers", ["id" => $id, "reference" => $transfer["reference"], "reason" => $data["reason"], "status" => "accepted"]);
        $this->assertDatabaseCount("inventory_transfer_items", 2);
        foreach($transfer["movements"] as $pair) {
            $this->assertDatabaseHas("inventory_transfer_items", [
                "inventory_transfer_id" => $id, "item_id" => $pair["item_id"],
                "outbound_movement_id" => $pair["exit"]->id, "inbound_movement_id" => $pair["entry"]->id,
            ]);
        }
        $this->assertSame(4, DB::table("inventory_movements")->where("origin_id", $id)->whereNull("metadata")->where("reason", "")->count());
        $this->assertEquals(3, DB::table("warehouse_items")->where("warehouse_id", $source)->where("item_id", $data["items"][0]["item_id"])->value("quantity"));
        $this->assertEquals(2, DB::table("warehouse_items")->where("warehouse_id", $destination)->where("item_id", $data["items"][0]["item_id"])->value("quantity"));

        $controller = app(StockManagementController::class);
        foreach([$source, $destination, "all"] as $warehouse) {
            $filters = ["warehouse_id" => $warehouse, "product_search" => "TRANSFER-TEST"];
            $page = $controller->transfers(Request::create("/stocks_management/transfers", "GET", $filters));
            $this->assertSame(1, $page->total());
            $this->assertSame(10, $page->perPage());
            $this->assertEquals(2, $page->items()[0]->items_count);
            $this->assertFalse($page->items()[0]->relationLoaded("items"));
        }
        $this->assertNull(InventoryTransferService::query(["warehouse_id" => "all", "allowed_warehouse_ids" => []])->find($id));
        $scoped = InventoryTransferService::query(["allowed_warehouse_ids" => [$source]])->findOrFail($id);
        $this->assertSame($source, $scoped->sourceWarehouse->id);
        $this->assertNull($scoped->destinationWarehouse);
        $this->assertNull(InventoryTransferService::query(["warehouse_id" => $destination, "allowed_warehouse_ids" => [$source]])->find($id));

        $detail = $controller->showTransfer($id)->getData(true)["data"];
        $this->assertCount(2, $detail["items"]);
        $this->assertSame($transfer["reference"], $detail["reference"]);
        $this->assertSame("Perno", $detail["items"][0]["item"]["name"]);
        $this->assertArrayNotHasKey("reason", $detail["items"][0]);

        $print = $controller->printTransfer($id);
        $this->assertSame("application/pdf", $print->headers->get("content-type"));
        $this->assertStringStartsWith("inline;", $print->headers->get("content-disposition"));
        $this->assertStringStartsWith("%PDF-", $print->getContent());

        $excel = new InventoryReportExport("transfers", ["warehouse_id" => $source, "product_search" => "Tuerca"]);
        $this->assertCount(1, $excel->collection());
        $row = $excel->map($excel->collection()->first());
        $this->assertStringContainsString("Perno", $row[6]);
        $this->assertStringContainsString("Tuerca", $row[6]);

        $ledger = InventoryMovementService::getKardexQuery(["origin_types" => ["transfer_out", "transfer_in"]])->get();
        $this->assertCount(4, $ledger);
        foreach($ledger as $record) {
            $this->assertSame($transfer["reference"], $record->reference);
            $this->assertSame($data["reason"], $record->reason);
        }
    }

    public function test_insufficient_stock_on_the_second_position_rolls_back_the_entire_transfer(): void {
        $data = $this->transferData();
        $data["items"][1]["quantity"] = 6;

        try {
            InventoryMovementService::transfer($data);
            $this->fail("El traslado debía fallar por stock insuficiente.");
        } catch(DomainException $exception) {
            $this->assertStringContainsString("supera el stock disponible", $exception->getMessage());
        }

        $this->assertDatabaseCount("inventory_transfers", 0);
        $this->assertDatabaseCount("inventory_transfer_items", 0);
        $this->assertSame(0, DB::table("inventory_movements")->whereIn("origin_type", ["transfer_out", "transfer_in"])->count());
        $this->assertEquals([5, 5], DB::table("warehouse_items")->where("warehouse_id", $data["source_warehouse_id"])->orderBy("item_id")->pluck("quantity")->all());
        $this->assertDatabaseMissing("warehouse_items", ["warehouse_id" => $data["destination_warehouse_id"]]);
    }

    public function test_transfer_status_updates_post_stock_only_on_acceptance_and_can_be_retried(): void {
        $data = $this->transferData();
        $transfer = InventoryMovementService::transfer($data + ["status" => "pending"]);
        $id = $transfer["id"];
        $controller = app(StockManagementController::class);
        $change = fn(string $status) => $controller->advanceTransfer(
            Request::create("/stocks_management/transfers/{$id}/status", "PATCH", ["status" => $status]),
            $id
        );

        $this->assertSame("pending", $transfer["status"]);
        $this->assertSame([], $transfer["movements"]);
        $this->assertSame(2, DB::table("inventory_transfer_items")->whereNull("outbound_movement_id")->count());
        $this->assertSame(422, $change("accepted")->getStatusCode());
        $this->assertSame("sent", $change("sent")->getData(true)["data"]["status"]);
        $this->assertDatabaseCount("inventory_movements", 0);

        $secondItem = $data["items"][1]["item_id"];
        $sourceStock = DB::table("warehouse_items")->where("warehouse_id", $data["source_warehouse_id"])->where("item_id", $secondItem);
        $sourceStock->update(["quantity" => 1]);
        $this->assertSame(422, $change("accepted")->getStatusCode());
        $this->assertDatabaseHas("inventory_transfers", ["id" => $id, "status" => "sent"]);
        $this->assertDatabaseCount("inventory_movements", 0);

        $sourceStock->update(["quantity" => 5]);
        $this->assertSame("accepted", $change("accepted")->getData(true)["data"]["status"]);
        $this->assertSame("accepted", $change("accepted")->getData(true)["data"]["status"]);
        $this->assertDatabaseCount("inventory_movements", 4);
        $this->assertSame(2, DB::table("inventory_transfer_items")->whereNotNull("inbound_movement_id")->count());
    }

    public function test_cancellation_preserves_pending_and_sent_transfers_and_records_the_reason_once(): void {
        $data = $this->transferData();
        $controller = app(StockManagementController::class);
        foreach(["pending", "sent"] as $status) {
            $transfer = InventoryMovementService::transfer($data + ["status" => $status]);
            $id = $transfer["id"];
            $otherStatus = $status === "sent" ? "pending" : "sent";
            $this->assertSame(422, $this->cancelTransfer($controller, $id, $otherStatus)->getStatusCode());
            $canceled = $this->cancelTransfer($controller, $id, $status, "Selección equivocada")->getData(true)["data"];
            $this->assertSame("canceled", $canceled["status"]);
            $this->assertNotNull($canceled["canceled_at"]);
            $this->assertSame($data["user_id"], $canceled["canceled_by_user"]["id"]);
            $this->assertSame("Selección equivocada", $canceled["cancellation_reason"]);
            $this->assertSame(200, $this->cancelTransfer($controller, $id, $status, "Otro motivo")->getStatusCode());
            $this->assertDatabaseHas("inventory_transfers", ["id" => $id, "cancellation_reason" => "Selección equivocada"]);
        }
        $this->assertDatabaseCount("inventory_transfer_items", 4);
        $this->assertDatabaseCount("inventory_movements", 0);
        $this->assertEquals([5, 5], DB::table("warehouse_items")->where("warehouse_id", $data["source_warehouse_id"])->orderBy("item_id")->pluck("quantity")->all());
        $this->assertCount(2, InventoryTransferService::query(["transfer_status" => "canceled"])->get());
    }

    public function test_accepted_transfer_cannot_be_canceled_even_if_the_caller_sends_an_old_status(): void {
        $data = $this->transferData();
        $transfer = InventoryMovementService::transfer($data);
        $stock = DB::table("warehouse_items")->orderBy("id")->get();

        foreach(["accepted", "sent", "pending"] as $expected) {
            $response = $this->cancelTransfer(app(StockManagementController::class), $transfer["id"], $expected);
            $this->assertSame(422, $response->getStatusCode());
            $this->assertStringContainsString("Solo se pueden cancelar traslados pendientes o enviados", $response->getData(true)["msg"]);
        }
        $this->assertDatabaseHas("inventory_transfers", ["id" => $transfer["id"], "status" => "accepted", "canceled_at" => null]);
        $this->assertDatabaseCount("inventory_movements", 4);
        $this->assertEquals($stock, DB::table("warehouse_items")->orderBy("id")->get());
    }

    private function cancelTransfer(StockManagementController $controller, int $id, string $expectedStatus, ?string $reason = null): \Illuminate\Http\JsonResponse {
        return $controller->advanceTransfer(Request::create("/stocks_management/transfers/{$id}/status", "PATCH", [
            "status" => "canceled", "expected_status" => $expectedStatus, "cancellation_reason" => $reason,
        ]), $id);
    }

    public function test_transfer_prefix_is_per_company_and_reference_dates_and_product_filters_combine(): void {
        $data = $this->transferData();
        $settings = DB::table("company_settings")->where("group", "internal_code_prefixes")->where("key", "inventory_transfer");
        $settings->update(["value" => "LOCAL"]);
        CompanySettingService::clearCache();
        $first = InventoryMovementService::transfer($data);

        $settings->update(["value" => null]);
        CompanySettingService::clearCache();
        $second = InventoryMovementService::transfer($data + ["status" => "sent"]);
        $this->assertMatchesRegularExpression('/^LOCAL-[A-Z0-9]{10}$/', $first["reference"]);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{10}$/', $second["reference"]);
        $this->assertDatabaseHas("inventory_transfers", ["id" => $first["id"], "reference" => $first["reference"]]);
        DB::table("inventory_transfers")->where("id", $first["id"])->update(["created_at" => "2026-11-16 23:59:59"]);
        DB::table("inventory_transfers")->where("id", $second["id"])->update(["created_at" => "2026-11-17 00:00:00"]);

        $filters = [
            "source_warehouse_id" => $data["source_warehouse_id"],
            "destination_warehouse_id" => $data["destination_warehouse_id"],
        ];
        $this->assertSame([$second["id"], $first["id"]], InventoryTransferService::query($filters + ["product_search" => "Perno"])->pluck("id")->all());
        $this->assertSame(0, InventoryTransferService::query($filters + ["product_search" => "Sin coincidencias"])->count());
        $this->assertSame([$first["id"]], InventoryTransferService::query($filters + ["reference" => "LOCAL-"])->pluck("id")->all());
        $this->assertSame([$second["id"]], InventoryTransferService::query($filters + ["date_from" => "2026-11-17"])->pluck("id")->all());

        $search = $filters + ["reference" => "LOCAL-", "date_from" => "2026-11-16", "date_to" => "2026-11-16", "product_search" => "Perno"];
        $listing = app(StockManagementController::class)->transfers(Request::create("/stocks_management/transfers", "GET", $search));
        $this->assertSame([$first["id"]], $listing->getCollection()->pluck("id")->all());
        $this->assertSame([$first["id"]], (new InventoryReportExport("transfers", $search))->collection()->pluck("id")->all());
    }

    public function test_source_and_destination_filters_are_independent_and_respect_warehouse_permissions(): void {
        $data = $this->transferData();
        $transfer = InventoryMovementService::transfer($data + ["status" => "pending"]);
        $id = $transfer["id"];
        $source = $data["source_warehouse_id"];
        $destination = $data["destination_warehouse_id"];
        $controller = app(StockManagementController::class);
        foreach([[], ["source_warehouse_id" => $source], ["destination_warehouse_id" => $destination], ["source_warehouse_id" => $source, "destination_warehouse_id" => $destination]] as $filters) {
            $this->assertSame([$id], $controller->transfers(Request::create("/stocks_management/transfers", "GET", $filters))->getCollection()->pluck("id")->all());
            $this->assertSame([$id], (new InventoryReportExport("transfers", $filters))->collection()->pluck("id")->all());
        }
        foreach([["source_warehouse_id" => $destination], ["destination_warehouse_id" => $source]] as $filters) {
            $this->assertSame(0, InventoryTransferService::query($filters)->count());
        }
        $this->assertSame(0, InventoryTransferService::query(["source_warehouse_id" => $source, "allowed_warehouse_ids" => [$destination]])->count());
        $this->assertSame(0, InventoryTransferService::query(["destination_warehouse_id" => $destination, "allowed_warehouse_ids" => [$source]])->count());
    }

    private function transferData(): array {
        $this->provisionSystemDatabase();
        $this->actingAs(User::query()->firstOrFail());

        $source = DB::table("warehouses")->first();
        $destination = DB::table("warehouses")->insertGetId([
            "branch_id" => $source->branch_id, "name" => "Destino", "status" => "active",
        ]);
        $items = [];
        foreach(["Perno", "Tuerca"] as $index => $name) {
            $item = DB::table("items")->insertGetId([
                "name" => $name, "internal_code" => "TRANSFER-TEST-{$index}", "type" => "product", "status" => "active",
                "price" => 1, "currency_id" => DB::table("currencies")->value("id"),
                "measurement_unit_id" => DB::table("measurement_units")->value("id"),
            ]);
            DB::table("warehouse_items")->insert([
                "warehouse_id" => $source->id, "item_id" => $item, "quantity" => 5, "average_cost" => 3, "inventory_value" => 15, "status" => "active",
            ]);
            $items[] = ["item_id" => $item, "quantity" => 2];
        }

        return [
            "source_warehouse_id" => $source->id, "destination_warehouse_id" => $destination,
            "user_id" => auth()->id(), "items" => $items, "reason" => "Reposición de inventario.",
        ];
    }
}
