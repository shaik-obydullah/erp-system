<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJournalEntryIdToFinancialTables extends Migration
{
    public function up(): void
    {
        foreach (['cashbook', 'transactions', 'incomes', 'expenses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->index('journal_entry_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['cashbook', 'transactions', 'incomes', 'expenses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['journal_entry_id']);
                $table->dropColumn('journal_entry_id');
            });
        }
    }
}
