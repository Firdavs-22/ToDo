<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_name',
        'task_description',
        'task_priority',
        'created_date',
        'task_deadline',
        'completed',
        'favorite',
        'status'
    ];

    public function todoCategories()
    {
        return $this->hasMany(TodoCategory::class,'todo_id');
    }

    protected $table = 'todo_list';
    public $timestamps = false;
}
