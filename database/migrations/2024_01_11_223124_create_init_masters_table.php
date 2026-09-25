<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {

        Schema::create("identity_document_types", function(Blueprint $table) {

            $table->id();
            $table->string("code", 255);
            $table->string("name", 255);
            $table->boolean("is_searchable")->default(true);
            $table->integer("min_length")->default(1);
            $table->integer("max_length")->default(50);

            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->unique(["code"]);

        });
        Schema::create("document_types", function(Blueprint $table) {

            $table->id();
            $table->string("code", 255);
            $table->string("name", 255);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->unique(["code"]);

        });
        Schema::create("currencies", function(Blueprint $table) {

            $table->id();
            $table->string("code", 255);
            $table->string("sign", 255);
            $table->string("singular_name", 255);
            $table->string("plural_name", 255);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->unique(["code"]);

        });
        Schema::create("companies", function(Blueprint $table) {

            $table->id();
            $table->string("slug", 255)->unique();
            $table->string("internal_code", 255);
            $table->unsignedBigInteger("identity_document_type_id")->nullable();
            $table->string("document_number", 255);
            $table->string("legal_name", 255);
            $table->string("commercial_name", 255);
            $table->unsignedBigInteger("currency_id")->nullable();
            $table->string("tagline", 255)->nullable();
            $table->string("description", 500)->nullable();
            $table->string("address", 255)->nullable();
            $table->string("telephone", 255)->nullable();
            $table->string("email", 255)->nullable();
            $table->string("token_api_misc", 255)->nullable();
            $table->string("logotype", 255)->nullable();
            $table->string("combinationmark", 255)->nullable();
            $table->string("logomark", 255)->nullable();
            $table->string("login_image", 255)->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("identity_document_type_id")->references("id")->on("identity_document_types")->restrictOnDelete();
            $table->foreign("currency_id")->references("id")->on("currencies")->restrictOnDelete();

        });
        Schema::create("menu_categories", function(Blueprint $table) {

            $table->id();
            $table->string("slug", 100)->unique();
            $table->string("name", 100);
            $table->integer("order")->default(0);
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamps();

        });
        Schema::create("sections", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("menu_category_id")->nullable();
            $table->string("slug", 255);
            $table->string("name", 255);
            $table->integer("order")->nullable();
            $table->string("dom_id", 255)->default("");
            $table->string("dom_label", 255)->default("");
            $table->string("dom_icon", 255)->default("");
            $table->boolean("has_sub_menu")->default(false);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("menu_category_id")->references("id")->on("menu_categories")->nullOnDelete();

        });
        Schema::create("menu_groups", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("section_id");
            $table->string("slug", 100);
            $table->string("name", 100);
            $table->integer("order")->default(0);
            $table->enum("status", ["active", "inactive"])->default("active");
            $table->timestamps();

            $table->foreign("section_id")->references("id")->on("sections")->cascadeOnDelete();
            $table->unique(["section_id", "slug"]);

        });
        Schema::create("sub_sections", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("section_id");
            $table->unsignedBigInteger("menu_group_id")->nullable();
            $table->string("slug", 255);
            $table->string("name", 255);
            $table->string("description", 255)->nullable();
            $table->integer("order")->nullable();
            $table->string("dom_id", 255)->default("");
            $table->string("dom_label", 255)->default("");
            $table->string("dom_icon", 255)->default("");
            $table->string("dom_route", 255)->default("");
            $table->boolean("is_enabled_by_default")->default(true);
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("section_id")->references("id")->on("sections")->onDelete("cascade");
            $table->foreign("menu_group_id")->references("id")->on("menu_groups")->nullOnDelete();
            $table->unique("slug", "sub_sections_slug_unique");
            $table->unique("dom_route", "sub_sections_dom_route_unique");

        });
        Schema::create("companies_sub_sections", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("company_id");
            $table->unsignedBigInteger("sub_section_id");
            $table->integer("section_order")->nullable();
            $table->integer("sub_section_order")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("company_id")->references("id")->on("companies")->onDelete("cascade");
            $table->foreign("sub_section_id")->references("id")->on("sub_sections")->onDelete("cascade");
            $table->unique(["company_id", "sub_section_id"], "companies_sub_sections_company_module_unique");
            $table->index(["company_id", "status", "section_order", "sub_section_order"], "companies_sub_sections_navigation_index");

        });
        Schema::create("roles", function(Blueprint $table) {

            $table->id();
            $table->string("slug", 255);
            $table->string("name", 255);
            $table->boolean("is_full_access")->default(false);
            $table->enum("branch_scope_mode", ["all", "restricted"])->default("all");
            $table->enum("cash_register_scope_mode", ["all", "restricted"])->default("all");
            $table->enum("warehouse_scope_mode", ["all", "restricted"])->default("all");
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

        });
        Schema::create("role_sub_sections", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("role_id");
            $table->unsignedBigInteger("sub_section_id");
            $table->json("actions")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("role_id")->references("id")->on("roles")->onDelete("cascade");
            $table->foreign("sub_section_id")->references("id")->on("sub_sections")->onDelete("cascade");
            $table->unique(["role_id", "sub_section_id"]);

        });
        Schema::create("users", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("role_id")->nullable();
            $table->enum("branch_scope_mode", ["inherit", "restricted"])->default("inherit");
            $table->enum("cash_register_scope_mode", ["inherit", "restricted"])->default("inherit");
            $table->enum("warehouse_scope_mode", ["inherit", "restricted"])->default("inherit");
            $table->unsignedBigInteger("identity_document_type_id");
            $table->string("document_number", 255);
            $table->string("name", 255);
            $table->string("email", 255);
            $table->timestamp("email_verified_at")->nullable();
            $table->string("password", 255);
            $table->rememberToken();
            $table->unsignedInteger("session_version")->default(1);
            $table->string("phone_number", 255)->nullable();
            $table->enum("gender", ["male", "female", "other"])->nullable();
            $table->date("birthdate")->nullable();
            $table->enum("status", ["active", "inactive", "blocked"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("role_id")->references("id")->on("roles")->onDelete("cascade");
            $table->foreign("identity_document_type_id")->references("id")->on("identity_document_types")->restrictOnDelete();
            $table->unique(["email"]);

        });
        Schema::create("authentication_events", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->string("tenant_slug", 120)->nullable();
            $table->string("event_type", 40);
            $table->enum("result", ["success", "failure", "blocked"]);
            $table->string("email", 255)->nullable();
            $table->string("ip_address", 45)->nullable();
            $table->string("user_agent", 500)->nullable();
            $table->string("session_hash", 64)->nullable();
            $table->string("reason", 500)->nullable();
            $table->timestamp("occurred_at")->useCurrent();

            $table->foreign("user_id")->references("id")->on("users")->nullOnDelete();

        });
        Schema::create("user_preferences", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->string("slug", 255);
            $table->text("value")->nullable();
            $table->enum("status", ["active", "inactive"])->default("active");

            $table->timestamp("created_at")->useCurrent()->nullable();
            $table->integer("created_by")->nullable();
            $table->timestamp("updated_at")->nullable();
            $table->integer("updated_by")->nullable();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");

        });
        Schema::create("user_navigation_metrics", function(Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("sub_section_id");
            $table->unsignedBigInteger("visit_count")->default(0);
            $table->unsignedTinyInteger("recent_rank")->nullable();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->foreign("sub_section_id")->references("id")->on("sub_sections")->onDelete("cascade");
            $table->unique(["user_id", "sub_section_id"], "user_navigation_route_unique");
            $table->index(["user_id", "recent_rank"], "user_navigation_recent_index");
            $table->index(["user_id", "visit_count"], "user_navigation_visits_index");

        });
        // Los datos se aprovisionan después del esquema mediante system:install.

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists("authentication_events");
        Schema::dropIfExists("user_navigation_metrics");
        Schema::dropIfExists("user_preferences");
        Schema::dropIfExists("users");
        Schema::dropIfExists("role_sub_sections");
        Schema::dropIfExists("roles");
        Schema::dropIfExists("companies_sub_sections");
        Schema::dropIfExists("sub_sections");
        Schema::dropIfExists("menu_groups");
        Schema::dropIfExists("sections");
        Schema::dropIfExists("menu_categories");
        Schema::dropIfExists("companies");
        Schema::dropIfExists("currencies");
        Schema::dropIfExists("document_types");
        Schema::dropIfExists("identity_document_types");

        Schema::enableForeignKeyConstraints();

    }
};
