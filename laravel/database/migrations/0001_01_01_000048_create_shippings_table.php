<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Migrations\AuditableMigration;

class CreateShippingsTable extends AuditableMigration
{
    public function up()
    {
        Schema::create('shippings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_sale_id');
            $table->string('full_name')->nullable();;
            $table->string('phone')->nullable();;
            $table->string('address')->nullable();;
            $table->string('city')->nullable();;
            $table->string('state')->nullable();;
            $table->string('zip')->nullable();;
            $table->string('country')->nullable();;

            $table->foreign('fk_sale_id')
                  ->references('id')
                  ->on('sales')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shippings');
    }
}