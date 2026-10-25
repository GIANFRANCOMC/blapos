<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {

    protected $connection = "landlord";

    public function up(): void {

        Schema::connection($this->connection)->create("platform_settings", function(Blueprint $table): void {

            $table->id();
            $table->string("key", 100)->unique();
            $table->string("value", 255);
            $table->string("description", 255)->nullable();
            $table->timestamps();

        });

    }

    public function down(): void {

        Schema::connection($this->connection)->dropIfExists("platform_settings");

    }

};
