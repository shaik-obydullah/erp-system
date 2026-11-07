<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJournalEntriesTable extends AuditableMigration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('entry_no', 30)->unique();
            $table->date('date');
            $table->string('memo', 255)->nullable();
            $table->string('event_type', 40);
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_key', 120)->unique();
            $table->enum('status', ['draft', 'posted', 'void'])->default('posted');
            $table->boolean('is_auto')->default(true);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $this->addAuditColumns($table);

            $table->index('date');
            $table->index('event_type');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
}
