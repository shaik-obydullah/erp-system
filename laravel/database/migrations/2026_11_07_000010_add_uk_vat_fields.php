<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('vat_code', 20)->nullable()->after('status');
            $table->decimal('vat_rate', 5, 2)->nullable()->after('vat_code');
        });

        foreach (['customers', 'suppliers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('vat_number', 30)->nullable();
                $table->string('country', 2)->nullable();
                $table->string('vat_treatment', 20)->nullable();
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->json('vat_breakdown')->nullable();
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->string('vat_code', 20)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('gross_amount', 10, 2)->default(0);
        });

        Schema::table('cart_details', function (Blueprint $table) {
            $table->string('vat_code', 20)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('vat_code', 20)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->index('journal_entry_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('vat_code', 20)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->string('vat_code', 20)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->string('vat_code', 20)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->index('journal_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropIndex(['journal_entry_id']);
            $table->dropColumn([
                'vat_code', 'vat_rate', 'net_amount', 'vat_amount', 'journal_entry_id',
            ]);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn(['vat_code', 'vat_rate', 'net_amount', 'vat_amount']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['vat_code', 'vat_rate', 'net_amount', 'vat_amount']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['journal_entry_id']);
            $table->dropColumn([
                'vat_code', 'vat_rate', 'net_amount', 'vat_amount', 'journal_entry_id',
            ]);
        });

        Schema::table('cart_details', function (Blueprint $table) {
            $table->dropColumn(['vat_code', 'vat_rate']);
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['vat_code', 'vat_rate', 'net_amount', 'vat_amount', 'gross_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('vat_breakdown');
        });

        foreach (['customers', 'suppliers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['vat_number', 'country', 'vat_treatment']);
            });
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['vat_code', 'vat_rate']);
        });
    }
};
