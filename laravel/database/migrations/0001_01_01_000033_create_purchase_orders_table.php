<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Migrations\AuditableMigration;

class CreatePurchaseOrdersTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_need_id');
            $table->unsignedBigInteger('fk_supplier_id'); 
            $table->string('order_number', 50)->unique();
            $table->decimal('total_amount', 10, 2);
            $table->text('remarks')->nullable();
            $table->decimal('due_amount', 10, 2);
            $this->addAuditColumns($table);
            $table->softDeletes();
            $table->foreign('fk_need_id')->references('id')->on('needs')->onDelete('cascade');
            $table->foreign('fk_supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['fk_need_id']);
            $table->dropForeign(['fk_supplier_id']);
        });

        Schema::dropIfExists('purchase_orders');
    }
}