<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\System\Warehouses\InventoryTransferStatus;
use App\Exports\System\Warehouses\InventoryReportExport;
use App\Http\Controllers\System\Warehouses\StockManagementController;
use App\Http\Requests\System\Warehouses\StoreTransferOperationRequest;
use App\Models\System\Organizations\User;
use App\Services\System\Organizations\Companies\CompanySettingService;
use App\Services\System\Warehouses\Inventory\{InventoryMovementService, InventoryTransferService};
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\ProvisionsSystemDatabase;
use Tests\TestCase;

final class InventoryTransferTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    public function test_registration_is_awaiting_dispatch_and_does_not_move_stock(): void {
        $data=$this->transferData();
        $result=InventoryTransferService::register($data);
        $this->assertSame('awaiting_dispatch',$result['status']);
        $this->assertDatabaseCount('inventory_movements',0);
        $this->assertDatabaseCount('inventory_transfer_items',2);
        $this->assertDatabaseHas('inventory_transfer_status_events',['action'=>'registered','status'=>'awaiting_dispatch']);
        $this->assertEquals([5,5],DB::table('warehouse_items')->orderBy('item_id')->pluck('quantity')->all());
        $detail=$this->detail($result['id']);
        $this->assertNull($detail['receiver_name']);
        $this->assertTrue($detail['can_dispatch']);
        $this->assertFalse($detail['can_receive']);
        $this->assertSame('awaiting_dispatch',$detail['items'][0]['status']);
    }

    public function test_dispatch_deducts_only_the_origin_and_keeps_the_value_in_transit(): void {
        $data=$this->transferData();
        $result=InventoryTransferService::register($data+['status'=>'in_transit']);
        $this->assertSame('in_transit',$result['status']);
        $this->assertDatabaseCount('inventory_movements',2);
        $this->assertDatabaseMissing('warehouse_items',['warehouse_id'=>$data['destination_warehouse_id']]);
        $this->assertEquals([3,3],DB::table('warehouse_items')->orderBy('item_id')->pluck('quantity')->all());
        $this->assertEquals(12,DB::table('inventory_transfer_items')->sum('transit_value'));
        $detail=$this->detail($result['id']);
        $this->assertEquals(2,$detail['items'][0]['pending_quantity']);
        $this->assertTrue($detail['can_receive']);
        $this->assertFalse($detail['can_cancel']);
        InventoryTransferService::advance($result['id'],InventoryTransferStatus::InTransit,$data['user_id'],0);
        $this->assertDatabaseCount('inventory_movements',2);
        $this->assertDatabaseCount('inventory_transfer_status_events',2);
    }

    public function test_failed_dispatch_rolls_back_all_lines_stock_and_history(): void {
        $data=$this->transferData();
        $data['items'][1]['quantity']=6;
        try { InventoryTransferService::register($data+['status'=>'in_transit']); $this->fail('Debía faltar stock.'); }
        catch(DomainException $e) { $this->assertStringContainsString('supera el stock',$e->getMessage()); }
        $this->assertDatabaseCount('inventory_transfers',0);
        $this->assertDatabaseCount('inventory_transfer_items',0);
        $this->assertDatabaseCount('inventory_movements',0);
        $this->assertDatabaseCount('inventory_transfer_status_events',0);
        $this->assertEquals([5,5],DB::table('warehouse_items')->orderBy('item_id')->pluck('quantity')->all());
    }

    public function test_partial_receipts_compute_each_line_and_the_header_without_changing_the_shipped_quantity(): void {
        $data=$this->transferData();
        $result=InventoryTransferService::register($data+['status'=>'in_transit']);
        $detail=$this->detail($result['id']);
        $first=$detail['items'][0]['id']; $second=$detail['items'][1]['id'];
        $payload=$this->payload($result['id'],[['id'=>$first,'quantity'=>1]]);
        $request=$this->operationRequest($payload);
        $response=app(StockManagementController::class)->receiveTransfer($request,$result['id']);
        $this->assertSame(200,$response->getStatusCode());
        $detail=$response->getData(true)['data'];
        $this->assertSame('partially_received',$detail['status']);
        $this->assertSame('partially_received',$detail['items'][0]['status']);
        $this->assertSame('awaiting_receipt',$detail['items'][1]['status']);
        $this->assertEquals(2,$detail['items'][0]['quantity']);
        $this->assertEquals(1,$detail['items'][0]['received_quantity']);
        $this->assertEquals(1,$detail['items'][0]['pending_quantity']);
        InventoryTransferService::operate($result['id'],'receipt',$this->payload($result['id'],[['id'=>$first,'quantity'=>1],['id'=>$second,'quantity'=>2]]),$data['user_id']);
        $detail=$this->detail($result['id']);
        $this->assertSame('received',$detail['status']);
        $this->assertSame(['received','received'],array_column($detail['items'],'status'));
        $this->assertEquals(0,DB::table('inventory_transfer_items')->sum('transit_value'));
        $this->assertEquals(4,DB::table('warehouse_items')->where('warehouse_id',$data['destination_warehouse_id'])->sum('quantity'));
        $this->assertDatabaseCount('inventory_transfer_operations',2);
        $this->assertDatabaseCount('inventory_transfer_operation_items',3);
        $this->assertCount(4,$detail['status_history']);
        $this->assertStringStartsWith('%PDF-',app(StockManagementController::class)->printTransfer($result['id'])->getContent());
    }

    public function test_repeated_partial_state_events_are_preserved_and_retries_do_not_duplicate_receipts(): void {
        $data=$this->transferData();
        $data['items']=[$data['items'][0]]; $data['items'][0]['quantity']=3;
        DB::table('warehouse_items')->where('item_id',$data['items'][0]['item_id'])->update(['quantity'=>3,'inventory_value'=>10,'average_cost'=>3.33]);
        $id=InventoryTransferService::register($data+['status'=>'in_transit'])['id'];
        $line=$this->detail($id)['items'][0]['id'];
        $first=$this->payload($id,[['id'=>$line,'quantity'=>1]]);
        $operation=InventoryTransferService::operate($id,'receipt',$first,$data['user_id']);
        foreach([2,3] as $step) InventoryTransferService::operate($id,'receipt',$this->payload($id,[['id'=>$line,'quantity'=>1]]),$data['user_id']);
        $retry=InventoryTransferService::operate($id,'receipt',$first,$data['user_id']);
        $this->assertSame($operation->id,$retry->id);
        $this->assertDatabaseCount('inventory_transfer_operations',3);
        $this->assertDatabaseCount('inventory_movements',4);
        $this->assertSame(['awaiting_dispatch','in_transit','partially_received','partially_received','received'],array_column($this->detail($id)['status_history'],'status'));
        $this->assertEquals(10,DB::table('inventory_transfer_operation_items')->sum('value'));
        $this->assertEquals(10,DB::table('warehouse_items')->where('warehouse_id',$data['destination_warehouse_id'])->sum('inventory_value'));
        $this->assertEquals(0,DB::table('inventory_transfer_items')->sum('transit_value'));
        try { InventoryTransferService::operate($id,'receipt',array_replace($first,['reason'=>'Distinto']),$data['user_id']); $this->fail('No se puede reutilizar una clave con otro contenido.'); }
        catch(DomainException $e) { $this->assertSame(409,$e->getCode()); }
    }

    public function test_receipts_reject_overages_foreign_lines_stale_versions_and_invalid_batches_atomically(): void {
        $data=$this->transferData();
        $id=InventoryTransferService::register($data+['status'=>'in_transit'])['id'];
        $lines=$this->detail($id)['items'];
        foreach([
            [['id'=>$lines[0]['id'],'quantity'=>0]],
            [['id'=>$lines[0]['id'],'quantity'=>-1]],
            [['id'=>$lines[0]['id'],'quantity'=>3]],
            [['id'=>999999,'quantity'=>1]],
            [['id'=>$lines[0]['id'],'quantity'=>1],['id'=>$lines[1]['id'],'quantity'=>3]],
        ] as $items) {
            try { InventoryTransferService::operate($id,'receipt',$this->payload($id,$items),$data['user_id']); $this->fail('Se aceptó una recepción inválida.'); }
            catch(DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $payload=$this->payload($id,[['id'=>$lines[0]['id'],'quantity'=>1]]);
        $payload['expected_version']=0;
        try { InventoryTransferService::operate($id,'receipt',$payload,$data['user_id']); $this->fail('Se aceptó una versión antigua.'); }
        catch(DomainException $e) { $this->assertSame(409,$e->getCode()); }
        $this->assertDatabaseCount('inventory_transfer_operations',0);
        $this->assertDatabaseCount('inventory_transfer_operation_items',0);
        $this->assertDatabaseCount('inventory_movements',2);
        $this->assertDatabaseCount('inventory_transfer_status_events',2);
    }

    public function test_cancellation_is_only_allowed_before_dispatch_and_receipt_cannot_be_a_manual_status(): void {
        $data=$this->transferData();
        $id=InventoryTransferService::register($data)['id'];
        InventoryTransferService::advance($id,InventoryTransferStatus::Canceled,$data['user_id'],0,'Error de almacén');
        InventoryTransferService::advance($id,InventoryTransferStatus::Canceled,$data['user_id'],0,'Otro motivo');
        $this->assertDatabaseHas('inventory_transfers',['id'=>$id,'cancellation_reason'=>'Error de almacén']);
        $this->assertSame(['canceled','canceled'],array_column($this->detail($id)['items'],'status'));
        $this->assertDatabaseCount('inventory_movements',0);
        $sent=InventoryTransferService::register($data+['status'=>'in_transit'])['id'];
        foreach([InventoryTransferStatus::Canceled,InventoryTransferStatus::Received] as $status) {
            try { InventoryTransferService::advance($sent,$status,$data['user_id'],1); $this->fail('Estado inválido.'); }
            catch(DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->assertSame('in_transit',$this->detail($sent)['status']);
    }

    public function test_returns_and_losses_close_differences_without_falsely_receiving_them_and_conserve_value(): void {
        $data=$this->transferData(); $data['items']=[$data['items'][0]]; $data['items'][0]['quantity']=5;
        $id=InventoryTransferService::register($data+['status'=>'in_transit'])['id'];
        $line=$this->detail($id)['items'][0]['id'];
        foreach([['receipt',2],['return',1],['loss',2]] as [$kind,$quantity]) {
            $payload=$this->payload($id,[['id'=>$line,'quantity'=>$quantity]]); $payload['reason']='Diferencia verificada';
            InventoryTransferService::operate($id,$kind,$payload,$data['user_id']);
        }
        $detail=$this->detail($id);
        $this->assertSame('closed_with_differences',$detail['status']);
        $this->assertSame('resolved',$detail['items'][0]['status']);
        $this->assertEquals(2,$detail['items'][0]['received_quantity']);
        $this->assertEquals(3,$detail['items'][0]['resolved_quantity']);
        $this->assertEquals(0,$detail['items'][0]['pending_quantity']);
        $this->assertEquals(0,$detail['items'][0]['transit_value']);
        $this->assertEquals(1,DB::table('warehouse_items')->where('warehouse_id',$data['source_warehouse_id'])->where('item_id',$data['items'][0]['item_id'])->value('quantity'));
        $this->assertEquals(2,DB::table('warehouse_items')->where('warehouse_id',$data['destination_warehouse_id'])->value('quantity'));
        $loss=DB::table('inventory_transfer_operations')->where('kind','loss')->value('id');
        $this->assertDatabaseHas('inventory_transfer_operation_items',['inventory_transfer_operation_id'=>$loss,'quantity'=>2,'value'=>6,'movement_id'=>null]);
        $this->assertDatabaseHas('inventory_movements',['origin_type'=>'transfer_return','quantity_change'=>1]);
        $this->assertFalse($detail['can_receive']);
    }

    public function test_authors_dates_filters_and_exports_keep_the_original_creator_and_all_receivers(): void {
        $data=$this->transferData();
        $receiver=User::firstOrFail()->replicate(); $receiver->name='Recepción'; $receiver->email='receiver@example.test'; $receiver->document_number='87654321'; $receiver->save();
        $id=InventoryTransferService::register($data)['id'];
        InventoryTransferService::advance($id,InventoryTransferStatus::InTransit,$receiver->id,0);
        $items=array_map(fn($line)=>['id'=>$line['id'],'quantity'=>2],$this->detail($id)['items']);
        InventoryTransferService::operate($id,'receipt',$this->payload($id,$items),$receiver->id);
        $receiver->update(['name'=>'Nombre actualizado']);
        $detail=$this->detail($id);
        $this->assertSame('Recepción',$detail['sender_name']);
        $this->assertSame('Recepción',$detail['receiver_name']);
        $this->assertSame(User::findOrFail($data['user_id'])->name,$detail['user']['name']);
        $filters=['user_id'=>$data['user_id'],'source_warehouse_id'=>$data['source_warehouse_id'],'product_search'=>'Perno'];
        $this->assertSame([$id],app(StockManagementController::class)->transfers(Request::create('/','GET',$filters))->getCollection()->pluck('id')->all());
        $export=new InventoryReportExport('transfers',$filters);
        $this->assertSame([$id],$export->collection()->pluck('id')->all());
        $row=$export->map($export->collection()->first());
        $this->assertSame('Recepción',$row[9]);
        $this->assertStringContainsString('Recepción completa registrada',$row[10]);
        $this->assertEquals(0,$row[11]);
        $this->assertSame('Recepción',$row[13]);
        $this->assertSame(0,InventoryTransferService::query($filters+['allowed_warehouse_ids'=>[]])->count());
        $this->assertCount(0,InventoryTransferService::getUsers([]));
        $this->assertSame([$data['user_id']],InventoryTransferService::getUsers(null)->pluck('id')->all());
    }

    public function test_destination_operator_can_receive_without_access_to_origin_and_cannot_resolve_or_dispatch(): void {
        $data=$this->transferData();
        $id=InventoryTransferService::register($data+['status'=>'in_transit'])['id'];
        $role=\App\Models\System\Organizations\Role::firstOrFail()->replicate();
        $role->slug='receipt-operator'; $role->name='Recepción de almacén'; $role->is_full_access=false; $role->save();
        DB::table('role_sub_sections')->insert(['role_id'=>$role->id,'sub_section_id'=>DB::table('sub_sections')->where('dom_route','stocks_management.transfers.index')->value('id'),'actions'=>json_encode(['view','operate']),'status'=>'active']);
        $actor=User::firstOrFail()->replicate(); $actor->role_id=$role->id; $actor->name='Operador destino'; $actor->email='destination@example.test'; $actor->document_number='87654320'; $actor->warehouse_scope_mode='restricted'; $actor->save();
        DB::table('user_warehouses')->insert(['user_id'=>$actor->id,'warehouse_id'=>$data['destination_warehouse_id'],'status'=>'active']);
        $this->actingAs($actor);
        $detail=$this->detail($id);
        $this->assertNull($detail['source_warehouse']);
        $this->assertTrue($detail['can_receive']);
        $this->assertFalse($detail['can_dispatch']);
        $this->assertFalse($detail['can_resolve']);
        $payload=$this->payload($id,[['id'=>$detail['items'][0]['id'],'quantity'=>1]]);
        $this->assertSame(200,app(StockManagementController::class)->receiveTransfer($this->operationRequest($payload),$id)->getStatusCode());
        $payload=$this->payload($id,[['id'=>$detail['items'][0]['id'],'quantity'=>1]])+['kind'=>'loss','reason'=>'Prueba de autorización'];
        try { app(StockManagementController::class)->resolveTransfer($this->operationRequest($payload),$id); $this->fail('Debía restringir la resolución sin acceso al origen.'); }
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $error) { $this->assertSame(403,$error->getStatusCode()); }
    }

    public function test_partial_receipts_use_the_company_decimal_precision_for_quantities_and_transit_value(): void {
        $data=$this->transferData();
        DB::table('company_settings')->where('group','numeric_validation')->where('key','decimal_precision')->update(['value'=>'3']);
        CompanySettingService::clearCache();
        $data['items']=[$data['items'][0]]; $data['items'][0]['quantity']='0.125';
        $id=InventoryTransferService::register($data+['status'=>'in_transit'])['id'];
        $line=$this->detail($id)['items'][0]['id'];
        foreach(['0.025','0.100'] as $quantity) {
            $payload=$this->payload($id,[['id'=>$line,'quantity'=>$quantity]]);
            $response=app(StockManagementController::class)->receiveTransfer($this->operationRequest($payload),$id);
            $this->assertSame(200,$response->getStatusCode());
        }
        $detail=$this->detail($id);
        $this->assertSame('received',$detail['status']);
        $this->assertEquals('0.125',$detail['items'][0]['received_quantity']);
        $this->assertEquals('0.375',DB::table('inventory_transfer_operation_items')->sum('value'));
        $this->assertEquals(0,$detail['items'][0]['transit_value']);
    }

    private function detail(int $id): array { return app(StockManagementController::class)->showTransfer($id)->getData(true)['data']; }
    private function payload(int $id,array $items): array { return ['request_key'=>(string) Str::uuid(),'expected_version'=>(int) DB::table('inventory_transfers')->where('id',$id)->value('version'),'items'=>$items]; }
    private function operationRequest(array $data): StoreTransferOperationRequest {
        $request=StoreTransferOperationRequest::create('/','POST',$data);
        $request->setContainer(app())->setRedirector(app('redirect'))->setUserResolver(fn()=>auth()->user());
        $request->validateResolved();
        return $request;
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
