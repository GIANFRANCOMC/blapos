<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {

        if(Schema::hasTable("attendances")) {

            DB::statement("ALTER TABLE attendances MODIFY status ENUM('active', 'canceled', 'inactive', 'finalized', 'absent') NOT NULL DEFAULT 'active'");

        }

        Schema::create("loyalty_point_rules", function(Blueprint $table) {

            $table->id();
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->enum("trigger_type", ["sale_total", "item_quantity", "subscription_sale"])->default("sale_total");
            $table->enum("apply_scope", ["all", "product", "service", "subscription", "selected_items"])->default("all");
            $table->decimal("amount_step", 15, 3)->default(1);
            $table->decimal("points_per_amount", 15, 3)->default(0);
            $table->decimal("points_per_unit", 15, 3)->default(0);
            $table->decimal("minimum_sale_total", 15, 3)->default(0);
            $table->timestamp("starts_at")->nullable();
            $table->timestamp("ends_at")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

        });

        Schema::create("loyalty_point_rule_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("loyalty_point_rule_id");
            $table->unsignedBigInteger("item_id");
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();

            $table->foreign("loyalty_point_rule_id")->references("id")->on("loyalty_point_rules")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->onDelete("cascade");

        });

        Schema::create("customer_point_balances", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("customer_id");
            $table->decimal("points_balance", 15, 3)->default(0);
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("customer_id")->references("id")->on("customers")->onDelete("cascade");
            $table->unique(["customer_id"]);

        });

        Schema::create("customer_point_movements", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("customer_id");
            $table->unsignedBigInteger("loyalty_point_rule_id")->nullable();
            $table->unsignedBigInteger("sale_header_id")->nullable();
            $table->unsignedBigInteger("sale_body_id")->nullable();
            $table->enum("movement_type", ["earned", "redeemed", "adjustment", "reversal"])->default("earned");
            $table->enum("basis_type", ["sale_total", "item_quantity", "manual"])->default("sale_total");
            $table->decimal("basis_amount", 15, 3)->default(0);
            $table->decimal("points", 15, 3);
            $table->string("description", 500)->nullable();
            $table->timestamp("occurred_at")->useCurrent();
            $table->enum("status", ["active", "canceled"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("customer_id")->references("id")->on("customers")->onDelete("cascade");
            $table->foreign("loyalty_point_rule_id")->references("id")->on("loyalty_point_rules")->nullOnDelete();
            $table->foreign("sale_header_id")->references("id")->on("sales_header")->nullOnDelete();
            $table->foreign("sale_body_id")->references("id")->on("sales_body")->nullOnDelete();

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

        Schema::dropIfExists("customer_point_movements");
        Schema::dropIfExists("customer_point_balances");
        Schema::dropIfExists("loyalty_point_rule_items");
        Schema::dropIfExists("loyalty_point_rules");

        if(Schema::hasTable("attendances")) {

            DB::statement("ALTER TABLE attendances MODIFY status ENUM('active', 'canceled', 'inactive', 'finalized') NOT NULL DEFAULT 'active'");

        }

    }
};
