<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'fk_employee_id', 'basic_salary', 'allowances',
        'deductions', 'net_salary', 'pay_date',
        'journal_entry_id', 'accounting_version',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'pay_date' => 'date',
        'accounting_version' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'fk_employee_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
