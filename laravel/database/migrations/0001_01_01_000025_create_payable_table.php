<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayableTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payable', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('table_name', 100)->nullable();
            $table->unsignedBigInteger('fk_reference_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->default(0.00);
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
        Schema::dropIfExists('payable');
    }
}
