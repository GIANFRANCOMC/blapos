<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\{Command};
use Illuminate\Support\Facades\{DB, Route, Schema};

final class DoctorSystemDatabase extends Command {
    protected $signature = "system:doctor {--company= : Valida una organización específica}";

    protected $description = "Diagnostica esquema, catálogo, rutas y referencias esenciales de organizaciones.";

    public function handle(): int {

        $errors = [];
        $requiredTables = [
            "companies", "menu_categories", "sections", "menu_groups", "sub_sections",
            "companies_sub_sections", "roles", "role_sub_sections", "users",
            "branches", "warehouses", "cash_registers", "company_settings",
        ];

        foreach($requiredTables as $table) {

            if(!Schema::hasTable($table)) {

                $errors[] = "Falta la tabla {$table}.";

            }

        }

        if($errors) {

            foreach($errors as $error) {

                $this->components->error($error);

            }

            return self::FAILURE;

        }

        $navigationItems = DB::table("sub_sections")->where("status", "active")->get(["dom_label", "dom_route"]);

        foreach($navigationItems as $item) {

            if(!Route::has($item->dom_route)) {

                $errors[] = "La opción {$item->dom_label} apunta a la ruta inexistente {$item->dom_route}.";

            }

        }

        $companies = DB::table("companies")
            ->when($this->option("company"), fn($query) => $query->where("id", (int) $this->option("company")))
            ->get();

        foreach($companies as $company) {

            foreach(["identity_document_type_id", "currency_id"] as $reference) {

                if(!$company->{

                    $reference

                }) {

                    $errors[] = "La organización {$company->id} no tiene {$reference}.";

                }

            }

            foreach(["roles", "branches", "warehouses", "cash_registers"] as $table) {

                if(!DB::table($table)->exists()) {

                    $errors[] = "La organización {$company->id} no tiene registros en {$table}.";

                }

            }

        }

        if($errors) {

            foreach($errors as $error) {

                $this->components->error($error);

            }

            return self::FAILURE;

        }

        $this->components->info(sprintf(
            "Base consistente: %d organizaciones y %d opciones de menú verificadas.",
            $companies->count(), $navigationItems->count()
        ));

        return self::SUCCESS;

    }
}
