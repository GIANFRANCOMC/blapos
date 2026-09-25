<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    public function up(): void {

        Schema::create("service_floors", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->string("code", 50);
            $table->string("name", 150);
            $table->smallInteger("level_number")->default(1);
            $table->unsignedSmallInteger("sort_order")->default(1);
            $table->string("background_color", 20)->default("#f7f8fa");
            $table->string("description", 500)->nullable();
            $table->string("status", 20)->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->restrictOnDelete();
            $table->unique(["branch_id", "code"]);
            $table->index(["branch_id", "status", "sort_order"], "service_floors_board_index");

        });

        Schema::create("service_stations", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("service_floor_id")->nullable();
            $table->string("code", 50);
            $table->string("name", 150);
            $table->string("station_type", 30)->default("table");
            $table->unsignedSmallInteger("capacity")->default(1);
            $table->decimal("position_x", 6, 3)->default(10);
            $table->decimal("position_y", 6, 3)->default(15);
            $table->string("color", 20)->default("#2899e5");
            $table->string("shape", 20)->default("round");
            $table->string("description", 500)->nullable();
            $table->string("status", 20)->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->restrictOnDelete();
            $table->foreign("service_floor_id")->references("id")->on("service_floors")->restrictOnDelete();
            $table->unique(["branch_id", "code"]);
            $table->index(["branch_id", "service_floor_id", "status"], "service_stations_board_index");

        });

        Schema::create("service_sessions", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("service_station_id")->nullable();
            $table->unsignedBigInteger("customer_id")->nullable();
            $table->unsignedBigInteger("assigned_user_id")->nullable();
            $table->unsignedBigInteger("sale_header_id")->nullable();
            $table->unsignedBigInteger("opened_by");
            $table->unsignedBigInteger("closed_by")->nullable();
            $table->string("reference", 50);
            $table->string("session_type", 30)->default("catalog_service");
            $table->string("status", 20)->default("pending");
            $table->dateTime("started_at")->nullable();
            $table->dateTime("ended_at")->nullable();
            $table->unsignedInteger("duration_minutes")->default(0);
            $table->dateTime("scheduled_at")->nullable();
            $table->dateTime("expected_end_at")->nullable();
            $table->unsignedSmallInteger("tolerance_minutes")->default(0);
            $table->string("queue_code", 30)->nullable();
            $table->string("observation", 500)->nullable();
            $table->string("cancellation_reason", 500)->nullable();

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->restrictOnDelete();
            $table->foreign("service_station_id")->references("id")->on("service_stations")->restrictOnDelete();
            $table->foreign("customer_id")->references("id")->on("customers")->nullOnDelete();
            $table->foreign("assigned_user_id")->references("id")->on("users")->nullOnDelete();
            $table->foreign("sale_header_id")->references("id")->on("sales_header")->restrictOnDelete();
            $table->foreign("opened_by")->references("id")->on("users")->restrictOnDelete();
            $table->foreign("closed_by")->references("id")->on("users")->nullOnDelete();
            $table->unique(["reference"]);
            $table->index(["service_station_id", "status"], "service_sessions_station_status_index");
            $table->index(["branch_id", "session_type", "status", "created_at"], "service_sessions_list_index");

        });

        Schema::create("service_session_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("service_session_id");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("assigned_user_id")->nullable();
            $table->string("name", 255);
            $table->string("item_type", 30);
            $table->decimal("quantity", 15, 3)->default(1);
            $table->decimal("unit_price", 15, 3)->default(0);
            $table->string("status", 20)->default("pending");
            $table->dateTime("started_at")->nullable();
            $table->dateTime("ended_at")->nullable();
            $table->unsignedInteger("duration_minutes")->default(0);
            $table->unsignedInteger("paused_minutes")->default(0);
            $table->string("preparation_status", 20)->default("pending");
            $table->dateTime("preparation_started_at")->nullable();
            $table->dateTime("ready_at")->nullable();
            $table->dateTime("delivered_at")->nullable();
            $table->string("observation", 500)->nullable();

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("service_session_id")->references("id")->on("service_sessions")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->restrictOnDelete();
            $table->foreign("assigned_user_id")->references("id")->on("users")->nullOnDelete();
            $table->index(["service_session_id", "status"], "service_session_items_status_index");

        });

        Schema::create("service_session_pauses", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("service_session_id");
            $table->unsignedBigInteger("service_session_item_id")->nullable();
            $table->unsignedBigInteger("paused_by");
            $table->unsignedBigInteger("resumed_by")->nullable();
            $table->dateTime("paused_at");
            $table->dateTime("resumed_at")->nullable();
            $table->unsignedInteger("duration_minutes")->default(0);
            $table->string("reason", 500)->nullable();
            $table->string("status", 20)->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->timestamp("updated_at")->nullable();

            $table->foreign("service_session_id")->references("id")->on("service_sessions")->onDelete("cascade");
            $table->foreign("service_session_item_id")->references("id")->on("service_session_items")->onDelete("cascade");
            $table->foreign("paused_by")->references("id")->on("users")->restrictOnDelete();
            $table->foreign("resumed_by")->references("id")->on("users")->nullOnDelete();

        });

        Schema::create("service_session_events", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("service_session_id");
            $table->unsignedBigInteger("service_session_item_id")->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->string("event_type", 40);
            $table->string("previous_status", 30)->nullable();
            $table->string("new_status", 30)->nullable();
            $table->string("note", 500)->nullable();
            $table->json("metadata")->nullable();
            $table->timestamp("occurred_at")->useCurrent();

            $table->foreign("service_session_id")->references("id")->on("service_sessions")->onDelete("cascade");
            $table->foreign("service_session_item_id")->references("id")->on("service_session_items")->onDelete("cascade");
            $table->foreign("user_id")->references("id")->on("users")->nullOnDelete();
            $table->index(["service_session_id", "occurred_at"], "service_session_events_timeline_index");

        });

    }

    public function down(): void {

        Schema::dropIfExists("service_session_events");
        Schema::dropIfExists("service_session_pauses");
        Schema::dropIfExists("service_session_items");
        Schema::dropIfExists("service_sessions");
        Schema::dropIfExists("service_stations");
        Schema::dropIfExists("service_floors");

    }
};
