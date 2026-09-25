<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    public function up(): void {

        Schema::create("user_attendances", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("user_id");
            $table->date("work_date");
            $table->dateTime("checked_in_at");
            $table->dateTime("checked_out_at")->nullable();
            $table->unsignedInteger("worked_minutes")->default(0);
            $table->unsignedInteger("ordinary_minutes")->default(0);
            $table->unsignedInteger("late_minutes")->default(0);
            $table->unsignedInteger("overtime_minutes")->default(0);
            $table->unsignedInteger("break_minutes")->default(0);
            $table->string("source_type", 30)->default("manual_form");
            $table->string("source_reference", 100)->nullable();
            $table->string("observation", 500)->nullable();
            $table->string("motive", 500)->nullable();
            $table->string("status", 20)->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("canceled_at")->nullable();
            $table->integer("canceled_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->restrictOnDelete();
            $table->foreign("user_id")->references("id")->on("users")->restrictOnDelete();

        });

        Schema::create("user_biometric_fingerprints", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("biometric_device_id");
            $table->integer("device_user_id");
            $table->unsignedTinyInteger("finger_index")->default(0);
            $table->longText("fingerprint_template")->nullable();
            $table->string("description", 500)->nullable();
            $table->string("status", 20)->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("user_id")->references("id")->on("users")->restrictOnDelete();
            $table->foreign("biometric_device_id")->references("id")->on("biometric_devices")->restrictOnDelete();
            $table->unique(
                ["biometric_device_id", "device_user_id", "finger_index"],
                "ubf_company_device_user_finger_uq"
            );

        });

        Schema::create("user_work_schedules", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id")->nullable();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->string("name", 150);
            $table->unsignedTinyInteger("weekday");
            $table->time("starts_at");
            $table->time("ends_at");
            $table->unsignedSmallInteger("tolerance_minutes")->default(0);
            $table->unsignedSmallInteger("rounding_minutes")->default(0);
            $table->boolean("crosses_midnight")->default(false);
            $table->boolean("is_working_day")->default(true);
            $table->string("status", 20)->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->nullOnDelete();
            $table->foreign("user_id")->references("id")->on("users")->nullOnDelete();

        });

        Schema::create("user_attendance_breaks", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_attendance_id");
            $table->dateTime("started_at");
            $table->dateTime("ended_at")->nullable();
            $table->unsignedInteger("duration_minutes")->default(0);
            $table->string("reason", 500)->nullable();
            $table->string("status", 20)->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("user_attendance_id")->references("id")->on("user_attendances")->onDelete("cascade");

        });

        Schema::create("user_attendance_corrections", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_attendance_id");
            $table->unsignedBigInteger("requested_by");
            $table->unsignedBigInteger("reviewed_by")->nullable();
            $table->dateTime("requested_check_in_at")->nullable();
            $table->dateTime("requested_check_out_at")->nullable();
            $table->string("reason", 500);
            $table->string("review_note", 500)->nullable();
            $table->string("status", 20)->default("pending");
            $table->timestamp("reviewed_at")->nullable();
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->timestamp("updated_at")->nullable();

            $table->foreign("user_attendance_id")->references("id")->on("user_attendances")->onDelete("cascade");
            $table->foreign("requested_by")->references("id")->on("users")->restrictOnDelete();
            $table->foreign("reviewed_by")->references("id")->on("users")->nullOnDelete();

        });

    }

    public function down(): void {

        Schema::dropIfExists("user_attendance_corrections");
        Schema::dropIfExists("user_attendance_breaks");
        Schema::dropIfExists("user_work_schedules");
        Schema::dropIfExists("user_biometric_fingerprints");
        Schema::dropIfExists("user_attendances");

    }
};
