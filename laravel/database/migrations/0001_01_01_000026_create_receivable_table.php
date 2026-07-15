<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceivableTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receivable', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('table_name', 100)->nullable();
            $table->unsignedBigInteger('fk_reference_id');
            $table->text('description');
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
        Schema::dropIfExists('receivable');
    }
}
