<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Support\Str;

/**
 * An employee that already has payroll records must not be deleted. The
 * payroll carries salary and accounting history that would be orphaned, so
 * the employee should be marked inactive instead.
 */
class EmployeeDeletionGuard
{
    /**
     * Return the reasons why the employee cannot be deleted.
     *
     * @return list<string>
     */
    public function blockers(Employee $employee): array
    {
        $blockers = [];

        $payrolls = Payroll::where('fk_employee_id', $employee->id)->count();
        if ($payrolls > 0) {
            $blockers[] = $payrolls.' '.Str::plural('payroll record', $payrolls);
        }

        return $blockers;
    }

    /**
     * Build the user-facing message for a set of blockers.
     *
     * @param  list<string>  $blockers
     */
    public function message(array $blockers): string
    {
        return 'This employee cannot be deleted because it has '
            .$this->join($blockers)
            .'. Mark the employee as inactive instead.';
    }

    /**
     * @param  list<string>  $blockers
     */
    private function join(array $blockers): string
    {
        if (count($blockers) <= 1) {
            return (string) ($blockers[0] ?? '');
        }

        $last = array_pop($blockers);

        return implode(', ', $blockers).' and '.$last;
    }
}
