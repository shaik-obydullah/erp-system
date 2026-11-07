<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->string('vat_code', 20)->nullable()->after('status');
            $table->decimal('vat_rate', 5, 2)->nullable()->after('vat_code');
        });

        // Carry each product's legacy VAT treatment onto its existing stock
        // lines so current inventory keeps the rate it was sold at.
        DB::table('stocks')->select('id', 'fk_product_id')->orderBy('id')->chunkById(200, function ($stocks) {
            foreach ($stocks as $stock) {
                $product = DB::table('products')
                    ->where('id', $stock->fk_product_id)
                    ->first(['vat_code', 'vat_rate']);

                if (! $product) {
                    continue;
                }

                DB::table('stocks')->where('id', $stock->id)->update([
                    'vat_code' => $product->vat_code,
                    'vat_rate' => $product->vat_rate,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['vat_code', 'vat_rate']);
        });
    }
};
