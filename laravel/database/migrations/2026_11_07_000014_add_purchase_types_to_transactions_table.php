<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPurchaseTypesToTransactionsTable extends Migration
{
    private const TYPES = [
        'userFund',
        'supplierDeposit',
        'supplierPayment',
        'bomExpense',
        'salaryPayment',
        'campaignExpense',
        'stockIn',
        'stockOut',
        'saleIncome',
        'saleDue',
        'fixedAsset',
        'income',
        'expense',
        'miscIncome',
        'miscExpense',
        'shipmentCost',
        'purchase',
        'purchaseReturn',
        'stockAdjustmentLoss',
        'stockAdjustmentGain',
    ];

    public function up(): void
    {
        $this->setTypeEnum(self::TYPES);
    }

    public function down(): void
    {
        if (! $this->supportsEnumAlter()) {
            return;
        }

        // Keep old rows readable if someone rolls the enum back.
        DB::table('transactions')
            ->where('type', 'purchase')
            ->update(['type' => 'expense']);

        DB::table('transactions')
            ->where('type', 'purchaseReturn')
            ->update(['type' => 'miscIncome']);

        $this->setTypeEnum(array_values(array_diff(self::TYPES, [
            'purchase',
            'purchaseReturn',
        ])));
    }

    private function supportsEnumAlter(): bool
    {
        // SQLite (used by the test suite) stores enums as plain text and does
        // not understand MySQL's MODIFY ... ENUM syntax.
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function setTypeEnum(array $types): void
    {
        if (! $this->supportsEnumAlter()) {
            return;
        }

        $values = implode(', ', array_map(fn (string $type) => "'{$type}'", $types));

        DB::statement("ALTER TABLE `transactions` MODIFY `type` ENUM({$values}) NOT NULL");
    }
}
