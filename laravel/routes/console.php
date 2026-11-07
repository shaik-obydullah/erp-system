<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly double-entry safety net. The Accounting overview also shows the
// same checks as a "Books health" widget.
Schedule::command('accounting:verify')
    ->dailyAt('02:00')
    ->withoutOverlapping();
