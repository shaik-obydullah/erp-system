<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the unused `inventory` table and its only link (stocks.fk_inventory_id).
     *
     * Inventory is modelled by `stocks` (batch/lot, quantity, prices, warehouse,
     * VAT and journal link). The `inventory` table was never populated or used.
     */
    public function up(): void
    {
        if (Schema::hasTable('stocks') && Schema::hasColumn('stocks', 'fk_inventory_id')) {
            $foreignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'stocks')
                ->where('COLUMN_NAME', 'fk_inventory_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->value('CONSTRAINT_NAME');

            if ($foreignKey) {
                Schema::table('stocks', function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey);
                });
            }

            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('fk_inventory_id');
            });
        }

        Schema::dropIfExists('inventory');

        // Drop the orphaned inventory.* permissions and their role assignments.
        $permissionIds = DB::table('permissions')
            ->where('name', 'like', 'inventory.%')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('fk_permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        // The create migration was removed from the repository; drop its
        // historical record so the migrations table reflects the codebase.
        DB::table('migrations')
            ->where('migration', '0001_01_01_000020_create_inventory_table')
            ->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory')) {
            Schema::create('inventory', function (Blueprint $table) {
                $table->id();
                $table->string('service_name');
                $table->enum('status', ['available', 'out_of_stock', 'discontinued']);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('stocks') && ! Schema::hasColumn('stocks', 'fk_inventory_id')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->bigInteger('fk_inventory_id')->unsigned()->nullable();
                $table->index('fk_inventory_id');
                $table->foreign('fk_inventory_id')->references('id')->on('inventory')->onDelete('cascade');
            });
        }
    }
};
