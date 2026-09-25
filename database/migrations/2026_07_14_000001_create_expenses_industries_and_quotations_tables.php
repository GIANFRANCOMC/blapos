<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    public function up(): void {

        Schema::create("business_industries", function(Blueprint $table) {

            $table->id();
            $table->string("slug", 120);
            $table->string("name", 255);
            $table->string("description", 500)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->unique(["slug"]);

        });

        Schema::table("companies", function(Blueprint $table) {

            $table->unsignedBigInteger("business_industry_id")->nullable()->after("currency_id");
            $table->foreign("business_industry_id")->references("id")->on("business_industries")->nullOnDelete();

        });

        Schema::create("business_industry_module_sets", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("business_industry_id");
            $table->unsignedBigInteger("sub_section_id");
            $table->boolean("is_enabled_by_default")->default(true);
            $table->string("reason", 500)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("business_industry_id")->references("id")->on("business_industries")->onDelete("cascade");
            $table->foreign("sub_section_id")->references("id")->on("sub_sections")->onDelete("cascade");
            $table->unique(["business_industry_id", "sub_section_id"], "business_industry_module_set_unique");

        });

        Schema::create("misc_expense_categories", function(Blueprint $table) {

            $table->id();
            $table->string("name", 150);
            $table->string("description", 500)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();


        });

        Schema::create("misc_expenses", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id")->nullable();
            $table->unsignedBigInteger("cash_session_id")->nullable();
            $table->unsignedBigInteger("payment_method_id")->nullable();
            $table->unsignedBigInteger("currency_id");
            $table->unsignedBigInteger("misc_expense_category_id")->nullable();
            $table->unsignedBigInteger("responsible_user_id")->nullable();
            $table->date("expense_date");
            $table->string("reference", 100)->nullable();
            $table->string("concept", 255);
            $table->decimal("amount", 15, 3);
            $table->text("description")->nullable();
            $table->text("observation")->nullable();
            $table->enum("status", ["active", "canceled"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->nullOnDelete();
            $table->foreign("cash_session_id")->references("id")->on("cash_sessions")->nullOnDelete();
            $table->foreign("payment_method_id")->references("id")->on("payment_methods")->nullOnDelete();
            $table->foreign("currency_id")->references("id")->on("currencies")->restrictOnDelete();
            $table->foreign("misc_expense_category_id")->references("id")->on("misc_expense_categories")->nullOnDelete();
            $table->foreign("responsible_user_id")->references("id")->on("users")->nullOnDelete();

        });

        Schema::create("quotation_headers", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id")->nullable();
            $table->unsignedBigInteger("holder_id");
            $table->unsignedBigInteger("seller_id");
            $table->unsignedBigInteger("currency_id");
            $table->unsignedBigInteger("sale_header_id")->nullable();
            $table->string("reference", 100);
            $table->date("issue_date");
            $table->date("valid_until")->nullable();
            $table->decimal("subtotal", 15, 3)->default(0);
            $table->decimal("tax", 15, 3)->default(0);
            $table->decimal("total", 15, 3)->default(0);
            $table->text("observation")->nullable();
            $table->enum("status", ["draft", "sent", "accepted", "converted", "canceled", "expired"])->default("draft");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("converted_at")->nullable();
            $table->integer("converted_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->nullOnDelete();
            $table->foreign("holder_id")->references("id")->on("customers")->restrictOnDelete();
            $table->foreign("seller_id")->references("id")->on("users")->restrictOnDelete();
            $table->foreign("currency_id")->references("id")->on("currencies")->restrictOnDelete();
            $table->foreign("sale_header_id")->references("id")->on("sales_header")->nullOnDelete();
            $table->unique(["reference"], "quotation_headers_reference_uq");
            $table->index(["status", "issue_date", "id"], "quotation_headers_status_date_idx");
            $table->index(["holder_id", "status", "issue_date", "id"], "quotation_headers_holder_status_date_idx");

        });

        Schema::create("quotation_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("quotation_header_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("currency_id");
            $table->string("name", 255);
            $table->enum("type", ["product", "service", "subscription"])->default("product");
            $table->decimal("quantity", 15, 3);
            $table->decimal("price", 15, 3);
            $table->boolean("price_includes_tax")->default(true);
            $table->boolean("igv_exempt")->default(false);
            $table->decimal("total", 15, 3);
            $table->text("observation")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("quotation_header_id")->references("id")->on("quotation_headers")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->foreign("currency_id")->references("id")->on("currencies")->restrictOnDelete();
            $table->index(["quotation_header_id", "status", "id"], "quotation_items_header_status_idx");
            $table->index(["item_id", "status", "id"], "quotation_items_item_status_idx");

        });

        Schema::create("quotation_taxes", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("quotation_header_id");
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
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("quotation_header_id")->references("id")->on("quotation_headers")->onDelete("cascade");
            $table->foreign("tax_id")->references("id")->on("taxes")->nullOnDelete();
            $table->index(["quotation_header_id", "status"], "quotation_taxes_header_status_idx");

        });

        Schema::table("sales_header", function(Blueprint $table) {

            $table->unsignedBigInteger("quotation_header_id")->nullable()->after("cash_session_id");
            $table->foreign("quotation_header_id")->references("id")->on("quotation_headers")->nullOnDelete();

        });

    }

    public function down(): void {

        Schema::table("sales_header", function(Blueprint $table) {

            $table->dropForeign(["quotation_header_id"]);
            $table->dropColumn("quotation_header_id");

        });

        Schema::dropIfExists("quotation_taxes");
        Schema::dropIfExists("quotation_items");
        Schema::dropIfExists("quotation_headers");
        Schema::dropIfExists("misc_expenses");
        Schema::dropIfExists("misc_expense_categories");
        Schema::dropIfExists("business_industry_module_sets");

        Schema::table("companies", function(Blueprint $table) {

            $table->dropForeign(["business_industry_id"]);
            $table->dropColumn("business_industry_id");

        });

        Schema::dropIfExists("business_industries");

    }
};
