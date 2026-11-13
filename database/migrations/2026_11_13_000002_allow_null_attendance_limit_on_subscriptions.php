<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void {

        DB::statement("ALTER TABLE subscriptions MODIFY attendance_limit_per_day INT NULL DEFAULT NULL");

    }

    public function down(): void {

        if(DB::table("subscriptions")->whereNull("attendance_limit_per_day")->exists()) {

            throw new RuntimeException("Hay membresías sin límite diario. Revertir esta columna perdería esa configuración.");

        }

        DB::statement("ALTER TABLE subscriptions MODIFY attendance_limit_per_day INT NOT NULL DEFAULT 1");

    }

};
