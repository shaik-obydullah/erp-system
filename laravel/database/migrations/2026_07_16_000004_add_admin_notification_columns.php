<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('fk_admin_id')->nullable()->after('id');
            $table->enum('type', ['info', 'success', 'warning', 'error'])->default('info')->after('notification');
            $table->string('module', 50)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['fk_admin_id', 'type', 'module']);
        });
    }
};
