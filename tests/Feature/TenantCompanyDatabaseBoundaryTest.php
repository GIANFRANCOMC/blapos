<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Tests\{TestCase};

final class TenantCompanyDatabaseBoundaryTest extends TestCase {
    use RefreshDatabase;

    private const STRUCTURAL_COMPANY_TABLES = [
        "branches",
        "companies_sub_sections",
        "company_settings",
        "company_socials_media",
    ];

    public function test_migrated_database_only_has_structural_company_id_columns(): void {

        $database = DB::connection()->getDatabaseName();
        $columns = collect(DB::select(
            "SELECT TABLE_NAME, IS_NULLABLE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = ?
             ORDER BY TABLE_NAME",
            [$database, "company_id"]
        ));

        $this->assertSame(
            self::STRUCTURAL_COMPANY_TABLES,
            $columns->pluck("TABLE_NAME")->all()
        );

        $this->assertSame(["NO"], $columns->pluck("IS_NULLABLE")->unique()->values()->all());

    }

    public function test_every_structural_company_id_has_a_company_foreign_key(): void {

        $database = DB::connection()->getDatabaseName();
        $foreignKeys = collect(DB::select(
            "SELECT TABLE_NAME, REFERENCED_TABLE_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY TABLE_NAME",
            [$database, "company_id"]
        ));

        $this->assertSame(
            self::STRUCTURAL_COMPANY_TABLES,
            $foreignKeys->pluck("TABLE_NAME")->all()
        );

        $this->assertSame(["companies"], $foreignKeys->pluck("REFERENCED_TABLE_NAME")->unique()->values()->all());

    }

    public function test_tenant_wide_business_constraints_and_operational_indexes(): void {

        $database = DB::connection()->getDatabaseName();
        $indexes = collect(DB::select(
            "SELECT CONCAT(TABLE_NAME, '.', INDEX_NAME) AS index_key,
                    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_list
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND INDEX_NAME <> 'PRIMARY'
               AND TABLE_NAME IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             GROUP BY TABLE_NAME, INDEX_NAME",
            [
                $database,
                "brands",
                "categories",
                "customers",
                "items",
                "sales_header",
                "warehouse_items",
                "warehouses",
                "business_audit_logs",
                "authentication_events",
                "suppliers",
            ]
        ))->pluck("columns_list", "index_key");

        $this->assertSame("internal_code", $indexes["brands.brands_code_uq"] ?? null);
        $this->assertSame("internal_code", $indexes["categories.categories_code_uq"] ?? null);
        $this->assertSame(
            "identity_document_type_id,document_number",
            $indexes["customers.customers_identity_document_unique"] ?? null
        );

        $this->assertSame("barcode", $indexes["items.items_barcode_uq"] ?? null);
        $this->assertSame("type,internal_code", $indexes["items.items_type_code_uq"] ?? null);
        $this->assertSame("serie_id,sequential", $indexes["sales_header.sales_header_serie_sequential_uq"] ?? null);
        $this->assertSame("warehouse_id,item_id", $indexes["warehouse_items.warehouse_items_warehouse_item_uq"] ?? null);
        $this->assertSame("branch_id,name", $indexes["warehouses.warehouses_branch_name_uq"] ?? null);

        $this->assertSame(
            "auditable_type,auditable_id,occurred_at",
            $indexes["business_audit_logs.business_audit_record_idx"] ?? null
        );

        $this->assertSame(
            "user_id,event_type,occurred_at",
            $indexes["authentication_events.authentication_events_user_type_idx"] ?? null
        );

        $this->assertSame(
            "status,name,id",
            $indexes["suppliers.suppliers_status_name_idx"] ?? null
        );

    }
}
