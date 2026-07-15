<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionPlanningsTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('production_plannings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_bill_of_material_id');
            $table->decimal('production_cost', 10, 2);
            $table->decimal('other_cost', 10, 2);
            $table->decimal('expected_profit', 10, 2);
            $table->integer('quantity');
            $table->enum('finalized', ['yes', 'no'])->default('no');
            $this->addAuditColumns($table);
            $table->softDeletes();
            $table->foreign('fk_bill_of_material_id')->references('id')->on('bill_of_materials')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_plannings');
    }
}
