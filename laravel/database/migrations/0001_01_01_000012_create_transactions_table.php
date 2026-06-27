<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Migrations\AuditableMigration;

class CreateTransactionsTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->enum('type', ['userFund', 'supplierDeposit', 'supplierPayment', 'bomExpense', 'salaryPayment', 'campaignExpense', 'stockIn', 'stockOut', 'saleIncome', 'saleDue', 'fixedAsset', 'income', 'expense', 'miscIncome', 'miscExpense']);
            $table->unsignedBigInteger('fk_reference_id');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('due_amount', 10, 2)->default(0.00);
            $table->index('fk_reference_id');
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
        Schema::dropIfExists('transactions');
    }
}