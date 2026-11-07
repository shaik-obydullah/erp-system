<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            // Snapshot of the stock cost at the time of sale. Used for accurate
            // COGS even when the stock's buy_price later changes.
            $table->decimal('cost_price', 15, 2)->nullable()->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
