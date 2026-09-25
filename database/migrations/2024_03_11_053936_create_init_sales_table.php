<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {

        Schema::create("sale_delivery_methods", function(Blueprint $table) {

            $table->id();
            $table->string("code", 50);
            $table->string("name", 100);
            $table->string("description", 300)->nullable();
            $table->unsignedSmallInteger("sort_order")->default(0);
            $table->boolean("is_default")->default(false);
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->unique(["code"], "sale_delivery_methods_code_uq");
            $table->index(["status", "sort_order"], "sale_delivery_methods_status_idx");

        });

        Schema::create("sales_header", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("serie_id");
            $table->integer("sequential");
            $table->unsignedBigInteger("holder_id");
            $table->unsignedBigInteger("seller_id");
            $table->unsignedBigInteger("currency_id");
            $table->unsignedBigInteger("warehouse_id")->nullable();
            $table->unsignedBigInteger("delivery_method_id")->nullable();
            $table->unsignedBigInteger("cash_session_id")->nullable();
            $table->date("issue_date");
            $table->enum("delivery_mode", ["immediate", "pending"])->default("immediate");
            $table->enum("delivery_status", ["pending", "partial", "delivered", "canceled"])->default("delivered");
            $table->timestamp("delivered_at")->nullable();
            $table->unsignedBigInteger("delivered_by")->nullable();
            $table->string("delivery_observation", 500)->nullable();
            $table->decimal("subtotal", 15, 3)->default(0);
            $table->decimal("tax", 15, 3)->default(0);
            $table->decimal("commission_total", 15, 3)->default(0);
            $table->decimal("total", 15, 3);
            $table->decimal("paid_amount", 15, 3)->default(0);
            $table->decimal("balance_due", 15, 3)->default(0);
            $table->enum("payment_status", ["unpaid", "partial", "paid", "overpaid"])->default("paid");
            $table->enum("payment_modality", ["paid_now", "cash_on_delivery", "installments"])->default("paid_now");
            $table->decimal("installment_extra_percentage", 15, 3)->default(0);
            $table->decimal("installment_extra_amount", 15, 3)->default(0);
            $table->text("observation")->nullable();
            $table->enum("status", ["active", "canceled", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("serie_id")->references("id")->on("series")->restrictOnDelete();
            $table->foreign("holder_id")->references("id")->on("customers")->restrictOnDelete();
            $table->foreign("seller_id")->references("id")->on("users")->restrictOnDelete();
            $table->foreign("currency_id")->references("id")->on("currencies")->restrictOnDelete();
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->nullOnDelete();
            $table->foreign("delivery_method_id", "sales_header_delivery_method_fk")
                ->references("id")
                ->on("sale_delivery_methods")
                ->nullOnDelete();

            $table->foreign("cash_session_id")->references("id")->on("cash_sessions")->nullOnDelete();
            $table->foreign("delivered_by")->references("id")->on("users")->nullOnDelete();
            $table->unique(["serie_id", "sequential"], "sales_header_serie_sequential_uq");
            $table->index(["status", "issue_date", "id"], "sales_header_status_date_idx");
            $table->index(["holder_id", "status", "issue_date", "id"], "sales_header_holder_status_date_idx");
            $table->index(["seller_id", "status", "issue_date", "id"], "sales_header_seller_status_date_idx");
            $table->index(["warehouse_id", "status", "issue_date", "id"], "sales_header_warehouse_status_date_idx");
            $table->index(["delivery_method_id", "status", "issue_date"], "sales_header_delivery_method_idx");
            $table->index(["delivery_status", "status", "issue_date", "id"], "sales_header_delivery_status_date_idx");
            $table->index(["payment_status", "status", "issue_date", "id"], "sales_header_payment_status_date_idx");

        });

        Schema::create("series_correlative_movements", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("serie_id");
            $table->unsignedBigInteger("sale_header_id");
            $table->unsignedBigInteger("user_id")->nullable();
            $table->integer("sequential");
            $table->enum("action", ["issued", "canceled"]);
            $table->enum("source", ["sale", "pos"])->default("sale");
            $table->string("note", 500)->nullable();
            $table->json("metadata")->nullable();
            $table->timestamp("occurred_at")->useCurrent();
            $table->timestamp("created_at")->useCurrent()->nullable();

            $table->foreign("serie_id")->references("id")->on("series")->restrictOnDelete();
            $table->foreign("sale_header_id")->references("id")->on("sales_header")->restrictOnDelete();
            $table->foreign("user_id")->references("id")->on("users")->nullOnDelete();
            $table->unique(
                ["serie_id", "sequential", "action"],
                "series_corr_company_serie_seq_action_uq"
            );

            $table->index(["sale_header_id", "action", "occurred_at"], "series_corr_sale_action_date_idx");

        });
        Schema::create("sales_body", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("sale_header_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("currency_id");
            $table->string("name", 255);
            $table->decimal("quantity", 15, 3);
            $table->decimal("price", 15, 3);
            $table->boolean("price_includes_tax")->default(true);
            $table->boolean("igv_exempt")->default(false);
            $table->decimal("total", 15, 3);
            $table->enum("commission_type", ["none", "percentage", "fixed"])->default("none");
            $table->decimal("commission_value", 15, 3)->default(0);
            $table->decimal("commission_amount", 15, 3)->default(0);
            $table->unsignedBigInteger("customer_id");
            $table->enum("type", ["product", "service", "subscription"])->default("product");
            $table->text("observation")->nullable();
            $table->text("extras");
            $table->enum("status", ["active", "canceled", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("sale_header_id")->references("id")->on("sales_header")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->foreign("currency_id")->references("id")->on("currencies")->restrictOnDelete();
            $table->foreign("customer_id")->references("id")->on("customers")->restrictOnDelete();
            $table->index(["sale_header_id", "status", "id"], "sales_body_header_status_idx");
            $table->index(["item_id", "status", "created_at", "id"], "sales_body_item_status_date_idx");
            $table->index(["customer_id", "status", "created_at", "id"], "sales_body_customer_status_date_idx");

        });

        Schema::create("sale_taxes", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("sale_header_id");
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

            $table->foreign("sale_header_id")->references("id")->on("sales_header")->onDelete("cascade");
            $table->foreign("tax_id")->references("id")->on("taxes")->nullOnDelete();
            $table->index(["sale_header_id", "status"], "sale_taxes_header_status_idx");

        });

        Schema::create("sale_payments", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("sale_header_id");
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

            $table->foreign("sale_header_id")->references("id")->on("sales_header")->onDelete("cascade");
            $table->foreign("payment_method_id")->references("id")->on("payment_methods")->nullOnDelete();
            $table->foreign("payment_method_variant_id")->references("id")->on("payment_method_variants")->nullOnDelete();
            $table->index(["sale_header_id", "status"], "sale_payments_header_status_idx");
            $table->index(["payment_method_id", "status", "created_at"], "sale_payments_method_status_date_idx");

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

        Schema::dropIfExists("sale_payments");
        Schema::dropIfExists("sale_taxes");
        Schema::dropIfExists("sales_body");
        Schema::dropIfExists("series_correlative_movements");
        Schema::dropIfExists("sales_header");
        Schema::dropIfExists("sale_delivery_methods");

    }
};
