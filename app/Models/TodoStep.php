<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoStep extends Model
{
    use HasFactory;

    public function todo()
    {
        return $this->belongsTo(TodoList::class, 'todo_id');
    }
    protected $fillable = [
        'step_name',
        'todo_id',
        'completed',
        'status',
    ];
    protected $hidden = [
        'todo',
    ];
    protected $table = 'todo_step';
    public $timestamps = false;
}
