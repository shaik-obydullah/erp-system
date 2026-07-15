<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentsTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_po_id');
            $table->unsignedBigInteger('fk_warehouse_id');
            $table->string('tracking_number')->unique();
            $table->date('received_date')->nullable();
            $table->enum('status', ['pending', 'shipped', 'in_transit', 'delivered', 'canceled']);
            $table->text('remark')->nullable();
            $this->addAuditColumns($table);
            $table->softDeletes();
            $table->foreign('fk_po_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('fk_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipments');
    }
}
