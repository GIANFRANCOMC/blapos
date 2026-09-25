<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {

        Schema::create("company_settings", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("company_id");
            $table->string("group", 255);
            $table->string("key", 255);
            $table->text("value")->nullable();
            $table->text("description")->nullable();
            $table->enum("value_type", ["string", "boolean", "integer", "decimal", "json"])->default("string");
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("company_id")->references("id")->on("companies")->onDelete("cascade");
            $table->unique(["company_id", "group", "key"]);

        });

        Schema::create("taxes", function(Blueprint $table) {

            $table->id();
            $table->string("code", 30);
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->decimal("rate", 15, 3)->default(0);
            $table->enum("calculation_type", ["percentage", "fixed"])->default("percentage");
            $table->enum("operation_type", ["addition", "subtraction"])->default("addition");
            $table->unsignedInteger("min_apply_quantity")->nullable();
            $table->unsignedInteger("max_apply_quantity")->nullable();
            $table->enum("scope", ["sale", "purchase", "both"])->default("both");
            $table->boolean("is_required")->default(true);
            $table->boolean("is_default")->default(false);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

        });

        Schema::create("payment_methods", function(Blueprint $table) {

            $table->id();
            $table->string("code", 30);
            $table->string("name", 255);
            $table->string("category", 40)->default("other");
            $table->string("sunat_code", 10)->nullable();
            $table->string("image_path", 500)->nullable();
            $table->text("description")->nullable();
            $table->enum("scope", ["sale", "purchase", "both"])->default("both");
            $table->boolean("requires_reference")->default(false);
            $table->boolean("supports_variants")->default(false);
            $table->boolean("allows_partial_payment")->default(true);
            $table->boolean("is_default")->default(false);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->unique(["code"], "payment_methods_code_uq");
            $table->index(["scope", "status", "name"], "payment_methods_scope_status_idx");

        });
        Schema::create("payment_method_variants", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("payment_method_id");
            $table->string("code", 40);
            $table->string("name", 150);
            $table->string("sunat_code", 10)->nullable();
            $table->string("image_path", 500)->nullable();
            $table->text("description")->nullable();
            $table->boolean("requires_reference")->default(true);
            $table->boolean("is_default")->default(false);
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("payment_method_id")->references("id")->on("payment_methods")->onDelete("cascade");
            $table->unique(["payment_method_id", "code"], "payment_method_variants_method_code_uq");
            $table->index(["payment_method_id", "status", "name"], "payment_method_variants_method_status_idx");

        });
        Schema::create("company_socials_media", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("company_id");
            $table->enum("type", ["web", "facebook", "instagram", "tiktok", "whatsapp", "other"])->default("other");
            $table->text("link");
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("company_id")->references("id")->on("companies")->onDelete("cascade");

        });
        Schema::create("branches", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("company_id");
            $table->string("internal_code", 255);
            $table->string("name", 255);
            $table->string("address", 255)->nullable();
            $table->string("reference", 255)->nullable();
            $table->string("telephone", 255)->nullable();
            $table->string("email", 255)->nullable();
            $table->integer("capacity")->nullable();
            $table->text("map_url")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("deleted_at")->nullable();
            $table->integer("deleted_by")->nullable();

            $table->foreign("company_id")->references("id")->on("companies")->onDelete("cascade");
            $table->unique(["company_id", "internal_code"]);

        });
        Schema::create("user_branches", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("branch_id");
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->unique(["user_id", "branch_id"]);
            $table->index(["user_id", "status"], "user_branches_access_idx");

        });

        Schema::create("business_audit_logs", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id")->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->string("module", 80);
            $table->string("action", 80);
            $table->string("auditable_type", 150)->nullable();
            $table->unsignedBigInteger("auditable_id")->nullable();
            $table->string("summary", 500);
            $table->json("before_data")->nullable();
            $table->json("after_data")->nullable();
            $table->json("context")->nullable();
            $table->string("ip_address", 45)->nullable();
            $table->string("user_agent", 500)->nullable();
            $table->timestamp("occurred_at")->useCurrent();

            $table->foreign("branch_id")->references("id")->on("branches")->nullOnDelete();
            $table->foreign("user_id")->references("id")->on("users")->nullOnDelete();

        });

        Schema::create("series", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("document_type_id");
            $table->string("code", 255);
            $table->integer("number");
            $table->integer("init")->default(1);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->foreign("document_type_id")->references("id")->on("document_types")->onDelete("cascade");

        });
        Schema::create("brands", function(Blueprint $table) {

            $table->id();
            $table->string("internal_code", 255);
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->string("logo_path", 500)->nullable();
            $table->string("origin_country_code", 3)->nullable();
            $table->string("website_url", 500)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->unique(["internal_code"], "brands_code_uq");
            $table->index(["status", "name"], "brands_status_name_idx");

        });
        Schema::create("items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("brand_id")->nullable();
            $table->string("internal_code", 255);
            $table->string("barcode", 13)->nullable();
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->decimal("price", 15, 3);
            $table->boolean("price_includes_tax")->default(true);
            $table->boolean("igv_exempt")->default(false);
            $table->decimal("min_price", 15, 3)->nullable();
            $table->decimal("max_price", 15, 3)->nullable();
            $table->unsignedBigInteger("currency_id");
            $table->enum("type", ["product", "service", "subscription"])->default("product");
            $table->enum("duration_type", ["hour", "day", "today", "month", "year"])->nullable();
            $table->integer("duration_value")->nullable();
            $table->unsignedInteger("estimated_duration_minutes")->nullable();
            $table->boolean("capacity_control_enabled")->default(false);
            $table->unsignedInteger("capacity_limit")->nullable();
            $table->unsignedInteger("capacity_used")->default(0);
            $table->dateTime("expires_at")->nullable();
            $table->decimal("commission_rate", 6, 3)->nullable();
            $table->enum("commission_type", ["none", "percentage", "fixed"])->default("none");
            $table->decimal("commission_value", 15, 3)->default(0);
            $table->unsignedSmallInteger("attendance_limit_per_day")->nullable();
            $table->json("benefits")->nullable();
            $table->json("restrictions")->nullable();
            $table->boolean("see_my_web")->nullable()->default(true);
            $table->boolean("see_my_web_price")->nullable()->default(false);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("brand_id")->references("id")->on("brands")->nullOnDelete();
            $table->foreign("currency_id")->references("id")->on("currencies")->restrictOnDelete();
            $table->unique(["type", "internal_code"], "items_type_code_uq");
            $table->unique(["barcode"], "items_barcode_uq");
            $table->index(["status", "type", "name"], "items_status_type_name_idx");
            $table->index(["status", "expires_at"], "items_status_expiration_idx");
            $table->index(["brand_id", "status"], "items_brand_status_idx");

        });
        Schema::create("asset_categories", function(Blueprint $table) {

            $table->id();
            $table->string("name", 150);
            $table->string("description", 500)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

        });
        Schema::create("assets", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("asset_category_id")->nullable();
            $table->string("internal_code", 255);
            $table->string("patrimonial_code", 100)->nullable();
            $table->string("serial_number", 150)->nullable();
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->enum("management_type", ["unit", "stock"])->default("stock");
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("asset_category_id")->references("id")->on("asset_categories")->nullOnDelete();

        });
        Schema::create("categories", function(Blueprint $table) {

            $table->id();
            $table->string("internal_code", 255);
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->unsignedSmallInteger("sort_order")->default(1);
            $table->boolean("is_public")->default(true);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->unique(["internal_code"], "categories_code_uq");
            $table->index(["status", "sort_order", "name"], "categories_status_order_idx");

        });
        Schema::create("category_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("category_id");
            $table->unsignedBigInteger("item_id");
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("category_id")->references("id")->on("categories")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->onDelete("cascade");
            $table->unique(["category_id", "item_id"], "category_items_category_item_uq");
            $table->index(["item_id", "status"], "category_items_item_status_idx");

        });
        Schema::create("customers", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("identity_document_type_id");
            $table->string("document_number", 255);
            $table->string("name", 255);
            $table->string("email", 255)->nullable();
            $table->string("phone_number", 255)->nullable();
            $table->string("emergency_contact_name", 255)->nullable();
            $table->string("emergency_contact_phone", 50)->nullable();
            $table->text("medical_notes")->nullable();
            $table->enum("gender", ["male", "female", "other"])->nullable();
            $table->date("birthdate")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("identity_document_type_id")->references("id")->on("identity_document_types")->onDelete("cascade");
            $table->unique(
                ["identity_document_type_id", "document_number"],
                "customers_identity_document_unique"
            );

            $table->index(["status", "name"], "customers_operation_search_index");

        });
        Schema::create("warehouses", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->string("name", 255);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->restrictOnDelete();
            $table->unique(["branch_id", "name"], "warehouses_branch_name_uq");
            $table->index(["branch_id", "status", "name"], "warehouses_branch_status_idx");

        });
        Schema::create("cash_registers", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->string("code", 30)->nullable();
            $table->string("name", 255);
            $table->boolean("is_main")->default(false);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->unique(["branch_id", "name"], "cash_registers_branch_name_uq");
            $table->index(["branch_id", "status", "name"], "cash_registers_branch_status_idx");

        });

        Schema::create("role_branches", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("role_id");
            $table->unsignedBigInteger("branch_id");
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamps();
            $table->integer("created_by")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("role_id")->references("id")->on("roles")->onDelete("cascade");
            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->unique(["role_id", "branch_id"]);
            $table->index(["role_id", "status"], "role_branches_access_idx");

        });

        Schema::create("role_cash_registers", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("role_id");
            $table->unsignedBigInteger("cash_register_id");
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamps();
            $table->integer("created_by")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("role_id")->references("id")->on("roles")->onDelete("cascade");
            $table->foreign("cash_register_id")->references("id")->on("cash_registers")->onDelete("cascade");
            $table->unique(["role_id", "cash_register_id"]);
            $table->index(["role_id", "status"], "role_cash_registers_access_idx");

        });

        Schema::create("role_warehouses", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("role_id");
            $table->unsignedBigInteger("warehouse_id");
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamps();
            $table->integer("created_by")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("role_id")->references("id")->on("roles")->onDelete("cascade");
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->onDelete("cascade");
            $table->unique(["role_id", "warehouse_id"]);
            $table->index(["role_id", "status"], "role_warehouses_access_idx");

        });

        Schema::create("user_cash_registers", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("cash_register_id");
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamps();
            $table->integer("created_by")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->foreign("cash_register_id")->references("id")->on("cash_registers")->onDelete("cascade");
            $table->unique(["user_id", "cash_register_id"]);
            $table->index(["user_id", "status"], "user_cash_registers_access_idx");

        });

        Schema::create("user_warehouses", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("warehouse_id");
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamps();
            $table->integer("created_by")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->onDelete("cascade");
            $table->unique(["user_id", "warehouse_id"]);
            $table->index(["user_id", "status"], "user_warehouses_access_idx");

        });

        Schema::create("cash_sessions", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("cash_register_id");
            $table->unsignedBigInteger("opened_by");
            $table->unsignedBigInteger("closed_by")->nullable();
            $table->timestamp("opened_at")->useCurrent();
            $table->timestamp("closed_at")->nullable();
            $table->decimal("opening_amount", 15, 3)->default(0);
            $table->decimal("expected_amount", 15, 3)->default(0);
            $table->decimal("counted_amount", 15, 3)->default(0);
            $table->decimal("difference_amount", 15, 3)->default(0);
            $table->text("observation")->nullable();
            $table->enum("status", ["open", "closed", "canceled"])->default("open");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->foreign("cash_register_id")->references("id")->on("cash_registers")->onDelete("cascade");
            $table->foreign("opened_by")->references("id")->on("users")->onDelete("cascade");
            $table->foreign("closed_by")->references("id")->on("users")->nullOnDelete();

        });

        Schema::create("cash_session_payments", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("cash_session_id");
            $table->unsignedBigInteger("payment_method_id")->nullable();
            $table->string("payment_method_name", 255);
            $table->decimal("expected_amount", 15, 3)->default(0);
            $table->decimal("counted_amount", 15, 3)->default(0);
            $table->decimal("difference_amount", 15, 3)->default(0);
            $table->text("note")->nullable();
            $table->enum("status", ["active", "canceled", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("cash_session_id")->references("id")->on("cash_sessions")->onDelete("cascade");
            $table->foreign("payment_method_id")->references("id")->on("payment_methods")->nullOnDelete();

        });

        Schema::create("cash_movements", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("cash_session_id")->nullable();
            $table->unsignedBigInteger("payment_method_id")->nullable();
            $table->unsignedBigInteger("user_id");
            $table->enum("movement_type", ["opening", "sale", "purchase", "expense", "income", "withdrawal", "adjustment", "closing"])->default("sale");
            $table->string("origin_type", 60)->nullable();
            $table->unsignedBigInteger("origin_id")->nullable();
            $table->decimal("amount", 15, 3)->default(0);
            $table->string("reference", 100)->nullable();
            $table->text("note")->nullable();
            $table->timestamp("occurred_at")->useCurrent();
            $table->enum("status", ["active", "canceled", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->foreign("cash_session_id")->references("id")->on("cash_sessions")->nullOnDelete();
            $table->foreign("payment_method_id")->references("id")->on("payment_methods")->nullOnDelete();
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");

        });

        Schema::create("warehouse_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("warehouse_id");
            $table->unsignedBigInteger("item_id");
            $table->decimal("quantity", 15, 3)->default(0);
            $table->decimal("minimum_stock", 15, 3)->default(0);
            $table->decimal("average_cost", 15, 3)->default(0);
            $table->decimal("inventory_value", 15, 3)->default(0);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("warehouse_id")->references("id")->on("warehouses")->restrictOnDelete();
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->unique(["warehouse_id", "item_id"], "warehouse_items_warehouse_item_uq");
            $table->index(["item_id", "status", "warehouse_id"], "warehouse_items_item_status_idx");

        });

        // Historial inmutable de entradas, salidas y correcciones de inventario.
        Schema::create("inventory_movements", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("warehouse_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("user_id")->nullable();
            $table->string("movement_type", 30);
            $table->string("origin_type", 50);
            $table->unsignedBigInteger("origin_id")->nullable();
            $table->decimal("quantity_before", 15, 3);
            $table->decimal("quantity_change", 15, 3);
            $table->decimal("quantity_after", 15, 3);
            $table->decimal("unit_cost", 15, 3)->default(0);
            $table->decimal("value_before", 15, 3)->default(0);
            $table->decimal("value_change", 15, 3)->default(0);
            $table->decimal("value_after", 15, 3)->default(0);
            $table->string("reason", 255);
            $table->json("metadata")->nullable();
            $table->timestamp("created_at")->useCurrent();

            $table->foreign("warehouse_id")->references("id")->on("warehouses")->restrictOnDelete();
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->foreign("user_id")->references("id")->on("users")->nullOnDelete();
            $table->index(["created_at", "id"], "inventory_movements_date_idx");
            $table->index(["warehouse_id", "created_at", "id"], "inventory_movements_warehouse_date_idx");
            $table->index(["item_id", "created_at", "id"], "inventory_movements_item_date_idx");
            $table->index(["origin_type", "origin_id"], "inventory_movements_origin_idx");

        });

        Schema::create("inventory_stock_alerts", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("warehouse_item_id");
            $table->decimal("quantity", 15, 3);
            $table->decimal("minimum_stock", 15, 3);
            $table->enum("status", ["open", "resolved"])->default("open");
            $table->timestamp("detected_at")->useCurrent();
            $table->timestamp("resolved_at")->nullable();
            $table->unsignedBigInteger("resolved_by")->nullable();
            $table->timestamps();

            $table->foreign("warehouse_item_id")->references("id")->on("warehouse_items")->restrictOnDelete();
            $table->foreign("resolved_by")->references("id")->on("users")->nullOnDelete();
            $table->index(["status", "detected_at"], "inventory_alerts_status_date_idx");
            $table->index(["warehouse_item_id", "status", "id"], "inventory_alerts_warehouse_item_status_idx");

        });
        Schema::create("inventory_guides", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("warehouse_id");
            $table->string("number", 40);
            $table->enum("guide_type", ["entry", "exit"]);
            $table->date("issue_date");
            $table->string("reason", 255);
            $table->string("reference", 100)->nullable();
            $table->enum("status", ["confirmed", "canceled"])->default("confirmed");
            $table->timestamp("confirmed_at")->useCurrent();
            $table->unsignedBigInteger("confirmed_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->unsignedBigInteger("canceled_by")->nullable();
            $table->timestamps();

            $table->foreign("warehouse_id")->references("id")->on("warehouses")->restrictOnDelete();
            $table->foreign("confirmed_by")->references("id")->on("users")->nullOnDelete();
            $table->foreign("canceled_by")->references("id")->on("users")->nullOnDelete();
            $table->unique(["number"], "inventory_guides_number_uq");
            $table->index(["warehouse_id", "status", "issue_date", "id"], "inventory_guides_warehouse_status_date_idx");

        });
        Schema::create("inventory_guide_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("inventory_guide_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("inventory_movement_id");
            $table->decimal("quantity", 15, 3);
            $table->decimal("unit_cost", 15, 3)->default(0);
            $table->timestamps();

            $table->foreign("inventory_guide_id")->references("id")->on("inventory_guides")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->foreign("inventory_movement_id")->references("id")->on("inventory_movements")->restrictOnDelete();
            $table->unique(["inventory_guide_id", "item_id"], "inventory_guide_items_guide_item_uq");
            $table->unique("inventory_movement_id", "inventory_guide_items_movement_uq");

        });
        Schema::create("recipe_dishes", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("item_id");
            $table->decimal("yield_quantity", 15, 3)->default(1);
            $table->decimal("waste_percentage", 15, 3)->default(0);
            $table->text("preparation_notes")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("item_id")->references("id")->on("items")->onDelete("cascade");

        });

        Schema::create("recipe_dish_components", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("recipe_dish_id");
            $table->unsignedBigInteger("item_id");
            $table->decimal("quantity", 15, 3);
            $table->decimal("waste_percentage", 15, 3)->default(0);
            $table->string("note", 255)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("recipe_dish_id")->references("id")->on("recipe_dishes")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->onDelete("cascade");

        });

        Schema::create("recipe_toppings", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("currency_id");
            $table->unsignedBigInteger("item_id")->nullable();
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->decimal("price", 15, 3)->default(0);
            $table->unsignedInteger("max_quantity")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("currency_id")->references("id")->on("currencies")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->nullOnDelete();

        });

        Schema::create("recipe_dish_toppings", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("recipe_dish_id");
            $table->unsignedBigInteger("recipe_topping_id");
            $table->boolean("is_default")->default(false);
            $table->unsignedInteger("min_quantity")->default(0);
            $table->unsignedInteger("max_quantity")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("recipe_dish_id")->references("id")->on("recipe_dishes")->onDelete("cascade");
            $table->foreign("recipe_topping_id")->references("id")->on("recipe_toppings")->onDelete("cascade");

        });

        Schema::create("recipe_topping_components", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("recipe_topping_id");
            $table->unsignedBigInteger("item_id");
            $table->decimal("quantity", 15, 3);
            $table->decimal("waste_percentage", 15, 3)->default(0);
            $table->string("note", 255)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("recipe_topping_id")->references("id")->on("recipe_toppings")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->onDelete("cascade");

        });

        Schema::create("recipe_dish_options", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("recipe_dish_id");
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->unsignedInteger("max_portions")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("recipe_dish_id")->references("id")->on("recipe_dishes")->onDelete("cascade");

        });

        Schema::create("recipe_dish_option_components", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("recipe_dish_option_id");
            $table->unsignedBigInteger("item_id");
            $table->decimal("quantity", 15, 3);
            $table->decimal("waste_percentage", 15, 3)->default(0);
            $table->string("note", 255)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("recipe_dish_option_id")->references("id")->on("recipe_dish_options")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->onDelete("cascade");

        });

        Schema::create("recipe_waste_records", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("recipe_dish_id")->nullable();
            $table->unsignedBigInteger("warehouse_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("inventory_movement_id");
            $table->decimal("quantity", 15, 3);
            $table->decimal("unit_cost", 15, 3)->default(0);
            $table->decimal("total_cost", 15, 3)->default(0);
            $table->string("reason", 500);
            $table->timestamp("occurred_at")->useCurrent();
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->unsignedBigInteger("created_by")->nullable();

            $table->foreign("recipe_dish_id")->references("id")->on("recipe_dishes")->nullOnDelete();
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->restrictOnDelete();
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->foreign("inventory_movement_id")->references("id")->on("inventory_movements")->restrictOnDelete();
            $table->foreign("created_by")->references("id")->on("users")->nullOnDelete();

        });

        Schema::create("cash_session_inventory_counts", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("cash_session_id");
            $table->unsignedBigInteger("warehouse_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("inventory_movement_id")->nullable();
            $table->decimal("system_quantity", 15, 3)->default(0);
            $table->decimal("counted_quantity", 15, 3)->default(0);
            $table->decimal("difference_quantity", 15, 3)->default(0);
            $table->text("observation")->nullable();
            $table->enum("status", ["pending", "adjusted", "ignored", "canceled"])->default("pending");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->foreign("cash_session_id")->references("id")->on("cash_sessions")->onDelete("cascade");
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->onDelete("cascade");
            $table->foreign("inventory_movement_id")->references("id")->on("inventory_movements")->nullOnDelete();

        });
        Schema::create("branch_assets", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("asset_id");
            $table->unsignedBigInteger("currency_id");
            $table->decimal("quantity", 15, 3)->nullable()->default(0);
            $table->decimal("acquisition_value", 15, 3)->nullable()->default(0);
            $table->date("acquisition_date")->nullable();
            $table->text("note")->nullable();
            $table->enum("status", ["active", "maintenance", "retired"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->foreign("asset_id")->references("id")->on("assets")->onDelete("cascade");
            $table->foreign("currency_id")->references("id")->on("currencies")->onDelete("cascade");
            $table->unique(["branch_id", "asset_id"]);

        });
        Schema::create("asset_assignments", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("asset_id");
            $table->unsignedBigInteger("currency_id");
            $table->decimal("quantity", 15, 3)->nullable()->default(0);
            $table->decimal("acquisition_value", 15, 3)->nullable()->default(0);
            $table->date("acquisition_date")->nullable();
            $table->text("note")->nullable();
            $table->enum("status", ["active", "maintenance", "retired"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->foreign("asset_id")->references("id")->on("assets")->onDelete("cascade");
            $table->foreign("currency_id")->references("id")->on("currencies")->onDelete("cascade");

        });
        Schema::create("asset_assignment_logs", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("action_by")->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->unsignedBigInteger("branch_id")->nullable();
            $table->unsignedBigInteger("asset_id");
            $table->unsignedBigInteger("from_user_id")->nullable();
            $table->unsignedBigInteger("to_user_id")->nullable();
            $table->unsignedBigInteger("from_branch_id")->nullable();
            $table->unsignedBigInteger("to_branch_id")->nullable();
            $table->enum("action_type", ["assigned", "transferred", "returned", "retired"]);
            $table->decimal("quantity", 15, 3);
            $table->text("note")->nullable();
            $table->timestamp("action_at")->useCurrent();

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("action_by")->references("id")->on("users")->nullOnDelete();
            $table->foreign("user_id")->references("id")->on("users")->nullOnDelete();
            $table->foreign("branch_id")->references("id")->on("branches")->nullOnDelete();
            $table->foreign("asset_id")->references("id")->on("assets")->restrictOnDelete();
            $table->foreign("from_user_id")->references("id")->on("users")->nullOnDelete();
            $table->foreign("to_user_id")->references("id")->on("users")->nullOnDelete();
            $table->foreign("from_branch_id")->references("id")->on("branches")->nullOnDelete();
            $table->foreign("to_branch_id")->references("id")->on("branches")->nullOnDelete();

        });
        // Initial data lives in 2024_12_31_235959_insert_initial_system_data.php.

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

        Schema::dropIfExists("asset_assignment_logs");
        Schema::dropIfExists("asset_assignments");
        Schema::dropIfExists("branch_assets");
        Schema::dropIfExists("cash_session_inventory_counts");
        Schema::dropIfExists("recipe_waste_records");
        Schema::dropIfExists("recipe_dish_option_components");
        Schema::dropIfExists("recipe_dish_options");
        Schema::dropIfExists("recipe_topping_components");
        Schema::dropIfExists("recipe_dish_toppings");
        Schema::dropIfExists("recipe_toppings");
        Schema::dropIfExists("recipe_dish_components");
        Schema::dropIfExists("recipe_dishes");
        Schema::dropIfExists("inventory_guide_items");
        Schema::dropIfExists("inventory_guides");
        Schema::dropIfExists("inventory_stock_alerts");
        Schema::dropIfExists("inventory_movements");
        Schema::dropIfExists("warehouse_items");
        Schema::dropIfExists("cash_movements");
        Schema::dropIfExists("cash_session_payments");
        Schema::dropIfExists("cash_sessions");
        Schema::dropIfExists("user_warehouses");
        Schema::dropIfExists("user_cash_registers");
        Schema::dropIfExists("role_warehouses");
        Schema::dropIfExists("role_cash_registers");
        Schema::dropIfExists("role_branches");
        Schema::dropIfExists("cash_registers");
        Schema::dropIfExists("warehouses");
        Schema::dropIfExists("customers");
        Schema::dropIfExists("category_items");
        Schema::dropIfExists("categories");
        Schema::dropIfExists("assets");
        Schema::dropIfExists("asset_categories");
        Schema::dropIfExists("items");
        Schema::dropIfExists("brands");
        Schema::dropIfExists("series");
        Schema::dropIfExists("business_audit_logs");
        Schema::dropIfExists("user_branches");
        Schema::dropIfExists("branches");
        Schema::dropIfExists("company_socials_media");
        Schema::dropIfExists("payment_method_variants");
        Schema::dropIfExists("payment_methods");
        Schema::dropIfExists("taxes");
        Schema::dropIfExists("company_settings");

    }
};
