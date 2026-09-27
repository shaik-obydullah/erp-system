<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            if (!Schema::hasColumn('contents', 'type')) {
                $table->string('type', 50)->default('page')->after('content');
            }
            if (!Schema::hasColumn('contents', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('type');
            }
            if (!Schema::hasColumn('contents', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('slug');
            }
            if (!Schema::hasColumn('contents', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('status');
            }
            if (!Schema::hasColumn('contents', 'created_at')) {
                $table->timestamps();
            }
            if (!Schema::hasColumn('contents', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('sort_order');
            }
            if (!Schema::hasColumn('contents', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('contents', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }
            if (!Schema::hasColumn('contents', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropColumn(['type', 'slug', 'status', 'sort_order', 'created_at', 'updated_at', 'created_by', 'updated_by', 'deleted_by', 'deleted_at']);
        });
    }
};
