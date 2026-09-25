<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {

        if(Schema::hasTable("attendances")) {

            DB::statement("ALTER TABLE attendances MODIFY status ENUM('active', 'canceled', 'inactive', 'finalized', 'absent') NOT NULL DEFAULT 'active'");

        }

        Schema::create("loyalty_point_rules", function(Blueprint $table) {

            $table->id();
            $table->string("name", 255);
            $table->text("description")->nullable();
            $table->enum("trigger_type", ["sale_total", "item_quantity", "subscription_sale"])->default("sale_total");
            $table->enum("apply_scope", ["all", "product", "service", "subscription", "selected_items"])->default("all");
            $table->decimal("amount_step", 15, 3)->default(1);
            $table->decimal("points_per_amount", 15, 3)->default(0);
            $table->decimal("points_per_unit", 15, 3)->default(0);
            $table->decimal("minimum_sale_total", 15, 3)->default(0);
            $table->timestamp("starts_at")->nullable();
            $table->timestamp("ends_at")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

        });

        Schema::create("loyalty_point_rule_items", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("loyalty_point_rule_id");
            $table->unsignedBigInteger("item_id");
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();

            $table->foreign("loyalty_point_rule_id")->references("id")->on("loyalty_point_rules")->onDelete("cascade");
            $table->foreign("item_id")->references("id")->on("items")->onDelete("cascade");

        });

        Schema::create("customer_point_balances", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("customer_id");
            $table->decimal("points_balance", 15, 3)->default(0);
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("customer_id")->references("id")->on("customers")->onDelete("cascade");
            $table->unique(["customer_id"]);

        });

        Schema::create("customer_point_movements", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("customer_id");
            $table->unsignedBigInteger("loyalty_point_rule_id")->nullable();
            $table->unsignedBigInteger("sale_header_id")->nullable();
            $table->unsignedBigInteger("sale_body_id")->nullable();
            $table->enum("movement_type", ["earned", "redeemed", "adjustment", "reversal"])->default("earned");
            $table->enum("basis_type", ["sale_total", "item_quantity", "manual"])->default("sale_total");
            $table->decimal("basis_amount", 15, 3)->default(0);
            $table->decimal("points", 15, 3);
            $table->string("description", 500)->nullable();
            $table->timestamp("occurred_at")->useCurrent();
            $table->enum("status", ["active", "canceled"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("customer_id")->references("id")->on("customers")->onDelete("cascade");
            $table->foreign("loyalty_point_rule_id")->references("id")->on("loyalty_point_rules")->nullOnDelete();
            $table->foreign("sale_header_id")->references("id")->on("sales_header")->nullOnDelete();
            $table->foreign("sale_body_id")->references("id")->on("sales_body")->nullOnDelete();

        });

        $this->syncSettings();

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

        Schema::dropIfExists("customer_point_movements");
        Schema::dropIfExists("customer_point_balances");
        Schema::dropIfExists("loyalty_point_rule_items");
        Schema::dropIfExists("loyalty_point_rules");

        if(Schema::hasTable("attendances")) {

            DB::statement("ALTER TABLE attendances MODIFY status ENUM('active', 'canceled', 'inactive', 'finalized') NOT NULL DEFAULT 'active'");

        }

        if(Schema::hasTable("company_settings")) {

            DB::table("company_settings")
                ->where(function($query) {

                    $query->where(function($query) {

                        $query->where("group", "customer_attendance")
                            ->whereIn("key", [
                                "auto_close_stale_enabled",
                                "auto_close_after_time",
                                "auto_close_end_time",
                                "retention_months",
                            ]);

                    })->orWhere(function($query) {

                        $query->where("group", "loyalty")
                            ->whereIn("key", [
                                "enabled",
                                "reverse_points_on_sale_cancellation",
                            ]);

                    })->orWhere(function($query) {

                        $query->where("group", "subscriptions")
                            ->where("key", "send_welcome_email_on_sale");

                    });

                })
                ->delete();

        }

    }

    private function syncSettings(): void {

        if(!Schema::hasTable("companies") || !Schema::hasTable("company_settings")) {

            return;

        }

        foreach(DB::table("companies")->pluck("id") as $companyId) {

            $settings = [
                ["customer_attendance", "auto_close_stale_enabled", "true", "Activa el cierre técnico de asistencias de clientes que quedaron abiertas sin salida.", "boolean"],
                ["customer_attendance", "auto_close_after_time", "01:00", "Hora local desde la cual el scheduler puede cerrar asistencias del día anterior que quedaron abiertas.", "string"],
                ["customer_attendance", "auto_close_end_time", "23:50", "Hora local usada como salida técnica cuando una asistencia quedó abierta sin checkout.", "string"],
                ["customer_attendance", "retention_months", "5", "Cantidad de meses que se conservan asistencias de clientes finalizadas, anuladas, inactivas o ausentes antes de permitir su depuración.", "integer"],
                ["subscriptions", "send_welcome_email_on_sale", "true", "Encola un correo de agradecimiento cuando una venta genera una membresía para un cliente.", "boolean"],
                ["loyalty", "enabled", "false", "Activa el cálculo de puntos para clientes en ventas confirmadas. Requiere reglas activas en loyalty_point_rules.", "boolean"],
                ["loyalty", "reverse_points_on_sale_cancellation", "true", "Revierte puntos ganados cuando se anula la venta que los originó.", "boolean"],
            ];

            foreach($settings as [$group, $key, $value, $description, $valueType]) {

                DB::table("company_settings")->updateOrInsert(
                    [
                        "company_id" => (int) $companyId,
                        "group" => $group,
                        "key" => $key,
                    ],
                    [
                        "company_id" => (int) $companyId,
                        "group" => $group,
                        "key" => $key,
                        "value" => $value,
                        "description" => $description,
                        "value_type" => $valueType,
                        "status" => "active",
                    ]
                );

            }

        }

    }
};
