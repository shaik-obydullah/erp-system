<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Migrations\AuditableMigration;

class CreateProductsTable extends AuditableMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('fk_brand_id')->unsigned()->nullable();
            $table->foreign('fk_brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->bigInteger('fk_model_id')->unsigned()->nullable();
            $table->foreign('fk_model_id')->references('id')->on('brands')->onDelete('cascade');
            $table->unsignedBigInteger('fk_category_id')->nullable();
            $table->foreign('fk_category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->unsignedBigInteger('fk_subcategory_id')->nullable();
            $table->foreign('fk_subcategory_id')->references('id')->on('categories')->onDelete('cascade');
            $table->unsignedBigInteger('fk_item_id')->nullable();
            $table->foreign('fk_item_id')->references('id')->on('categories')->onDelete('cascade');
            $table->unsignedBigInteger('fk_supplier_id')->nullable();
            $table->foreign('fk_supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->unsignedInteger('fk_unit_id')->nullable();
            $table->foreign('fk_unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->string('name', 200);
            $table->string('url_slug', 250)->unique();
            $table->string('image', 100)->nullable();
            $table->string('sku', 50)->nullable();
            $table->string('barcode', 50)->nullable();
            $table->text('size')->nullable();
            $table->text('color')->nullable();
            $table->text('specification')->nullable();
            $table->text('attribute')->nullable();
            $table->smallInteger('review_number')->nullable();
            $table->smallInteger('review_avg')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'archive'])->default('active');
            $table->index('fk_brand_id');
            $table->index('fk_model_id');
            $table->index('fk_category_id');
            $table->index('fk_subcategory_id');
            $table->index('fk_supplier_id');
            $table->index('fk_unit_id');
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
        Schema::dropIfExists('products');
    }
}