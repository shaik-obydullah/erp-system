<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentReturnsTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipment_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_po_id');
            $table->decimal('invoice_amount', 10, 2);
            $table->enum('return_reason', ['damaged', 'incorrect_item', 'excess_quantity', 'other']);
            $table->enum('status', ['pending', 'processed', 'completed', 'rejected']);
            $table->text('remark')->nullable();
            $this->addAuditColumns($table);
            $table->softDeletes();
            $table->foreign('fk_po_id')->references('id')->on('purchase_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipment_returns');
    }
}
