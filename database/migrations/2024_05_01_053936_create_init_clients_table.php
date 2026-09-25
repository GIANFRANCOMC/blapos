<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {

        Schema::create("book_complaints", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("branch_id");
            $table->unsignedBigInteger("identity_document_type_id");
            $table->string("document_number", 255);
            $table->string("name", 255);
            $table->string("email", 255)->nullable();
            $table->string("phone_number", 255)->nullable();
            $table->enum("type", ["complaint", "claim", "suggestion"]);
            $table->text("description");
            $table->text("request")->nullable();
            $table->string("evidence", 255)->nullable();
            $table->text("admin_response")->nullable();
            $table->text("public_response")->nullable();
            $table->string("tracking_code", 64);
            $table->timestamp("responded_at")->nullable();
            $table->unsignedBigInteger("responded_by")->nullable();
            $table->string("submitted_ip", 45)->nullable();
            $table->text("submitted_user_agent")->nullable();
            $table->string("submitted_platform", 100)->nullable();
            $table->string("submitted_browser", 100)->nullable();
            $table->enum("status", ["pending", "in_progress", "resolved"])->default("pending");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->timestamp("deleted_at")->nullable();
            $table->integer("deleted_by")->nullable();

            $table->foreign("branch_id")->references("id")->on("branches")->onDelete("cascade");
            $table->foreign("identity_document_type_id")->references("id")->on("identity_document_types")->onDelete("cascade");
            $table->foreign("responded_by")->references("id")->on("users")->nullOnDelete();
            $table->unique(["tracking_code"]);

        });

        Schema::create("book_complaint_attachments", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("book_complaint_id");
            $table->string("file_name", 255);
            $table->string("file_path", 500);
            $table->string("mime_type", 100);
            $table->unsignedBigInteger("file_size")->default(0);
            $table->timestamp("created_at")->useCurrent()->nullable();

            $table->foreign("book_complaint_id")->references("id")->on("book_complaints")->onDelete("cascade");

        });

        Schema::create("book_complaint_status_histories", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("book_complaint_id");
            $table->unsignedBigInteger("changed_by")->nullable();
            $table->string("previous_status", 30)->nullable();
            $table->string("new_status", 30);
            $table->string("note", 500)->nullable();
            $table->timestamp("changed_at")->useCurrent();

            $table->foreign("book_complaint_id")->references("id")->on("book_complaints")->onDelete("cascade");
            $table->foreign("changed_by")->references("id")->on("users")->nullOnDelete();

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

        Schema::dropIfExists("book_complaint_status_histories");
        Schema::dropIfExists("book_complaint_attachments");
        Schema::dropIfExists("book_complaints");

    }
};
