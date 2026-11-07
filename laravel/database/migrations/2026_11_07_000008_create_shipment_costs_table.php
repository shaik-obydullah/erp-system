<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentCostsTable extends AuditableMigration
{
    public function up(): void
    {
        Schema::create('shipment_costs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('fk_shipment_id');
            $table->unsignedBigInteger('fk_supplier_id')->nullable();
            $table->enum('cost_type', ['freight', 'duty', 'handling', 'insurance', 'other'])->default('freight');
            $table->decimal('amount', 15, 2);
            $table->date('cost_date');
            $table->enum('payment_status', ['paid', 'unpaid'])->default('paid');
            $table->date('paid_date')->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->text('description')->nullable();

            // Links back to the simple finance layer and the double-entry ledger.
            $table->unsignedBigInteger('fk_transaction_id')->nullable();
            $table->unsignedBigInteger('fk_cashbook_id')->nullable();
            $table->unsignedBigInteger('fk_payment_transaction_id')->nullable();
            $table->unsignedBigInteger('fk_payment_cashbook_id')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();

            $this->addAuditColumns($table);
            $table->softDeletes();

            $table->foreign('fk_shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->foreign('fk_supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('fk_transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('fk_cashbook_id')->references('id')->on('cashbook')->nullOnDelete();
            $table->foreign('fk_payment_transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->foreign('fk_payment_cashbook_id')->references('id')->on('cashbook')->nullOnDelete();

            $table->index(['fk_shipment_id', 'cost_date']);
            $table->index('journal_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_costs');
    }
}
