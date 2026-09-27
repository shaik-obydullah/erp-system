<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingZonesTable extends AuditableMigration
{
    public function up()
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('countries')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $this->addAuditColumns($table);
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_zones');
    }
}