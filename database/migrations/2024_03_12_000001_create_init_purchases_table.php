<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    public function up(): void {

        Schema::create("suppliers", function(Blueprint $table) {

            $table->id();
            $table->string("document_type", 20)->nullable();
            $table->string("document_number", 30)->nullable();
            $table->string("name", 255);
            $table->string("contact_name", 255)->nullable();
            $table->string("telephone", 30)->nullable();
            $table->string("email", 255)->nullable();
            $table->string("address", 255)->nullable();
            $table->unsignedSmallInteger("payment_term_days")->default(0);
            $table->decimal("credit_limit", 15, 3)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->index(["status", "name", "id"], "suppliers_status_name_idx");
            $table->index(["document_number"], "suppliers_document_number_idx");

        });

        Schema::create("supplier_contacts", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("supplier_id");
            $table->string("name", 255);
            $table->string("position", 100)->nullable();
            $table->string("telephone", 30)->nullable();
            $table->string("email", 255)->nullable();
            $table->boolean("is_primary")->default(false);
            $table->string("status", 20)->default("active");
            $table->timestamps();

            $table->foreign("supplier_id")->references("id")->on("suppliers")->onDelete("cascade");
            $table->index(["supplier_id", "status", "is_primary"], "supplier_contacts_access_idx");

        });

        Schema::create("supplier_bank_accounts", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("supplier_id");
            $table->string("bank_name", 150);
            $table->string("currency_code", 10);
            $table->string("account_number", 100);
            $table->string("interbank_code", 100)->nullable();
            $table->boolean("is_primary")->default(false);
            $table->string("status", 20)->default("active");
            $table->timestamps();

            $table->foreign("supplier_id")->references("id")->on("suppliers")->onDelete("cascade");
            $table->index(["supplier_id", "status", "is_primary"], "supplier_accounts_access_idx");

        });

        Schema::create("purchase_headers", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("supplier_id");
            $table->unsignedBigInteger("warehouse_id");
            $table->unsignedBigInteger("currency_id");
            $table->enum("document_type", ["order", "invoice"])->default("order");
            $table->string("document_series", 20)->nullable();
            $table->string("reference", 40);
            $table->string("document_number", 50)->nullable();
            $table->date("issue_date");
            $table->date("expected_date")->nullable();
            $table->date("due_date")->nullable();
            $table->string("approval_status", 20)->default("approved");
            $table->unsignedBigInteger("approved_by")->nullable();
            $table->timestamp("approved_at")->nullable();
            $table->enum("delivery_mode", ["immediate", "pending"])->default("immediate");
            $table->enum("payment_modality", ["paid_now", "cash_on_delivery", "installments"])->default("paid_now");
            $table->decimal("installment_extra_percentage", 15, 3)->default(0);
            $table->decimal("installment_extra_amount", 15, 3)->default(0);
            $table->decimal("subtotal", 15, 3)->default(0);
            $table->decimal("tax", 15, 3)->default(0);
            $table->decimal("expense_total", 15, 3)->default(0);
            $table->decimal("total", 15, 3)->default(0);
            $table->decimal("paid_amount", 15, 3)->default(0);
            $table->decimal("balance_due", 15, 3)->default(0);
            $table->enum("payment_status", ["unpaid", "partial", "paid", "overpaid"])->default("unpaid");
            $table->text("observation")->nullable();
            $table->enum("status", [
                "confirmed",
                "partial",
                "received",
                "canceled",
            ])->default("confirmed");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();
            $table->foreign("supplier_id")->references("id")->on("suppliers")->onDelete("restrict");
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->onDelete("restrict");
            $table->foreign("currency_id")->references("id")->on("currencies")->onDelete("restrict");
            $table->foreign("approved_by")->references("id")->on("users")->nullOnDelete();
            $table->unique(["reference"], "purchase_headers_reference_uq");
            $table->index(["status", "issue_date", "id"], "purchase_headers_status_date_idx");
            $table->index(["warehouse_id", "status", "expected_date", "id"], "purchase_headers_warehouse_status_date_idx");
            $table->index(["supplier_id", "status", "issue_date", "id"], "purchase_headers_supplier_status_date_idx");
            $table->index(["payment_status", "status", "due_date", "id"], "purchase_headers_payment_status_date_idx");
            $table->index(["approval_status", "status", "id"], "purchase_headers_approval_status_idx");

        });

        Schema::create("purchase_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("purchase_header_id");
            $table->unsignedBigInteger("item_id");
            $table->string("name", 255);
            $table->decimal("quantity", 15, 3);
            $table->decimal("received_quantity", 15, 3)->default(0);
            $table->decimal("unit_cost", 15, 3);
            $table->decimal("subtotal", 15, 3);
            $table->enum("status", ["pending", "partial", "received", "canceled"])->default("pending");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->foreign("purchase_header_id")->references("id")->on("purchase_headers")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->onDelete("restrict");
            $table->index(["purchase_header_id", "status", "id"], "purchase_items_header_status_idx");
            $table->index(["item_id", "status", "created_at", "id"], "purchase_items_item_status_date_idx");

        });

        Schema::create("purchase_receipts", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("purchase_header_id");
            $table->unsignedBigInteger("warehouse_id");
            $table->string("reference", 40);
            $table->dateTime("received_at");
            $table->text("observation")->nullable();
            $table->enum("status", ["received", "canceled"])->default("received");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();
            $table->foreign("purchase_header_id")->references("id")->on("purchase_headers")->onDelete("cascade");
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->onDelete("restrict");
            $table->unique(["reference"], "purchase_receipts_reference_uq");
            $table->index(["purchase_header_id", "status", "received_at", "id"], "purchase_receipts_header_status_date_idx");
            $table->index(["warehouse_id", "status", "received_at", "id"], "purchase_receipts_warehouse_status_date_idx");

        });

        Schema::create("purchase_receipt_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("purchase_receipt_id");
            $table->unsignedBigInteger("purchase_item_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("inventory_movement_id")->nullable();
            $table->decimal("quantity", 15, 3);
            $table->decimal("unit_cost", 15, 3);
            $table->decimal("total_cost", 15, 3);
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->foreign("purchase_receipt_id")->references("id")->on("purchase_receipts")->onDelete("cascade");
            $table->foreign("purchase_item_id")->references("id")->on("purchase_items")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->onDelete("restrict");
            $table->foreign("inventory_movement_id")->references("id")->on("inventory_movements")->nullOnDelete();
            $table->unique(["purchase_receipt_id", "purchase_item_id"], "purchase_receipt_items_receipt_item_uq");
            $table->index(["inventory_movement_id"], "purchase_receipt_items_movement_idx");

        });

        Schema::create("purchase_taxes", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("purchase_header_id");
            $table->unsignedBigInteger("tax_id")->nullable();
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->decimal("rate", 15, 3)->default(0);
            $table->enum("calculation_type", ["percentage", "fixed"])->default("percentage");
            $table->enum("operation_type", ["addition", "subtraction"])->default("addition");
            $table->boolean("is_required")->default(true);
            $table->unsignedInteger("quantity")->default(1);
            $table->decimal("base_amount", 15, 3)->default(0);
            $table->decimal("amount", 15, 3)->default(0);
            $table->enum("status", ["active", "canceled", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->foreign("purchase_header_id")->references("id")->on("purchase_headers")->onDelete("cascade");
            $table->foreign("tax_id")->references("id")->on("taxes")->nullOnDelete();
            $table->index(["purchase_header_id", "status"], "purchase_taxes_header_status_idx");

        });

        Schema::create("purchase_payments", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("purchase_header_id");
            $table->unsignedBigInteger("payment_method_id")->nullable();
            $table->unsignedBigInteger("payment_method_variant_id")->nullable();
            $table->string("name", 255);
            $table->decimal("amount", 15, 3)->default(0);
            $table->string("reference", 100)->nullable();
            $table->text("note")->nullable();
            $table->enum("status", ["active", "canceled", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->foreign("purchase_header_id")->references("id")->on("purchase_headers")->onDelete("cascade");
            $table->foreign("payment_method_id")->references("id")->on("payment_methods")->nullOnDelete();
            $table->foreign("payment_method_variant_id")->references("id")->on("payment_method_variants")->nullOnDelete();
            $table->index(["purchase_header_id", "status"], "purchase_payments_header_status_idx");
            $table->index(["payment_method_id", "status", "created_at"], "purchase_payments_method_status_date_idx");

        });

        Schema::create("purchase_expenses", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("purchase_header_id");
            $table->string("expense_type", 40);
            $table->string("name", 150);
            $table->decimal("amount", 15, 3)->default(0);
            $table->string("allocation_method", 20)->default("value");
            $table->string("note", 500)->nullable();
            $table->timestamps();

            $table->foreign("purchase_header_id")->references("id")->on("purchase_headers")->onDelete("cascade");

        });

        Schema::create("purchase_returns", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("purchase_header_id");
            $table->unsignedBigInteger("purchase_receipt_id")->nullable();
            $table->unsignedBigInteger("warehouse_id");
            $table->string("reference", 50);
            $table->dateTime("returned_at");
            $table->string("reason", 500);
            $table->string("status", 20)->default("confirmed");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("purchase_header_id")->references("id")->on("purchase_headers")->restrictOnDelete();
            $table->foreign("purchase_receipt_id")->references("id")->on("purchase_receipts")->nullOnDelete();
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->restrictOnDelete();
            $table->unique(["reference"], "purchase_returns_reference_uq");
            $table->index(["purchase_header_id", "status", "returned_at", "id"], "purchase_returns_header_status_date_idx");

        });

        Schema::create("purchase_return_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("purchase_return_id");
            $table->unsignedBigInteger("purchase_item_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("inventory_movement_id")->nullable();
            $table->decimal("quantity", 15, 3);
            $table->decimal("unit_cost", 15, 3);
            $table->decimal("allocated_expense_total", 15, 3)->default(0);
            $table->decimal("inventory_unit_cost", 15, 3);
            $table->decimal("total_cost", 15, 3);
            $table->timestamp("created_at")->useCurrent()->nullable();

            $table->foreign("purchase_return_id")->references("id")->on("purchase_returns")->onDelete("cascade");
            $table->foreign("purchase_item_id")->references("id")->on("purchase_items")->restrictOnDelete();
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->foreign("inventory_movement_id")->references("id")->on("inventory_movements")->nullOnDelete();

        });

    }

    public function down(): void {

        Schema::dropIfExists("purchase_return_items");
        Schema::dropIfExists("purchase_returns");
        Schema::dropIfExists("purchase_expenses");
        Schema::dropIfExists("purchase_payments");
        Schema::dropIfExists("purchase_taxes");
        Schema::dropIfExists("purchase_receipt_items");
        Schema::dropIfExists("purchase_receipts");
        Schema::dropIfExists("purchase_items");
        Schema::dropIfExists("purchase_headers");
        Schema::dropIfExists("supplier_bank_accounts");
        Schema::dropIfExists("supplier_contacts");
        Schema::dropIfExists("suppliers");

    }
};
