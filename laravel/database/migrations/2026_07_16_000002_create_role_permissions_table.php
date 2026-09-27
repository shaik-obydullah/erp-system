<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_role_id');
            $table->unsignedBigInteger('fk_permission_id');
            $table->foreign('fk_role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('fk_permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->unique(['fk_role_id', 'fk_permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
