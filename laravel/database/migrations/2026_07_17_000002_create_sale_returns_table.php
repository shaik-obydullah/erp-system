<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fk_sale_id');
            $table->unsignedBigInteger('fk_stock_id');
            $table->integer('quantity')->default(1);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->enum('reason', ['damaged', 'wrong_item', 'customer_request', 'defective', 'other'])->default('customer_request');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
