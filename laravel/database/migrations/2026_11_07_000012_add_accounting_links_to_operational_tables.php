<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link the remaining financial screens to their journal entries, and add
     * an actual-spend column so campaign budgets stay a memo while real spend
     * is posted to the ledger.
     */
    public function up(): void
    {
        foreach (['fixed_assets', 'payrolls', 'campaigns', 'productions', 'stocks', 'suppliers', 'shipment_returns'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->unsignedInteger('accounting_version')->default(0);
                $table->index('journal_entry_id');
            });
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->decimal('spent_amount', 15, 2)->default(0.00)->after('budget');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('spent_amount');
        });

        foreach (['fixed_assets', 'payrolls', 'campaigns', 'productions', 'stocks', 'suppliers', 'shipment_returns'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['journal_entry_id']);
                $table->dropColumn(['journal_entry_id', 'accounting_version']);
            });
        }
    }
};
