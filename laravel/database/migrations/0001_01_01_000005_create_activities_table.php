<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('fk_admin_id')->nullable();
            $table->enum('type', ['success', 'warning', 'error']);
            $table->string('name', 150);
            $table->string('ip_address', 45);
            $table->string('visitor_country', 50)->nullable();
            $table->string('visitor_state', 100)->nullable();
            $table->string('visitor_city', 100)->nullable();
            $table->string('visitor_address', 150)->nullable();
            $table->dateTime('created_at')->nullable(); 
            $table->dateTime('updated_at')->nullable(); 
            $table->unsignedBigInteger('updated_by')->nullable();
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
        Schema::dropIfExists('activities');
    }
}