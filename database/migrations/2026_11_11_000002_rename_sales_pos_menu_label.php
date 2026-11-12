<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Support\Facades\{DB};

return new class extends Migration {

    public function up(): void {

        $this->rename("Venta POS", "POS venta");

    }

    public function down(): void {

        $this->rename("POS venta", "Venta POS");

    }

    private function rename(string $previous, string $label): void {

        DB::table("sub_sections")
            ->where("dom_route", "sales.pos")
            ->where("dom_label", $previous)
            ->update(["dom_label" => $label, "updated_at" => now()]);

    }

};
