<?php

namespace App\Accounting;

use App\Models\Account;
use RuntimeException;

class ChartOfAccounts
{
    /** @var array<string, int> */
    private array $ids = [];

    public function id(string $systemKey): int
    {
        if (isset($this->ids[$systemKey])) {
            return $this->ids[$systemKey];
        }

        $id = Account::query()->where('system_key', $systemKey)->value('id');

        if (! $id) {
            throw new RuntimeException(
                "Accounting account [{$systemKey}] is not configured. Run: php artisan db:seed --class=ChartOfAccountsSeeder"
            );
        }

        return $this->ids[$systemKey] = (int) $id;
    }

    public function flush(): void
    {
        $this->ids = [];
    }
}
