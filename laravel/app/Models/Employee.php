<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'mobile',
        'job_title', 'salary', 'hire_date', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'hire_date' => 'date',
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'fk_employee_id');
    }

    public function tasks()
    {
        return $this->hasMany(TaskManagement::class, 'fk_employee_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
