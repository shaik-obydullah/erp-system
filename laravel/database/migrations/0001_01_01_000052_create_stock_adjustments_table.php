<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends AuditableMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(column: 'fk_stock_id');
            $table->foreign('fk_stock_id')->references('id')->on('stocks')->onDelete('cascade');
            $table->unsignedBigInteger('fk_warehouse_id')->nullable();
            $table->foreign('fk_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->string('batch', 100)->nullable();
            $table->string('lot', 100)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->enum('reason', ['correction', 'damage', 'return'])->default('correction');
            $table->index('fk_stock_id');
            $this->addAuditColumns($table);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
