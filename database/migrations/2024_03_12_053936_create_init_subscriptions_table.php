<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {

        Schema::create("subscriptions", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("sale_header_id")->nullable();
            $table->unsignedBigInteger("sale_body_id")->nullable();
            $table->unsignedBigInteger("renewed_from_id")->nullable();
            $table->unsignedBigInteger("customer_id");
            $table->enum("duration_type", ["hour", "day", "today", "month", "year"])->nullable();
            $table->integer("duration_value")->nullable();
            $table->dateTime("start_date");
            $table->dateTime("end_date");
            $table->boolean("set_end_of_day")->default(false);
            $table->boolean("force")->default(false);
            $table->integer("attendance_limit_per_day")->default(1);
            $table->text("observation")->nullable();
            $table->text("motive")->nullable();
            $table->enum("type", ["sale", "manual"])->default("sale");
            $table->enum("status", ["active", "canceled", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->foreign("sale_header_id")->references("id")->on("sales_header")->onDelete("cascade");
            $table->foreign("sale_body_id")->references("id")->on("sales_body")->onDelete("cascade");
            $table->foreign("renewed_from_id")->references("id")->on("subscriptions")->nullOnDelete();
            $table->foreign("customer_id")->references("id")->on("customers")->onDelete("cascade");

        });
        Schema::create("attendances", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("customer_id");
            $table->unsignedBigInteger("biometric_device_id")->nullable();
            $table->string("source_reference", 100)->nullable();
            $table->dateTime("start_date")->nullable();
            $table->dateTime("end_date")->nullable();
            $table->text("observation")->nullable();
            $table->text("motive")->nullable();
            $table->enum("type", ["manual_form", "qr_camera", "qr_scanner", "qr_public", "biometric"])->default("manual_form");
            $table->enum("status", ["active", "canceled", "inactive", "finalized", "absent"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->foreign("customer_id")->references("id")->on("customers")->onDelete("cascade");

        });
        Schema::create("attendance_corrections", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("attendance_id");
            $table->unsignedBigInteger("requested_by")->nullable();
            $table->dateTime("previous_start_date")->nullable();
            $table->dateTime("previous_end_date")->nullable();
            $table->dateTime("requested_start_date")->nullable();
            $table->dateTime("requested_end_date")->nullable();
            $table->string("reason", 500);
            $table->enum("status", ["pending", "approved", "rejected"])->default("pending");
            $table->unsignedBigInteger("reviewed_by")->nullable();
            $table->string("review_note", 500)->nullable();
            $table->timestamp("reviewed_at")->nullable();
            $table->timestamps();

            $table->foreign("attendance_id")->references("id")->on("attendances")->onDelete("cascade");
            $table->foreign("requested_by")->references("id")->on("users")->nullOnDelete();
            $table->foreign("reviewed_by")->references("id")->on("users")->nullOnDelete();

        });
        Schema::create("subscription_emails", function(Blueprint $table) {

            $table->id();
            $table->string("to", 255);
            $table->string("subject", 255);
            $table->text("body")->nullable();
            $table->text("extras_json")->nullable();
            $table->string("type", 255)->nullable();
            $table->string("model_id", 255)->nullable();
            $table->string("model_type", 255)->nullable();
            $table->enum("status", ["pending", "sent", "failed"])->default("pending");
            $table->unsignedSmallInteger("attempts")->default(0);
            $table->unsignedSmallInteger("max_attempts")->default(3);
            $table->timestamp("next_attempt_at")->nullable();
            $table->timestamp("sent_at")->nullable();
            $table->timestamp("failed_at")->nullable();
            $table->string("last_error", 500)->nullable();

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();


        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

        Schema::dropIfExists("subscription_emails");
        Schema::dropIfExists("attendance_corrections");
        Schema::dropIfExists("attendances");
        Schema::dropIfExists("subscriptions");

    }
};
