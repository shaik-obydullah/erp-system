<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingMethodsTable extends AuditableMigration
{
    public function up()
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_shipping_zone_id');
            $table->string('name', 100);
            $table->enum('type', ['flat_rate', 'free_shipping', 'local_pickup'])->default('flat_rate');
            $table->decimal('cost', 10, 2)->default(0);
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $this->addAuditColumns($table);
            $table->softDeletes();

            $table->foreign('fk_shipping_zone_id')
                ->references('id')
                ->on('shipping_zones')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_methods');
    }
}