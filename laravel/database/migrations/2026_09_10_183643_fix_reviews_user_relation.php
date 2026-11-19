<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("reviews", function (Blueprint $table) {
            $table->dropForeign(["fk_user_id"]);
        });

        Schema::table("reviews", function (Blueprint $table) {
            $table->foreign("fk_user_id")->references("id")->on("customers")->onDelete("cascade");
            $table->timestamp("created_at")->nullable();
            $table->timestamp("updated_at")->nullable();
        });
    }

    public function down(): void
    {
        Schema::table("reviews", function (Blueprint $table) {
            $table->dropForeign(["fk_user_id"]);
            $table->dropColumn(["created_at", "updated_at"]);
        });

        Schema::table("reviews", function (Blueprint $table) {
            $table->foreign("fk_user_id")->references("id")->on("users")->onDelete("cascade");
        });
    }
};
