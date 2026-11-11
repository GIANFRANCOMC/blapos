<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {

    public function up(): void {

        if(!Schema::hasTable("sub_sections") || !Schema::hasTable("companies_sub_sections")) {
            return;
        }

        $salesModules = DB::table("sub_sections")
            ->join("sections", "sections.id", "=", "sub_sections.section_id")
            ->where("sections.slug", "sc_sales")
            ->where("sub_sections.status", "active")
            ->pluck("sub_sections.id");

        if($salesModules->isEmpty()) {
            return;
        }

        DB::transaction(function() use ($salesModules): void {
            DB::table("sub_sections")->whereIn("id", $salesModules)
                ->where("is_enabled_by_default", false)
                ->update(["is_enabled_by_default" => true, "updated_at" => now()]);

            DB::table("companies_sub_sections")->whereIn("sub_section_id", $salesModules)
                ->where("status", "inactive")
                ->update(["status" => "active", "updated_at" => now()]);

            if(Schema::hasTable("business_industry_module_sets")) {
                DB::table("business_industry_module_sets")->whereIn("sub_section_id", $salesModules)
                    ->where("is_enabled_by_default", false)
                    ->update(["is_enabled_by_default" => true, "updated_at" => now()]);
            }
        });

    }

    public function down(): void {

        // Los módulos pueden haberse configurado por empresa después de la activación inicial.

    }

};
