<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleDetailsTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('fk_sale_id');
            $table->unsignedBigInteger('fk_stock_id');
            $table->string('stock_name', 200);
            $table->string('size', 20)->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('total_stock')->default(0);
            $table->unsignedInteger('sale_stock')->default(0);
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->string('return_reason', 200)->nullable();
            $table->foreign('fk_sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_details');
    }
}
