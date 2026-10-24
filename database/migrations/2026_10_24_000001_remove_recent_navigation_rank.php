<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {

    public function up(): void {

        if(!Schema::hasColumn("user_navigation_metrics", "recent_rank")) {

            return;

        }

        Schema::table("user_navigation_metrics", function(Blueprint $table): void {

            $table->dropIndex("user_navigation_recent_index");
            $table->dropColumn("recent_rank");

        });

    }

    public function down(): void {

        if(!Schema::hasTable("user_navigation_metrics") || Schema::hasColumn("user_navigation_metrics", "recent_rank")) {

            return;

        }

        Schema::table("user_navigation_metrics", function(Blueprint $table): void {

            $table->unsignedTinyInteger("recent_rank")->nullable();
            $table->index(["user_id", "recent_rank"], "user_navigation_recent_index");

        });

    }

};
