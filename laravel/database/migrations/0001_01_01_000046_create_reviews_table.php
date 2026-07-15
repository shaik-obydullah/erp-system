<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_product_id');
            $table->unsignedBigInteger('fk_user_id');
            $table->tinyInteger('rating')->unsigned()->comment('Rating out of 5');
            $table->text('review')->nullable();
            $table->enum('status', ['published', 'unpublished']);
            $table->foreign('fk_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('fk_user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('reviews');
    }
}
