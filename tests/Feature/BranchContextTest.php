<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\System\Organizations\{User};
use App\Services\System\Tenancy\{BranchContext};
use Illuminate\Auth\Access\{AuthorizationException};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB, Hash};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class BranchContextTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    public function test_restricted_user_cannot_select_an_unassigned_branch(): void {

        $this->provisionSystemDatabase();

        $companyId = (int) DB::table("companies")->value("id");
        $identityDocumentTypeId = (int) DB::table("identity_document_types")->value("id");
        $allowedBranchId = (int) DB::table("branches")->value("id");
        $deniedBranchId = (int) DB::table("branches")->insertGetId([
            "company_id" => $companyId,
            "internal_code" => "SUC-002",
            "name" => "Sucursal restringida",
            "status" => "active",
        ]);

        $roleId = (int) DB::table("roles")->insertGetId([
            "slug" => "branch-limited",
            "name" => "Acceso limitado",
            "is_full_access" => false,
            "branch_scope_mode" => "all",
            "cash_register_scope_mode" => "all",
            "warehouse_scope_mode" => "all",
            "status" => "active",
        ]);
        $userId = (int) DB::table("users")->insertGetId([
            "role_id" => $roleId,
            "branch_scope_mode" => "restricted",
            "cash_register_scope_mode" => "inherit",
            "warehouse_scope_mode" => "inherit",
            "identity_document_type_id" => $identityDocumentTypeId,
            "document_number" => "70000001",
            "name" => "Usuario limitado",
            "email" => "limited@example.test",
            "password" => Hash::make("password"),
            "status" => "active",
        ]);
        DB::table("user_branches")->insert([
            "user_id" => $userId,
            "branch_id" => $allowedBranchId,
            "status" => "active",
        ]);

        $context = app(BranchContext::class);
        $context->setUser(User::query()->findOrFail($userId));

        $this->assertSame([$allowedBranchId], $context->allowedIds());
        $this->assertTrue($context->allows($allowedBranchId));
        $this->assertFalse($context->allows($deniedBranchId));

        $this->expectException(AuthorizationException::class);
        $context->select($deniedBranchId);

    }
}
