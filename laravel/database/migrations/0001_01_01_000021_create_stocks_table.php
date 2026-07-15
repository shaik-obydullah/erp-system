<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('fk_product_id')->unsigned()->nullable();
            $table->foreign('fk_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->bigInteger('fk_inventory_id')->unsigned()->nullable();
            $table->foreign('fk_inventory_id')->references('id')->on('inventory')->onDelete('cascade');
            $table->bigInteger('fk_warehouses_id')->unsigned()->nullable();
            $table->string('batch', 100)->nullable();
            $table->string('lot', 100)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('buy_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'archive'])->default('active');
            $table->index('fk_product_id');
            $table->index('fk_inventory_id');
            $this->addAuditColumns($table);
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
        Schema::dropIfExists('stocks');
    }
}
