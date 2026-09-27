<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (!Schema::hasColumn('activities', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('activities', 'subject_type')) {
                $table->string('subject_type')->nullable()->after('description');
            }
            if (!Schema::hasColumn('activities', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            }
            if (!Schema::hasColumn('activities', 'new_data')) {
                $table->text('new_data')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('activities', 'old_data')) {
                $table->text('old_data')->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['description', 'subject_type', 'subject_id', 'old_data', 'new_data']);
        });
    }
};
