<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {

        Schema::create("external_api_request_logs", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->string("service", 100);
            $table->string("action", 100);
            $table->string("document_type", 30)->nullable();
            $table->string("document_number", 30)->nullable();
            $table->enum("result", ["success", "failed", "blocked"])->default("failed");
            $table->string("ip_address", 45)->nullable();
            $table->timestamp("requested_at")->useCurrent();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("set null");

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

        Schema::dropIfExists("external_api_request_logs");

    }
};
