<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // VAT is a stock-line concern now. Products must not carry any tax or
        // finance attributes, so the legacy product VAT columns are removed.
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['vat_code', 'vat_rate']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('vat_code', 20)->nullable()->after('status');
            $table->decimal('vat_rate', 5, 2)->nullable()->after('vat_code');
        });
    }
};
