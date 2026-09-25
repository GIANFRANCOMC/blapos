<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    protected $connection = "landlord";

    public function up(): void {

        Schema::connection($this->connection)->create("platform_users", function(Blueprint $table): void {

            $table->id();
            $table->string("name", 150);
            $table->string("email", 190)->unique();
            $table->string("password");
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->unsignedInteger("session_version")->default(1);
            $table->timestamp("last_login_at")->nullable();
            $table->string("last_login_ip", 45)->nullable();
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->timestamp("updated_at")->nullable();

        });

        Schema::connection($this->connection)->create("tenant_databases", function(Blueprint $table): void {

            $table->id();
            $table->uuid("public_id")->unique();
            $table->string("slug", 120)->unique();
            $table->string("database_name", 180)->unique();
            $table->enum("status", [
                "provisioning",
                "active",
                "inactive",
                "suspended",
                "provisioning_failed",
                "maintenance",
            ])->default("provisioning");
            $table->text("status_reason")->nullable();
            $table->timestamp("status_changed_at")->nullable();
            $table->timestamp("last_resolved_at")->nullable();
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();
            $table->index(["status", "slug"], "tenant_databases_status_slug_index");
            $table->index(["status", "status_changed_at"], "tenant_databases_status_changed_index");

        });

        Schema::connection($this->connection)->create("tenant_domains", function(Blueprint $table): void {

            $table->id();
            $table->unsignedBigInteger("tenant_database_id");
            $table->string("domain", 255)->unique();
            $table->enum("type", ["subdomain"])->default("subdomain");
            $table->boolean("is_primary")->default(false);
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("tenant_database_id")->references("id")->on("tenant_databases")->onDelete("cascade");
            $table->index(["tenant_database_id", "status", "is_primary"], "tenant_domains_tenant_status_index");

        });

        Schema::connection($this->connection)->create("tenant_audit_logs", function(Blueprint $table): void {

            $table->id();
            $table->unsignedBigInteger("tenant_database_id")->nullable();
            $table->string("action", 80);
            $table->enum("result", ["success", "failure", "blocked"])->default("success");
            $table->string("host", 255)->nullable();
            $table->string("ip_address", 45)->nullable();
            $table->string("actor", 150)->nullable();
            $table->json("context")->nullable();
            $table->timestamp("occurred_at")->useCurrent();

            $table->foreign("tenant_database_id")->references("id")->on("tenant_databases")->nullOnDelete();
            $table->index(["tenant_database_id", "occurred_at"], "tenant_audit_logs_timeline_index");
            $table->index(["action", "result", "occurred_at"], "tenant_audit_logs_action_index");

        });

        Schema::connection($this->connection)->create("tenant_announcements", function(Blueprint $table): void {

            $table->id();
            $table->unsignedBigInteger("tenant_database_id")->nullable();
            $table->string("title", 180);
            $table->text("message");
            $table->enum("severity", ["info", "success", "warning", "danger"])->default("info");
            $table->timestamp("starts_at")->nullable();
            $table->timestamp("ends_at")->nullable();
            $table->boolean("dismissible")->default(true);
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->unsignedBigInteger("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->unsignedBigInteger("updated_by")->nullable();

            $table->foreign("tenant_database_id")->references("id")->on("tenant_databases")->cascadeOnDelete();
            $table->foreign("created_by")->references("id")->on("platform_users")->nullOnDelete();
            $table->foreign("updated_by")->references("id")->on("platform_users")->nullOnDelete();
            $table->index(["tenant_database_id", "status", "starts_at", "ends_at"], "tenant_announcements_visibility_index");

        });

    }

    public function down(): void {

        Schema::connection($this->connection)->dropIfExists("tenant_announcements");
        Schema::connection($this->connection)->dropIfExists("tenant_audit_logs");
        Schema::connection($this->connection)->dropIfExists("tenant_domains");
        Schema::connection($this->connection)->dropIfExists("tenant_databases");
        Schema::connection($this->connection)->dropIfExists("platform_users");

    }
};
