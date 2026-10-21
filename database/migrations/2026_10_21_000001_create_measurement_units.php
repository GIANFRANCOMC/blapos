<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {

        Schema::create("measurement_units", function(Blueprint $table): void {

            $table->id();
            $table->string("code", 50)->unique();
            $table->string("singular_name", 100);
            $table->string("plural_name", 100);
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamps();

        });

        Schema::table("items", function(Blueprint $table): void {

            $table->foreignId("measurement_unit_id")
                ->nullable()
                ->after("currency_id")
                ->constrained("measurement_units")
                ->restrictOnDelete();

        });

    }

    public function down(): void {

        Schema::table("items", function(Blueprint $table): void {

            $table->dropConstrainedForeignId("measurement_unit_id");

        });

        Schema::dropIfExists("measurement_units");

    }

};
