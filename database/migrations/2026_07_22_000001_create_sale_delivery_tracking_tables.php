<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    public function up(): void {

        Schema::create("sale_deliveries", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("sale_header_id");
            $table->unsignedBigInteger("warehouse_id")->nullable();
            $table->decimal("total_quantity", 15, 3)->default(0);
            $table->decimal("delivered_quantity", 15, 3)->default(0);
            $table->decimal("pending_quantity", 15, 3)->default(0);
            $table->enum("status", ["pending", "partial", "delivered", "canceled"])->default("pending");
            $table->timestamp("last_delivered_at")->nullable();
            $table->unsignedBigInteger("last_delivered_by")->nullable();
            $table->string("observation", 500)->nullable();
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("sale_header_id")->references("id")->on("sales_header")->onDelete("cascade");
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->nullOnDelete();
            $table->foreign("last_delivered_by")->references("id")->on("users")->nullOnDelete();
            $table->unique(["sale_header_id"], "sale_deliveries_sale_uq");
            $table->index(["status", "created_at", "id"], "sale_deliveries_status_date_idx");
            $table->index(["warehouse_id", "status", "id"], "sale_deliveries_warehouse_status_idx");

        });

        Schema::create("sale_delivery_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("sale_delivery_id");
            $table->unsignedBigInteger("sale_body_id");
            $table->unsignedBigInteger("item_id");
            $table->decimal("quantity_ordered", 15, 3)->default(0);
            $table->decimal("quantity_delivered", 15, 3)->default(0);
            $table->decimal("quantity_pending", 15, 3)->default(0);
            $table->enum("status", ["pending", "partial", "delivered", "canceled"])->default("pending");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("sale_delivery_id")->references("id")->on("sale_deliveries")->onDelete("cascade");
            $table->foreign("sale_body_id")->references("id")->on("sales_body")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->unique(["sale_delivery_id", "sale_body_id"], "sale_delivery_items_unique_body");
            $table->index(["sale_delivery_id", "status", "id"], "sale_delivery_items_delivery_status_idx");

        });

        Schema::create("sale_delivery_events", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("sale_delivery_id");
            $table->unsignedBigInteger("warehouse_id");
            $table->unsignedBigInteger("delivered_by")->nullable();
            $table->timestamp("delivered_at")->useCurrent();
            $table->decimal("total_quantity", 15, 3)->default(0);
            $table->string("observation", 500)->nullable();
            $table->enum("status", ["active", "canceled"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();

            $table->foreign("sale_delivery_id")->references("id")->on("sale_deliveries")->onDelete("cascade");
            $table->foreign("warehouse_id")->references("id")->on("warehouses")->restrictOnDelete();
            $table->foreign("delivered_by")->references("id")->on("users")->nullOnDelete();
            $table->index(["sale_delivery_id", "status", "delivered_at", "id"], "sale_delivery_events_delivery_status_idx");

        });

        Schema::create("sale_delivery_event_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("sale_delivery_event_id");
            $table->unsignedBigInteger("sale_delivery_item_id");
            $table->unsignedBigInteger("sale_body_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("inventory_movement_id")->nullable();
            $table->decimal("quantity", 15, 3)->default(0);
            $table->timestamp("created_at")->useCurrent()->nullable();

            $table->foreign("sale_delivery_event_id")->references("id")->on("sale_delivery_events")->onDelete("cascade");
            $table->foreign("sale_delivery_item_id")->references("id")->on("sale_delivery_items")->onDelete("cascade");
            $table->foreign("sale_body_id")->references("id")->on("sales_body")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->foreign("inventory_movement_id")->references("id")->on("inventory_movements")->nullOnDelete();
            $table->unique(["sale_delivery_event_id", "sale_delivery_item_id"], "sale_delivery_event_items_event_item_uq");
            $table->index(["inventory_movement_id"], "sale_delivery_event_items_movement_idx");

        });

    }

    public function down(): void {

        Schema::dropIfExists("sale_delivery_event_items");
        Schema::dropIfExists("sale_delivery_events");
        Schema::dropIfExists("sale_delivery_items");
        Schema::dropIfExists("sale_deliveries");

    }
};
