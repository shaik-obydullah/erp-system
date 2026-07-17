<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifySuppliersForAuth extends Migration
{
    public function up()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('email', 100)->nullable(false)->unique()->change();
            $table->string('password')->nullable(false)->change();
            $table->timestamp('email_verified_at')->nullable()->after('password');
        });

        Schema::create('supplier_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('supplier_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('supplier_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down()
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->unique()->change();
            $table->string('password')->nullable()->change();
            $table->dropColumn('email_verified_at');
        });

        Schema::dropIfExists('supplier_sessions');
        Schema::dropIfExists('supplier_password_reset_tokens');
    }
}
