<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskManagement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'task_management';

    public $timestamps = false;

    protected $fillable = [
        'fk_employee_id', 'task_name', 'description',
        'status', 'start_date', 'due_date',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'fk_employee_id');
    }
}
