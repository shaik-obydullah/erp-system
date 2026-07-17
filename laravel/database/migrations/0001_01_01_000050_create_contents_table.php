<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends AuditableMigration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('attribute')->nullable();
            $table->string('media')->nullable();
            $table->text('content')->nullable();
            $table->string('type', 50)->default('page');
            $table->string('slug')->nullable()->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $this->addAuditColumns($table);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
