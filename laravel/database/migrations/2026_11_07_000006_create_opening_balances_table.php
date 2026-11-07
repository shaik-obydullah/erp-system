<?php

use App\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpeningBalancesTable extends AuditableMigration
{
    public function up(): void
    {
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $this->addAuditColumns($table);
            $table->softDeletes();

            $table->unique(['account_id', 'fiscal_year']);
            $table->index('fiscal_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
    }
}
